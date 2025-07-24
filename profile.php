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

// 4. Obtener repositorios
$reposUrl = "https://api.github.com/users/$username/repos?per_page=100&sort=updated";
$reposJson = file_get_contents($reposUrl, false, $context);
$repos = json_decode($reposJson, true);

// 5. Procesar lenguajes y repos
$languages = [];
$topRepos = [];

foreach ($repos as $repo) {
    if ($repo['stargazers_count'] > 0) {
        $topRepos[] = $repo;
        $lang = $repo['language'] ?? 'Sin especificar';
        $languages[$lang] = ($languages[$lang] ?? 0) + 1;
    }
}

// Ordenar repos por estrellas (desc)
usort($topRepos, fn($a, $b) => $b['stargazers_count'] - $a['stargazers_count']);
$topRepos = array_slice($topRepos, 0, 6);

// Preparar datos para Chart.js
$chartLabels = array_keys($languages);
$chartData = array_values($languages);

// Obtener eventos públicos
$eventsUrl = "https://api.github.com/users/$username/events/public?per_page=10";
$eventsJson = file_get_contents($eventsUrl, false, $context);
$events = json_decode($eventsJson, true);
?>

<!DOCTYPE html>
<html>

<head>
    <title><?= htmlspecialchars($data['name'] ?? $username) ?> - DevFolio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #ffffff;
            --fg: #000000;
            --card: #f8f9fa;
        }

        [data-theme="dark"] {
            --bg: #0d1117;
            --fg: #c9d1d9;
            --card: #161b22;
        }

        body {
            background-color: var(--bg) !important;
            color: var(--fg) !important;
            transition: background .3s, color .3s;
        }

        .card {
            background-color: var(--card) !important;
            border-color: var(--card);
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="text-end mb-3">
            <button id="themeToggle" class="btn btn-outline-secondary btn-sm">
                🌓
            </button>
        </div>
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

    <!-- Repositorios destacados -->
    <div class="container mt-5">
        <h3>Repositorios destacados</h3>
        <div class="row">
            <?php foreach ($topRepos as $repo): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($repo['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($repo['description'] ?? 'Sin descripción') ?></p>
                            <a href="<?= $repo['html_url'] ?>" target="_blank" class="btn btn-primary btn-sm">
                                ★ <?= $repo['stargazers_count'] ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Gráfico de lenguajes -->
    <div class="container mt-5">
        <h3>Lenguajes más usados</h3>
        <canvas id="languagesChart" width="400" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('languagesChart');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <!-- Actividad reciente -->
    <div class="container mt-5">
        <h3>Actividad reciente</h3>
        <ul class="list-group">
            <?php foreach ($events as $event): ?>
                <?php
                $type = $event['type'];
                $repo = $event['repo']['name'] ?? '';
                $action = match ($type) {
                    'PushEvent' => 'hizo push en',
                    'WatchEvent' => 'dio ⭐ a',
                    'ForkEvent' => 'fork',
                    default => $type
                };
                ?>
                <li class="list-group-item">
                    <small class="text-muted"><?= date('d/m H:i', strtotime($event['created_at'])) ?></small>
                    <span><?= $action ?> <strong><?= htmlspecialchars($repo) ?></strong></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
        (() => {
            const html = document.documentElement;
            const btn = document.getElementById('themeToggle');

            // Leer tema guardado
            const saved = localStorage.getItem('theme');
            if (saved) html.setAttribute('data-theme', saved);

            // Alternar
            btn.addEventListener('click', () => {
                const current = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', current);
                localStorage.setItem('theme', current);
            });
        })();
    </script>
</body>

</html>