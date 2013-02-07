<!DOCTYPE html>
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

            function initWbsocket() {
                var host = "ws://127.0.0.1:8000"; // SET THIS TO YOUR SERVER
                try {
                    socket = new WebSocket(host);
                    log('WebSocket - status '+socket.readyState);
                    socket.onopen    = function(msg) {
                        log("Welcome - status "+this.readyState);
                    };
                    socket.onmessage = function(msg) {
                        log("Received: "+msg.data);
                    };
                    socket.onclose   = function(msg) {
                        log("Disconnected - status "+this.readyState);
                    };
                }
                catch(ex){
                    log(ex);
                }
                $_("msg").focus();
            }

            function send(){
                var txt,msg;
                txt = $_("msg");
                msg = txt.value;
                if(!msg) {
                    alert("Message can not be empty");
                    return;
                }
                txt.value="";
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
            function $_(id){ return document.getElementById(id); }
            function log(msg){ $_("log").innerHTML+="<br>"+msg; }
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
                if (cookie['rpg-volume'] == 0) {
                    $('#sound').trigger("click");
                }

                // Adding Animation
                // Adding animation from file "Database.js"


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

                    createMonster('monster3', 9, 9);
                }


                function init() {
                    rpg.player.useMouse(true);
                    rpg.player.setTypeMove("real");

                    // Set the scrolling on the player
                    rpg.setScreenIn("Player");

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