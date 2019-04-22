<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>WS Test</title>
</head>
<body>
    <div id="messages">

    </div>
</body>
<script>
    var ws = null;

    document.addEventListener("DOMContentLoaded", function() {
        'use strict';

        var messages = document.getElementById('messages');

        function start() {
            if (ws) {
                ws.close();
            }
            ws = new WebSocket('ws://localhost:8888/ws/');
            ws.onopen = function(){
                messages.innerHTML = "Connection established!" + '<br>';
            };
            ws.onmessage = function(e){
                messages.innerHTML = "Connection established!" + '<br>' + e.data + '<br>';
            };
            ws.onclose = function(){
                messages.innerHTML = "Connection closed, reconnect..." +  '<br>';
                check();
            };
        }

        function check() {
            if (!ws || ws.readyState !== ws.OPEN) {
                console.log('reconnecting...');
                start();
            }
            return true;
        }

        start();

        setInterval(check, 5000);
    });
</script>
</html>
<?php
