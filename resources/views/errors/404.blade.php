<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Página no encontrada</title>
    <style>
        body {
            background-color: #121212;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        h1 {
            font-size: 6rem;
            color: #00ff88;
            margin: 0;
        }
        h2 {
            font-size: 1.8rem;
            margin: 10px 0;
        }
        p {
            color: #ccc;
            margin: 10px 0 30px;
        }
        a {
            text-decoration: none;
            background: #00ff88;
            color: #121212;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: bold;
            transition: 0.3s;
        }
        a:hover {
            background: #00cc66;
        }
    </style>
</head>
<body>
    <h1>404</h1>
    <h2>Página no encontrada</h2>
    <p>Ups 😅, parece que la página que buscás no existe o fue movida.</p>
    <a href="{{ url('/') }}">Volver al inicio</a>
</body>
</html>
