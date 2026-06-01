<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Sistem</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #434343 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-icon {
            width: 180px;
            height: 180px;
            margin: 0 auto 30px;
        }
        h1 {
            font-size: 72px;
            color: #434343;
            margin-bottom: 10px;
            font-weight: 700;
        }
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #434343 0%, #2c3e50 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 67, 67, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 67, 67, 0.6);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        .gear-animation {
            animation: rotate 3s linear infinite;
            transform-origin: center;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spark {
            animation: flicker 0.5s ease-in-out infinite alternate;
        }
        @keyframes flicker {
            from { opacity: 0.3; }
            to { opacity: 1; }
        }
        .tech-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #666;
        }
        .tech-info code {
            display: block;
            word-break: break-all;
        }
        @media (max-width: 480px) {
            h1 { font-size: 56px; }
            h2 { font-size: 20px; }
            .error-container { padding: 40px 20px; }
            .error-icon { width: 140px; height: 140px; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <svg class="error-icon" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#434343;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#2c3e50;stop-opacity:1" />
                </linearGradient>
            </defs>
            <!-- Background Circle -->
            <circle cx="100" cy="100" r="90" fill="#e8e8e8"/>
            <!-- Main Gear -->
            <g class="gear-animation" transform="translate(100, 100)">
                <circle cx="0" cy="0" r="50" fill="none" stroke="url(#grad1)" stroke-width="8"/>
                <circle cx="0" cy="0" r="20" fill="url(#grad1)"/>
                <!-- Gear Teeth -->
                <rect x="-8" y="-58" width="16" height="12" fill="url(#grad1)"/>
                <rect x="-8" y="46" width="16" height="12" fill="url(#grad1)"/>
                <rect x="-58" y="-8" width="12" height="16" fill="url(#grad1)"/>
                <rect x="46" y="-8" width="12" height="16" fill="url(#grad1)"/>
                <rect x="-45" y="-45" width="12" height="12" fill="url(#grad1)" transform="rotate(45)"/>
                <rect x="33" y="33" width="12" height="12" fill="url(#grad1)" transform="rotate(45)"/>
                <rect x="-45" y="33" width="12" height="12" fill="url(#grad1)" transform="rotate(-45)"/>
                <rect x="33" y="-45" width="12" height="12" fill="url(#grad1)" transform="rotate(-45)"/>
            </g>
            <!-- Small broken gear -->
            <g transform="translate(150, 60)">
                <circle cx="0" cy="0" r="20" fill="none" stroke="#e74c3c" stroke-width="4"/>
                <line x1="-15" y1="-15" x2="15" y2="15" stroke="#e74c3c" stroke-width="3" stroke-linecap="round"/>
                <line x1="15" y1="-15" x2="-15" y2="15" stroke="#e74c3c" stroke-width="3" stroke-linecap="round"/>
            </g>
            <!-- Sparks -->
            <circle class="spark" cx="60" cy="50" r="3" fill="#f39c12"/>
            <circle class="spark" cx="140" cy="140" r="2" fill="#e74c3c" style="animation-delay: 0.2s"/>
            <circle class="spark" cx="50" cy="130" r="2.5" fill="#f1c40f" style="animation-delay: 0.4s"/>
        </svg>
        
        <h1>500</h1>
        <h2>Kesalahan Sistem</h2>
        <p>Terjadi kesalahan pada server kami. Tim teknis telah diberitahu dan sedang bekerja untuk memperbaikinya.</p>
        
        <div class="btn-group">
            <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
            <button onclick="location.reload()" class="btn btn-secondary">Coba Lagi</button>
        </div>
        
        <?php if (ENVIRONMENT === 'development' && isset($exception)): ?>
        <div class="tech-info">
            <strong>Error Details:</strong><br>
            <code><?php echo htmlspecialchars($exception->getMessage()); ?></code><br>
            <code>File: <?php echo $exception->getFile(); ?>:<?php echo $exception->getLine(); ?></code>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
