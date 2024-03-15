<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
        <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
        <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo htmlspecialchars($_POST['token']); ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
        <script>
            iFrameResize({},'#paytriframe');
        </script>
    </body>
</html>