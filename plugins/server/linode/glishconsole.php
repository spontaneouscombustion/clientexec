<?php
$data = base64_decode($_GET["tokens"]);
$explodeData = explode("&", $data);

//Finding Host value
$hostVal = explode("=", $explodeData[0])[1];
$portVal = explode("=", $explodeData[1])[1];
$encryptVal = explode("=", $explodeData[2])[1];
$tokenVal = explode("=", $explodeData[3])[1];
?>
<!DOCTYPE html>
<html>
<head>
    <title>noVNC</title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

    <!-- Stylesheets -->
    <link rel="stylesheet" href="./noVNC/app/styles/lite.css">

     <!--
    <script type='text/javascript'
        src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>
    -->

    <!-- promise polyfills promises for IE11 -->
    <script src="./noVNC/vendor/promise.js"></script>
    <!-- ES2015/ES6 modules polyfill -->
    <script type="module">
        window._noVNC_has_module_support = true;
    </script>
    <script>
        window.addEventListener("load", function() {
            if (window._noVNC_has_module_support) return;
            var loader = document.createElement("script");
            loader.src = "./noVNC/vendor/browser-es-module-loader/dist/browser-es-module-loader.js";
            document.head.appendChild(loader);
        });
    </script>

    <!-- actual script modules -->
    <script type="module" crossorigin="anonymous">
        // Load supporting scripts
        import * as WebUtil from './noVNC/app/webutil.js';
        import RFB from './noVNC/core/rfb.js';

        var rfb;
        var desktopName;

        function updateDesktopName(e) {
            desktopName = e.detail.name;
        }
        function credentials(e) {
            var html;

            var form = document.createElement('form');
            form.innerHTML = '<label></label>';
            form.innerHTML += '<input type=password size=10 id="password_input">';
            form.onsubmit = setPassword;

            // bypass status() because it sets text content
          //  document.getElementByName('noVNC_status_bar').setAttribute("class", "noVNC_status_warn");
            document.getElementById('noVNC_status').innerHTML = '';
            document.getElementById('noVNC_status').appendChild(form);
            document.getElementById('noVNC_status').querySelector('label').textContent = 'Password Required: ';
        }
        function setPassword() {
            rfb.sendCredentials({ password: document.getElementById('password_input').value });
            return false;
        }
        function sendCtrlAltDel() {
            rfb.sendCtrlAltDel();
            return false;
        }
        function machineShutdown() {
            rfb.machineShutdown();
            return false;
        }
        function machineReboot() {
            rfb.machineReboot();
            return false;
        }
        function machineReset() {
            rfb.machineReset();
            return false;
        }
        function status(text, level) {
            switch (level) {
                case 'normal':
                case 'warn':
                case 'error':
                    break;
                default:
                    level = "warn";
            }
    //        document.getElementByName('noVNC_status_bar').className = "noVNC_status_" + level;
            document.getElementById('noVNC_status').textContent = text;
        }

        function connected(e) {
          //  document.getElementById('sendCtrlAltDelButton').disabled = false;
            if (WebUtil.getConfigVar('encrypt',
                                     (window.location.protocol === "https:"))) {
                status("Connected (encrypted) to " + desktopName, "normal");
            } else {
                status("Connected (unencrypted) to " + desktopName, "normal");
            }
        }

        function disconnected(e) {
          //  document.getElementById('sendCtrlAltDelButton').disabled = true;
          //  updatePowerButtons();
            if (e.detail.clean) {
                status("Disconnected", "normal");
            } else {
                status("Something went wrong, connection is closed", "error");
            }
        }


        function updatePowerButtons() {
            var powerbuttons;
            powerbuttons = document.getElementById('noVNC_power_buttons');
            if (rfb.capabilities.power) {
                powerbuttons.className= "noVNC_shown";
            } else {
                powerbuttons.className = "noVNC_hidden";
            }
        }

      //  document.getElementById('sendCtrlAltDelButton').onclick = sendCtrlAltDel;
      //  document.getElementById('machineShutdownButton').onclick = machineShutdown;
      //  document.getElementById('machineRebootButton').onclick = machineReboot;
      //  document.getElementById('machineResetButton').onclick = machineReset;

        WebUtil.init_logging(WebUtil.getConfigVar('logging', 'warn'));
        document.title = WebUtil.getConfigVar('title', 'noVNC');
        // By default, use the host and port of server that served this file
        var host = WebUtil.getConfigVar('host', "<?php echo $hostVal; ?>");
        var port = WebUtil.getConfigVar('port', "<?php echo $portVal; ?>");


        // if port == 80 (or 443) then it won't be present and should be
        // set manually
        if (!port) {
            if (window.location.protocol.substring(0,5) == 'https') {
                port = 443;
            }
            else if (window.location.protocol.substring(0,4) == 'http') {
                port = 80;
            }
        }

        var password = WebUtil.getConfigVar('password', '');
        var path = WebUtil.getConfigVar('path', '');

        // If a token variable is passed in, set the parameter in a cookie.
        // This is used by nova-novncproxy.
        var token = WebUtil.getConfigVar('token', "<?php echo $tokenVal; ?>");
        if (token) {
            // if token is already present in the path we should use it
            path = WebUtil.injectParamIfMissing(path, "token", token);

            WebUtil.createCookie('token', token, 1)
        }

        (function() {

            status("Connecting", "normal");

            if ((!host) || (!port)) {
                status('Must specify host and port in URL', 'error');
            }

            var url;

            if (WebUtil.getConfigVar('encrypt',
                                     (window.location.protocol === "https:"))) {
                url = 'wss';
            } else {
                url = 'wss';
            }

            url += '://' + host.slice(7) ;
            if(port) {
                url += ':' + port;
            }
            url += '/' + path;
            url = url.replace("?token=", "");

            rfb = new RFB(document.body, url,
                          { repeaterID: WebUtil.getConfigVar('repeaterID', ''),
                            shared: WebUtil.getConfigVar('shared', true),
                            credentials: { password: password } });
            rfb.viewOnly = WebUtil.getConfigVar('view_only', false);
            rfb.addEventListener("connect",  connected);
            rfb.addEventListener("disconnect", disconnected);
            rfb.addEventListener("capabilities", function () { updatePowerButtons(); });
            rfb.addEventListener("credentialsrequired", credentials);
            rfb.addEventListener("desktopname", updateDesktopName);
            rfb.scaleViewport = WebUtil.getConfigVar('scale', false);
            rfb.resizeSession = WebUtil.getConfigVar('resize', false);
        })();
    </script>
</head>

<body>
  <div id="noVNC_status_bar">
    <div id="noVNC_left_dummy_elem"></div>
    <div id="noVNC_status">Loading</div>
    <!--
    <div id="noVNC_buttons">
      <input type=button value="Send Ctrl+Alt+Delete"
             id="sendCtrlAltDelButton" class="noVNC_shown">
      <span id="noVNC_power_buttons" class="noVNC_hidden">
        <input type=button value="Shutdown"
               id="machineShutdownButton">
        <input type=button value="Reboot"
               id="machineRebootButton">
        <input type=button value="Reset"
               id="machineResetButton">
      </span>
    </div>
  -->
  </div>
</body>
</html>
