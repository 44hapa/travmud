<!DOCTYPE html>
<?php
require_once('./../config.php');
$config = Config::getConfig();
?>
<html>
    <head>
        <title>Role playing Game with RPG JS</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

        <!-- Import jQuery (optional) -->
        <script src="http://code.jquery.com/jquery-1.6.min.js"></script>

        <!-- Import Rpg.js Beta 2 -->
        <script src="rpg-beta-2.js"></script>

        <!-- Import generated animations -->
        <script src="Database/Animation.js"></script>
        <script src="Database/Lang.js"></script>
        <script src="Database/Arpg.js"></script>
        <link rel="stylesheet" href="style.css" type="text/css">

<!--  WEBSOCKET      -->
        <style type="text/css">
            html,body {
                font:normal 0.9em arial,helvetica;
            }
            #log {
                width:600px;
                height:300px;
                border:1px solid #7F9DB9;
                overflow:auto;
            }
            #msg {
                width:400px;
            }
            #websocket-parent {
                width: 600px;
                position: absolute;
                right: 10px;
            }
        </style>


        <script type="text/javascript">
            var socket;
            var mapFromSocket;

            function initWbsocket() {
                var host = "ws://<?php echo ($config['websocket']['addr'].':'.$config['websocket']['port']) ?>"; // SET THIS TO YOUR SERVER
                try {
                    socket = new WebSocket(host);
                    log('WebSocket - status '+socket.readyState);
                    socket.onopen    = function(msg) {
                        log("Welcome - status "+this.readyState);
                    };
                    socket.onmessage = function(msg) {
                        var msgObj = $.parseJSON(msg.data);
                        console.log(msgObj);
                        var comparingDirection = {
                            север : 2,
                            юг : 8,
                            запад : 4,
                            восток : 6
                        }

                        if (null != msgObj.message) {
                            log("Received: "+msgObj.message);
                        }

                        if (msgObj.partMap){
                            mapFromSocket = msgObj.partMap;
//=========================================== NEED REMAKE ===========================================
                            rpg.loadMap('XpeH', {
                                tileset: 'village.png',
                                autotiles: ['sol11.png'],
                                bgm:  {mp3: 'Town', ogg: 'Town'},
                                player:  {
                                    x: 10,
                                    y: 10,
                                    direction: 'up',
                                    filename: 'Hero.png',
                                    regX: 30,
                                    regY: 55,
                                    actionBattle: {
                                        hp_max: 200,
                                        actions: ['myattack']
                                    },
                                    actions: ['myattack']
                                }

                            }, false, false, mapFromSocket);
//=========================================== NEED REMAKE ===========================================
                        }

                        // Что прислал моб
                        if (msgObj.mob.name){
                            var mobName = msgObj.mob.name;
                            // какое действие осуществил моб

                            // Подгружаем нового монстра
                            if (msgObj.mob.actionType == 'create'){
                                dataFromSocket = msgObj.mob.actionValue[mobName];
                                rpg.prepareEventAjax(mobName, false, dataFromSocket);
                                // create monster
                                rpg.setEventPrepared(mobName, {x: 8, y: 13});
                                rpg.addEventPrepared(mobName);
                            }
                        }

                        // Смотрим, прислал ли что какой-нибудь чар (не мы!!!)
                        if (msgObj.user.name){
                            var userName = msgObj.user.name;
                            // смотрим, какое действие прислал чар.

                            // Коннект нового чара
                            if (msgObj.user.actionType == 'connectChar'){
                                dataFromSocket = msgObj.user.actionValue[userName];
                                rpg.prepareEventAjax(userName, false, dataFromSocket);
                                // create char
                                rpg.setEventPrepared(userName);
                                rpg.addEventPrepared(userName);
                            }

                            // Движуха чара
                            if (msgObj.user.actionType == 'move'){
                                var charObj = rpg.getEventByName(msgObj.user.name);
                                dataFromSocket = msgObj.user.actionValue;
                                console.log(comparingDirection);
                                console.log(dataFromSocket);
                                charObj.moreMove(comparingDirection[dataFromSocket],1);
//                                charObj.move(comparingDirection[dataFromSocket]);
                            }
                            // Смерть чара
                            if (msgObj.user.actionType == 'die'){
                                var charObj = rpg.getEventByName(msgObj.user.name);
                                dataFromSocket = msgObj.user.actionValue;
                                rpg.animations['Darkness 1'].setPosition(charObj.x, charObj.y);
                                rpg.animations['Darkness 1'].play();

                                rpg.removeEvent(charObj.id)
//                                charObj.bitmap.visible = false;
                            }
                        }


                        // Это когда мы приконнектились и получили имя
                        if (msgObj.actionType == 'setPosition'){

                            // TODO: Нужно затереть карту, и загрузить ее заново!!!

                            // Создадим себя в нужных координатах
                            var myPosition = msgObj.actionValue.myPosition;
                            // Сделаем себя видимым
                            rpg.player.visible(true);
                            rpg.player.setPosition(myPosition.positionX,myPosition.positionY);
                            rpg.setCamera(myPosition.positionX, myPosition.positionY);

                            // Если уже кто-то приконнектился до нас (мы не первые) - отрисуем и их
                            if (msgObj.actionValue.charsPosition) {
                                for (var charName in msgObj.actionValue.charsPosition) {
                                    dataFromSocket = msgObj.actionValue.charsPosition[charName];
                                    rpg.prepareEventAjax(charName, false, dataFromSocket);
                                    // create char
                                    rpg.setEventPrepared(charName);
                                    rpg.addEventPrepared(charName);
                                }
                            }
                        }


                        // Получение текущего состояния
                        if (msgObj.actionType == 'prompt'){
                                var prompt = msgObj.actionValue;
                                viewHp = prompt.curentHealth * 200 / prompt.maxHealth;
                                $('#hp').animate({'width': viewHp + 'px'});
//                                console.log(msgObj.actionValue);
                                //$('#hp').animate({'width': ($('#hp').width() + msgObj.actionValue) + 'px'});
                        }

                        // Мы наносим удар
                        if (msgObj.actionType == 'strikeSword'){
                            var charObj = rpg.getEventByName(msgObj.actionValue);
                            // Повернемся лицом к противнику.
                            var xDir = rpg.player.x - charObj.x;
                            var yDir = rpg.player.y - charObj.y;

                            if (yDir > 0) {
                                rpg.player.setStopDirection("bottom");
                            }
                            if (yDir < 0) {
                                rpg.player.setStopDirection("up");
                            }
                            if (xDir > 0) {
                                rpg.player.setStopDirection("left");
                            }
                            if (xDir < 0) {
                                rpg.player.setStopDirection("right");
                            }

                            rpg.player.action('myattack');
                        }

                        // Враг наносит удар
                        if (msgObj.actionType == 'gotStrikeSword'){
                            var charObj = rpg.getEventByName(msgObj.user.name);
                            charObj.action('attack_ennemy');
                            // Если удар достиг цели
                            if (msgObj.actionValue > 0) {
                                // Мигаем
                                rpg.player.blink(15, 1);
                                // Отрисовываем уменьшение хелсов (думаем, что у нас их 200px)
                                var damagePx = 200 * msgObj.actionValue / 100;
                                $('#hp').animate({'width': ($('#hp').width() - damagePx) + 'px'});
                            }
                        }

                        // Мы умерли
                        if (msgObj.actionType == 'die'){
                            // Отрисовываем хелсы к ноль
                            $('#hp').animate({'width':  '0px'});
                            rpg.animations['Darkness 1'].setPosition(rpg.player.x, rpg.player.y);
                            rpg.animations['Darkness 1'].play();
                            rpg.player.visible(false);
                        }

                        if (msgObj.actionType == 'move') {
                            var responseDirection = msgObj.actionValue;
                            rpg.player.moreMove(comparingDirection[responseDirection],1);
                        }

                    };
                    socket.onclose   = function(msg) {
                        log("Disconnected - status "+this.readyState);
                    };
                }
                catch(ex){
                    log(ex);
                }
                $("#msg").focus();
            }

            function send(){
                var txt,msg;
                txt = $("#msg");
                msg = txt.val();
                if(!msg) {
                    alert("Message can not be empty");
                    return;
                }
                txt.val("");
                txt.focus();
                try {
                    socket.send(msg);
                    log('Sent: '+msg);
                } catch(ex) {
                    log(ex);
                }
            }
            function quit(){
                if (socket != null) {
                    log("Goodbye!");
                    socket.close();
                    socket=null;
                }
            }

            function reconnect() {
                quit();
                init();
            }

            // Utilities
            function log(msg){ $("#log").html($("#log").html() + "<br>"+msg); }
            function onkey(event){ if(event.keyCode==13){ send(); } }
        </script>
