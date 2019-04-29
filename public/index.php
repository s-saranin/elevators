<?
    require_once './../bootstrap.php';

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Elevator simulation</title>
    <link rel="stylesheet" type="text/css" href="assets/style.css">
</head>
<body>
    <div class="wrapper">
        <div class="frame-columns">
            <div class="frame order">
                <span class="frame__label">Order</span>
                <div>
                    <label>
                        <span>Floor: </span>
                        <select id="query-order-floor-value">
                            <? foreach (range(1, 10) as $floor) : ?>
                                <option value="<?= $floor ?>"><?= $floor ?></option>
                            <? endforeach; ?>
                        </select>
                    </label>
                    <button id="query-order-floor">Send</button>
                </div>
            </div>
            <div class="frame query">
                <span class="frame__label">Query</span>
                <div>
                    <button>Orders</button>
                    <button>Statistics</button>
                    <button>Iterations</button>
                </div>
            </div>
        </div>
        <div class="frame-columns">
            <div class="frame simulation">
                <span class="frame__label">Output</span>
                <pre id="simulation-output"></pre>
            </div>
            <div class="frame information">
                <span class="frame__label">Information</span>
                <pre id="information-output"></pre>
            </div>
        </div>
    </div>

</body>
<script>
    var ws = null;

    document.addEventListener("DOMContentLoaded", function() {
        'use strict';

        var messages = document.getElementById('simulation-output');

        function start() {
            if (ws) {
                ws.close();
            }
            ws = new WebSocket('ws://<? echo WS_HOST?>:8888/ws/');
            ws.onopen = function(){
                messages.innerHTML = "Connection established!" + '<br>';
            };
            ws.onmessage = function(message) {
                message = JSON.parse(message.data);

                switch (message.type) {
                    case 'render':
                        messages.innerHTML = "Connection established!" + '<br>' + message.value + '<br>';
                        break;
                    default:
                        console.log(message.value);
                }
            };
            ws.onclose = function(){
                messages.innerHTML = "Connection closed, reconnect..." +  '<br>';
            };
        }

        function check() {
            if (!ws || ws.readyState !== ws.OPEN) {
                start();
            }
            return true;
        }

        start();

        setInterval(check, 5000);

        document.getElementById('query-order-floor').addEventListener(
            "click",
            function() {
                if (ws.readyState === ws.OPEN) {
                    var select = document.getElementById('query-order-floor-value');
                    ws.send(JSON.stringify({
                        'type': 'order',
                        'value': select.options[select.selectedIndex].value
                    }));
                }
            });
        });
</script>
</html>
<?php
