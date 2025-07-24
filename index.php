<?php
if (isset($_POST['username']) && !empty(trim($_POST['username']))) {
    $user = trim($_POST['username']);
    header("Location: profile.php?user=" . urlencode($user));
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DevFolio.me - Generador de Portafolio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #0f0f0f, #1a1a2e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradient 12s ease infinite;
            font-family: 'Inter', sans-serif;
            color: #e5e5e5;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem 4rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        p {
            color: #a1a1aa;
            margin-bottom: 2rem;
        }
        input[type="text"] {
            width: 100%;
            max-width: 300px;
            padding: 0.75rem 1rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            outline: none;
            margin-bottom: 1rem;
        }
        input::placeholder {
            color: #a1a1aa;
        }
        button {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        button:hover {
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.6);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="glass">
        <h1>DevFolio.me</h1>
        <p>Genera tu portafolio dev en segundos</p>
        <form method="post" autocomplete="off">
            <input type="text" name="username" placeholder="Tu username de GitHub" required>
            <br>
            <button type="submit">Generar portafolio</button>
        </form>
    </div>
</body>
</html>