<!--WEBSOCKET END -->

        <script>

            var rpg;
            RPGJS.load({
                plugins: ["arpg", "gold"]
            }, function() {
                // Иничиализация Websocket
                initWbsocket();

                rpg = new Rpg("canvas_rpg");

                rpg.setLang("en");

                var cookie = getCookie();
//                if (cookie['rpg-volume'] == 0) {
                    $('#sound').trigger("click");
//                }

                // Место анимации (откуда отсчитываем координаты)
                rpg.setGraphicAnimation(192, 192);

                // Adding animation from file "Database.js"
                rpg.addAnimation(Database.animation['EM Exclamation']);
                rpg.addAnimation(Database.animation['Darkness 1']);
                rpg.addAnimation(Database.animation['Fang']);
                rpg.addAnimation(Database.animation['SP Recovery 2']);

                // Adding Actions
                rpg.addAction('attack_ennemy', {
                        action: 'attack',
                        suffix_motion: [''],
                        duration_motion: 1,
                        block_movement: true,
                        wait_finish: 1
                });

                rpg.addAction('myattack', {
                    action: 'attack', // for Action Battle System
                    suffix_motion: ['_SWD_1'], // suffix of the filename
                    duration_motion: 1, // animation loop
                    block_movement: true,
                    wait_finish: 5, // frame
                    speed: 25,
                    keypress: [Input.A],
                    condition: function() {
                        return rpg.switchesIsOn(2);
                    }
                });

                /**
                                Map Town
                 */
                rpg.loadMap('Test', {
                    tileset: 'village.png',
                    autotiles: ['sol11.png'],
                    bgm:  {mp3: 'Town', ogg: 'Town'},
                    player:  {
                        x: 10,
                        y: 10,
                        direction: 'up',
                        filename: 'Hero.png',
                        regX: 30,
                        regY: 55,
                        actionBattle: {
                            hp_max: 200,
                            actions: ['myattack']
                        },
                        actions: ['myattack']
                    }

                }, init);

                function mapLoadTemple() {
                    var order = 0;
                    var order_correct = true;

                    // Function called with the command "call"in the event
                    rpg.onEventCall('Switch01', function() {
                        order_valid(1, this);
                    });
                    rpg.onEventCall('Switch02', function() {
                        order_valid(2, this);
                    });
                    rpg.onEventCall('Switch03', function() {
                        order_valid(3, this);
                    });
                    rpg.onEventCall('Switch04', function() {
                        order_valid(4, this);
                    });

                    function order_valid(value, event) {
                        order++;
                        if (order != value) {
                            order_correct = false;
                        }
                        if (order == 4) {
                            if (order_correct) {
                                rpg.setSwitches(8, true);
                            }
                            else {
                                rpg.playSE('057-Wrong01.ogg');
                                event.commandsExit();
                                rpg.setSwitches([4, 5, 6, 7], false);
                                order = 0;
                                order_correct = true;
                            }
                        }
                    }

//                    createMonster('monster3', 9, 9);
                }


                function init() {
                    // Сделаем себя невидимыми
//                    rpg.player.bitmap.visible = false;
                    rpg.player.visible(false);

                    //Дадим мечик (он забинден на букву "а")
                    rpg.setSwitches(2, true);

                    rpg.player.useMouse(true);
//                    rpg.player.setTypeMove("real");
                    // Изменим тип хотьбы
                    rpg.player.setTypeMove("tile");

                    // Set the scrolling on the player
                    rpg.setScreenIn("Player");

                    // тестилка анимации
//                    rpg.animations['Darkness 1'].setPosition(10,10);
//                    rpg.animations['Darkness 1'].play();
//
//                    rpg.player.setStopDirection('up') -- изменение направления персонажа
//                    rpg.player.action('myattack') -- махать мечем

                    // Отображаем хелсы МОБОВ
//                    var qwe = new Arpg;
//                    qwe.displayBar(rpg.player, 10, 100, 70, 5);
//                    console.log(qwe)

                        // Хелсы юзера
//                      $('#hp').animate({'width': ($('#hp').width() - 5) + 'px'});
//                      // Мигание юзера
//                      rpg.player.blink(300, 1);
                }

                function createMonster(name, x, y) {
                    // Change positions and add events on the map
                    rpg.setEventPrepared(name, {x: x, y: y});
                    rpg.addEventPrepared(name);
                }

                function end() {
                    rpg.onEventCall('end', function() {
                        alert('Thank you for trying the mini-rpg !');
                        window.location.reload(true);
                    });
                }

                Input.lock(rpg.canvas, true);

            });



            function mapLoad() {
            }
        </script>


    </head>
    <body>
