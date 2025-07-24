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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            --bg: #0f0f0f;
            --glass: rgba(255, 255, 255, 0.08);
            --accent: #6366f1;
            --text: #e5e5e5;
            --text-secondary: #a1a1aa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #0f0f0f, #1a1a2e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            color: var(--text);
            min-height: 100vh;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .glass {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .profile-header {
            text-align: center;
            margin: 3rem 0;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--glass);
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .repo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .repo-card {
            background: var(--glass);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .repo-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
        }

        .btn-glow {
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-glow:hover {
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.6);
            transform: translateY(-2px);
        }

        .timeline {
            max-width: 800px;
            margin: 2rem auto;
        }

        .timeline-item {
            background: var(--glass);
            margin: 1rem 0;
            padding: 1.5rem;
            border-radius: 16px;
            border-left: 3px solid var(--accent);
        }

        .chart-container {
            max-width: 600px;
            margin: 3rem auto;
            background: var(--glass);
            padding: 2rem;
            border-radius: 20px;
        }

        @media print {
            body {
                background: var(--bg) !important;
                color: var(--text) !important;
            }

            #downloadBtn {
                display: none;
            }
        }

        #qrCanvas {
            display: inline-block;
            margin: 1rem auto;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Perfil -->
        <div class="glass profile-header">
            <img src="<?= $data['avatar_url'] ?>" alt="Avatar" class="profile-avatar">
            <h1 style="font-weight: 700; font-size: 2.5rem; margin: 1rem 0;"><?= htmlspecialchars($data['name'] ?? $username) ?></h1>
            <p style="color: var(--text-secondary); font-size: 1.2rem;">@<?= $username ?></p>
            <p><?= htmlspecialchars($data['bio'] ?? '') ?></p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $data['public_repos'] ?></h3>
                <p>Repositorios</p>
            </div>
            <div class="stat-card">
                <h3><?= $data['followers'] ?></h3>
                <p>Seguidores</p>
            </div>
            <div class="stat-card">
                <h3><?= $data['following'] ?></h3>
                <p>Siguiendo</p>
            </div>
        </div>

        <!-- Lenguajes -->
        <div class="chart-container">
            <h3 style="text-align: center; margin-bottom: 2rem;">Lenguajes</h3>
            <canvas id="languagesChart"></canvas>
        </div>

        <!-- Repos -->
        <div class="repo-grid">
            <?php foreach ($topRepos as $repo): ?>
                <div class="repo-card">
                    <h4><?= htmlspecialchars($repo['name']) ?></h4>
                    <p style="color: var(--text-secondary);"><?= htmlspecialchars($repo['description'] ?? 'Sin descripción') ?></p>
                    <a href="<?= $repo['html_url'] ?>" target="_blank" class="btn-glow">
                        ★ <?= $repo['stargazers_count'] ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Timeline -->
        <div class="timeline">
            <h3 style="text-align: center; margin-bottom: 2rem;">Actividad Reciente</h3>
            <?php foreach (array_slice($events, 0, 5) as $event): ?>
                <div class="timeline-item">
                    <strong><?= date('d/m H:i', strtotime($event['created_at'])) ?></strong> -
                    <?= $event['type'] ?> en <strong><?= htmlspecialchars($event['repo']['name']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart.js (igual que antes)
        new Chart(document.getElementById('languagesChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e5e5e5'
                        }
                    }
                }
            }
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>


    <!-- Compartir y QR -->
    <div class="glass" style="margin-top: 4rem; text-align: center;">
        <h3 style="margin-bottom: 1rem;">Comparte tu portafolio</h3>

        <!-- Botones -->
        <a id="tw" class="btn-glow" style="margin: .5rem;" href="#" target="_blank">🐦 Twitter</a>
        <a id="li" class="btn-glow" style="margin: .5rem;" href="#" target="_blank">💼 LinkedIn</a>

        <!-- QR -->
        <div style="margin-top: 1.5rem;">
            <canvas id="qrCanvas"></canvas>
        </div>
    </div>

    <script>
        (() => {
            const url = encodeURIComponent(location.href);
            const text = encodeURIComponent('Echa un vistazo a mi portafolio DevFolio 🚀');

            // Links de redes
            document.getElementById('tw').href = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
            document.getElementById('li').href = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;

            // Generar QR
            QRCode.toCanvas(document.getElementById('qrCanvas'), location.href, {
                width: 160,
                margin: 2,
                color: {
                    dark: '#e5e5e5',
                    light: '#0f0f0f00'
                }
            });
        })();
    </script>
    <button id="downloadBtn" style="
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 999;
  background: linear-gradient(135deg, var(--accent), #8b5cf6);
  border: none;
  color: white;
  padding: 14px 28px;
  border-radius: 50px;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
" onmouseover="this.style.boxShadow='0 6px 30px rgba(99,102,241,.7)'"
        onmouseout="this.style.boxShadow='0 4px 20px rgba(99,102,241,.4)'">
        📄 Descargar PDF
    </button>

    <script>
        document.getElementById('downloadBtn').addEventListener('click', () => {
            const element = document.body; // captura toda la página
            const opt = {
                margin: 0,
                filename: '<?= $username ?>-devfolio.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</body>

</html>