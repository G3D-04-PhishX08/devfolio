<?php
// 1. Obtener username desde la URL
$username = $_GET['user'] ?? 'octocat'; // Default: octocat

// 2. Llamar a la API de GitHub
$apiUrl = "https://api.github.com/users/$username";
$context = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => [
            "User-Agent: PHP" // GitHub requiere User-Agent
        ]
    ]
]);
$response = file_get_contents($apiUrl, false, $context);
$data = json_decode($response, true);

// 3. Verificar si el usuario existe
if (isset($data['message']) && $data['message'] === 'Not Found') {
    die("Usuario no encontrado 😢");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($data['name'] ?? $username) ?> - DevFolio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="text-center">
            <img src="<?= $data['avatar_url'] ?>" class="rounded-circle" width="120">
            <h1 class="mt-3"><?= htmlspecialchars($data['name'] ?? $username) ?></h1>
            <p class="text-muted">@<?= $username ?></p>
            <p><?= htmlspecialchars($data['bio'] ?? 'Sin biografía') ?></p>
            <ul class="list-inline">
                <li class="list-inline-item"><strong><?= $data['public_repos'] ?></strong> Repos</li>
                <li class="list-inline-item"><strong><?= $data['followers'] ?></strong> Followers</li>
                <li class="list-inline-item"><strong><?= $data['following'] ?></strong> Following</li>
            </ul>
        </div>
    </div>
</body>
</html>