<!-- WEBSOCKET       -->
        <div id="websocket-parent">
            <h3>WebSocket v2.00</h3>
            <div id="log"></div>
            <input id="msg" type="textbox" onkeypress="onkey(event)"/>
            <button onclick="send()">Send</button>
            <button onclick="quit()">Quit</button>
            <button onclick="reconnect()">Reconnect</button>
        </div>
<!-- WEBSOCKET END       -->

        <div id="canvas_rpg-parent">
            <canvas id="canvas_rpg" width="640px" height="480px"></canvas>
            <div id="bar_hp">
                <div id="hp"></div>
            </div>
            <div id="header">
                <div id="sound" class="sound-play"></div>
                <div id="gold"> : <span>0</span></div>
                <div id="option"></div>
            </div>

            <div id="options" class="window">
                <h1>Options</h1>
                <fieldset><legend>Languages</legend>
                    <label><input type="radio" name="lang" value="en" checked> English</label><br />
                    <label><input type="radio" name="lang" value="fr"> Français</label>
                </fieldset>
                <h1>Help</h1>
                <ul>
                    <li>Enter or Space : Speak, Action</li>
                    <li>Arrows : Move</li>
                    <li>A : Attack (if sword equipped)</li>
                </ul>
                <h1>Embed</h1>
                <p>To put this game on your site, please copy and paste the code below : </p>
                <pre>
