<?php header('Access-Control-Allow-Origin: http://localhost:8000'); ?>
<?php header('Strict-Transport-Security: max-age=31536000;'); ?>
<?php header('Content-Security-Policy: default-src \'self\';'); ?>
<?php header('HTTP/1.1 301 Moved Permanently'); ?>
<?php header('Location: https://www.google.com'); ?>
<?php exit; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>301 Page</title>
</head>

<body>
    <h1>301 Page</h1>
    <p>Testing that response code 301 is allowed.</p>
</body>

</html>