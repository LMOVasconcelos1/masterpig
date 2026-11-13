<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Atualizando sistema — Sui Control</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Figtree', system-ui, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 50%, #fbbf24 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Bolhas flutuantes de fundo */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            animation: float-up linear infinite;
            pointer-events: none;
        }

        @keyframes float-up {
            0% { transform: translateY(100vh) scale(0.5); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) scale(1); opacity: 0; }
        }

        .container {
            text-align: center;
            z-index: 10;
            padding: 2rem;
            max-width: 520px;
            width: 100%;
        }

        .pig-stage {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 1.5rem;
        }

        /* Suíno em SVG animado */
        .pig-svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.15));
            animation: pig-bounce 1.2s ease-in-out infinite;
        }

        @keyframes pig-bounce {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-15px) rotate(2deg); }
        }

        /* Rabo do porco balançando */
        .tail {
            transform-origin: 50% 50%;
            animation: tail-wag 0.4s ease-in-out infinite;
        }

        @keyframes tail-wag {
            0%, 100% { transform: rotate(-10deg); }
            50% { transform: rotate(15deg); }
        }

        /* Orelha piscando */
        .ear {
            transform-origin: bottom center;
            animation: ear-twitch 2s ease-in-out infinite;
        }

        @keyframes ear-twitch {
            0%, 90%, 100% { transform: rotate(0deg); }
            95% { transform: rotate(-15deg); }
        }

        /* Olho piscando */
        .eye {
            transform-origin: center;
            animation: blink 3s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 92%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }

        /* Focinho se mexendo */
        .snout {
            animation: snout-sniff 1.5s ease-in-out infinite;
        }

        @keyframes snout-sniff {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #7c2d12;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 1rem;
            color: #9a3412;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .progress-wrapper {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 999px;
            padding: 6px;
            margin-bottom: 1rem;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.1);
        }

        .progress-bar {
            height: 24px;
            background: linear-gradient(90deg, #f97316, #ef4444, #f97316);
            background-size: 200% 100%;
            border-radius: 999px;
            transition: width 0.5s ease;
            animation: progress-shine 2s linear infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes progress-shine {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: progress-sweep 1.5s linear infinite;
        }

        @keyframes progress-sweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: #7c2d12;
            margin-bottom: 0.5rem;
        }

        .current-task {
            font-size: 0.95rem;
            color: #9a3412;
            min-height: 1.5rem;
            font-weight: 500;
        }

        .version-badge {
            display: inline-block;
            background: rgba(124, 45, 18, 0.15);
            color: #7c2d12;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .error-box {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 1rem;
            padding: 1rem;
            margin-top: 1.5rem;
            color: #991b1b;
            font-size: 0.9rem;
            display: none;
        }

        .error-box.show { display: block; }

        .retry-btn {
            margin-top: 1rem;
            padding: 0.6rem 1.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .retry-btn:hover { background: #dc2626; }
    </style>
</head>
<body>

    <!-- Bolhas de fundo -->
    <div class="bubble" style="width: 60px; height: 60px; left: 10%; animation-duration: 8s; animation-delay: 0s;"></div>
    <div class="bubble" style="width: 40px; height: 40px; left: 25%; animation-duration: 6s; animation-delay: 2s;"></div>
    <div class="bubble" style="width: 80px; height: 80px; left: 70%; animation-duration: 10s; animation-delay: 1s;"></div>
    <div class="bubble" style="width: 30px; height: 30px; left: 85%; animation-duration: 7s; animation-delay: 3s;"></div>
    <div class="bubble" style="width: 50px; height: 50px; left: 50%; animation-duration: 9s; animation-delay: 0.5s;"></div>

    <div class="container">
        <!-- Suíno animado em SVG -->
        <div class="pig-stage">
            <svg class="pig-svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <!-- Corpo -->
                <ellipse cx="100" cy="120" rx="65" ry="50" fill="#f4a8b8" stroke="#e0859a" stroke-width="3"/>

                <!-- Rabo -->
                <g class="tail" transform="translate(155, 100)">
                    <path d="M 0 0 Q 15 -10 5 -20 Q -5 -25 0 -15" fill="none" stroke="#e0859a" stroke-width="4" stroke-linecap="round"/>
                </g>

                <!-- Pernas -->
                <rect x="55" y="150" width="18" height="30" rx="6" fill="#e0859a"/>
                <rect x="80" y="155" width="18" height="28" rx="6" fill="#e0859a"/>
                <rect x="108" y="155" width="18" height="28" rx="6" fill="#e0859a"/>
                <rect x="130" y="150" width="18" height="30" rx="6" fill="#e0859a"/>

                <!-- Cabeça -->
                <ellipse cx="70" cy="85" rx="42" ry="38" fill="#f4a8b8" stroke="#e0859a" stroke-width="3"/>

                <!-- Orelha esquerda -->
                <g class="ear" transform="translate(45, 55)">
                    <path d="M 0 0 Q -15 -20 -5 -25 Q 5 -20 0 0 Z" fill="#e0859a" stroke="#c97088" stroke-width="2"/>
                </g>

                <!-- Orelha direita -->
                <g class="ear" transform="translate(90, 52)" style="animation-delay: 0.3s;">
                    <path d="M 0 0 Q 15 -20 5 -25 Q -5 -20 0 0 Z" fill="#e0859a" stroke="#c97088" stroke-width="2"/>
                </g>

                <!-- Olho esquerdo -->
                <g class="eye" transform="translate(58, 78)">
                    <ellipse cx="0" cy="0" rx="5" ry="6" fill="#3d2817"/>
                    <ellipse cx="-1.5" cy="-2" rx="1.5" ry="2" fill="white"/>
                </g>

                <!-- Olho direito -->
                <g class="eye" transform="translate(82, 78)" style="animation-delay: 0.1s;">
                    <ellipse cx="0" cy="0" rx="5" ry="6" fill="#3d2817"/>
                    <ellipse cx="-1.5" cy="-2" rx="1.5" ry="2" fill="white"/>
                </g>

                <!-- Bochechas rosadas -->
                <ellipse cx="50" cy="92" rx="8" ry="5" fill="#f08ca0" opacity="0.6"/>
                <ellipse cx="90" cy="92" rx="8" ry="5" fill="#f08ca0" opacity="0.6"/>

                <!-- Focinho -->
                <g class="snout" transform="translate(70, 98)">
                    <ellipse cx="0" cy="0" rx="16" ry="12" fill="#f08ca0" stroke="#e0859a" stroke-width="2"/>
                    <ellipse cx="-5" cy="0" rx="3" ry="4" fill="#7c2d12"/>
                    <ellipse cx="5" cy="0" rx="3" ry="4" fill="#7c2d12"/>
                </g>

                <!-- Sorriso -->
                <path d="M 62 108 Q 70 114 78 108" fill="none" stroke="#7c2d12" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>

        <h1 class="title">Atualizando seu sistema</h1>
        <p class="subtitle">
            Estamos verificando e atualizando o banco de dados da sua granja.<br>
            Isso é rápido e garante que tudo funcione direitinho! 🐷
        </p>

        <div class="progress-text" id="progressText">Preparando...</div>
        <div class="progress-wrapper">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
        </div>
        <div class="current-task" id="currentTask">Iniciando verificação...</div>

        <div class="version-badge" id="versionBadge">Versão atual: {{ $firstVersion }}</div>

        <div class="error-box" id="errorBox">
            <strong>Ops! Algo deu errado:</strong>
            <div id="errorMessage"></div>
            <button class="retry-btn" onclick="startUpdate()">Tentar novamente</button>
        </div>
    </div>

    <script>
        const totalVersions = {{ $total }};
        let completed = 0;

        async function startUpdate() {
            document.getElementById('errorBox').classList.remove('show');
            completed = 0;
            updateProgress();

            try {
                while (true) {
                    const res = await fetch('{{ route('schema-update.run-next') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    });

                    if (!res.ok) {
                        throw new Error('Erro na resposta do servidor (HTTP ' + res.status + ')');
                    }

                    const data = await res.json();

                    if (!data.success) {
                        throw new Error(data.message || 'Erro ao aplicar migração.');
                    }

                    completed++;
                    updateProgress(data.version, data.remaining);

                    if (data.done) {
                        document.getElementById('currentTask').textContent = 'Tudo pronto! Redirecionando...';
                        setTimeout(() => {
                            window.location.href = '{{ route('dashboard') }}';
                        }, 1200);
                        break;
                    }
                }
            } catch (err) {
                document.getElementById('errorMessage').textContent = err.message;
                document.getElementById('errorBox').classList.add('show');
                document.getElementById('currentTask').textContent = 'Falha na atualização.';
            }
        }

        function updateProgress(version, remaining) {
            const pct = totalVersions > 0 ? Math.min(100, Math.round((completed / totalVersions) * 100)) : 100;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressText').textContent = completed + ' / ' + totalVersions + ' migrações aplicadas';

            if (version) {
                document.getElementById('currentTask').textContent = 'Aplicando versão ' + version + '...';
                document.getElementById('versionBadge').textContent = 'Versão atual: ' + version;
            }

            if (remaining !== undefined) {
                document.getElementById('currentTask').textContent = remaining > 0
                    ? 'Faltam ' + remaining + ' atualização(ões)...'
                    : 'Concluído!';
            }
        }

        // Inicia automaticamente ao carregar a página
        window.addEventListener('load', startUpdate);
    </script>
</body>
</html>
