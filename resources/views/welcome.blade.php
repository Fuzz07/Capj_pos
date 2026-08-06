<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url={{ route(auth()->check() ? 'dashboard' : 'login') }}">
    <title>Captain J POS System</title>
</head>
<body>
    <p>Redirecting... <a href="{{ route(auth()->check() ? 'dashboard' : 'login') }}">Click here if not redirected.</a></p>
</body>
</html>
