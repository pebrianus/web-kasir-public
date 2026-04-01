<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #edeae4;
            --surface:  #e4e0d9;
            --border:   #cdc9c1;
            --text-dim: #8a8580;
            --text:     #3a3830;
            --accent:   #5c6b52;
            --accent-h: #4a5641;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background-color: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            overflow: hidden;
        }

        /* subtle grid texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 48px 48px;
            opacity: 0.35;
            pointer-events: none;
        }

        .card {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 56px 64px;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow:
                0 1px 2px rgba(0,0,0,0.06),
                0 4px 16px rgba(0,0,0,0.07);
            animation: rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge {
            display: inline-block;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-dim);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 4px 14px;
            margin-bottom: 28px;
        }

        .code {
            font-family: 'DM Serif Display', serif;
            font-size: 96px;
            line-height: 1;
            color: var(--text);
            letter-spacing: -2px;
            margin-bottom: 12px;
            opacity: 0;
            animation: rise 0.5s 0.1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .divider {
            width: 40px;
            height: 2px;
            background: var(--border);
            border-radius: 2px;
            margin: 0 auto 20px;
        }

        .message {
            font-size: 15px;
            color: var(--text-dim);
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 22px;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.18s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: #f5f2ed;
        }
        .btn-primary:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(92,107,82,0.25);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-dim);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            background: var(--bg);
            color: var(--text);
            border-color: #b5b1aa;
        }

        .arrow { font-size: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">Error 403</div>
        <div class="code">403</div>
        <div class="divider"></div>
        <p class="message">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.' }}
        </p>
        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-primary">
                <span class="arrow">←</span> Kembali
            </a>
            <a href="{{ url('/') }}" class="btn btn-ghost">
                Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