&lt;iframe src=&quot;http://rpgjs.com/examples/examples/demo-beta&quot; width&quot;640px&quot; wid480quot;768px&quot;&gt;&lt;/iframe&gt;
                </pre>
            </div>

        </div>

    </body>

    <script>
        //------ Optional
        $(function() {
            $('#sound').click(function() {
                var val;
                if ($(this).hasClass("sound-play")) {
                    $(this).removeClass('sound-play');
                    $(this).addClass('sound-mute');
                    val = 0;
                }
                else {
                    $(this).removeClass('sound-mute');
                    $(this).addClass('sound-play');
                    val = 1;
                }
                rpg.setVolumeAudio(val);
                setCookie("rpg-volume", val);
            });


            $('#option').toggle(function() {
                scene = new Scene(rpg);
                scene.setFreeze("all");
                $('#options').fadeIn("fast");
            }, function() {
                scene.exit();
                $('#options').fadeOut("fast");
                focus();
            });

            function focus() {
                $('#canvas_rpg-dom').focus();
            }

            $('input[name="lang"]').click(function() {
                rpg.setLang($(this).val());
            });


        });

        function setCookie (name, value) {
            var expire = new Date() ;
            expire.setTime(new Date().getTime() + 60*60*24*14);
            document.cookie = name + "=" + value + ";expires=" + expire.toGMTString();
        }

        function getCookie() {
            var value = "", name = "";
            var separator = document.cookie.indexOf( "=" );
            var ret = {};
            name = document.cookie.substring(0, separator);
            value = document.cookie.substring(separator + 1, separator + 2);
            ret[name] = value;
            return ret;
        }

        //---------

    </script>

</html>