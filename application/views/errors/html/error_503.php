<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Unavailable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
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
            color: #f12711;
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
            background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(241, 39, 17, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(241, 39, 17, 0.6);
        }
        .gear-animation {
            animation: rotate 3s linear infinite;
            transform-origin: center;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .maintenance-badge {
            background: #fff3cd;
            color: #856404;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 20px;
            font-size: 14px;
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
                    <stop offset="0%" style="stop-color:#f5af19;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#f12711;stop-opacity:1" />
                </linearGradient>
            </defs>
            <!-- Background Circle -->
            <circle cx="100" cy="100" r="90" fill="#fff3cd"/>
            <!-- Wrench Icon -->
            <g transform="translate(100, 100)">
                <!-- Wrench Handle -->
                <rect x="-10" y="20" width="20" height="60" rx="5" fill="url(#grad1)" transform="rotate(45)"/>
                <!-- Wrench Head -->
                <circle cx="0" cy="10" r="25" fill="none" stroke="url(#grad1)" stroke-width="8"/>
                <circle cx="0" cy="10" r="12" fill="url(#grad1)"/>
            </g>
            <!-- Gear -->
            <g class="gear-animation" transform="translate(150, 50)">
                <circle cx="0" cy="0" r="20" fill="none" stroke="#f12711" stroke-width="4"/>
                <circle cx="0" cy="0" r="8" fill="#f12711"/>
                <rect x="-4" y="-24" width="8" height="6" fill="#f12711"/>
                <rect x="-4" y="18" width="8" height="6" fill="#f12711"/>
                <rect x="-24" y="-4" width="6" height="8" fill="#f12711"/>
                <rect x="18" y="-4" width="6" height="8" fill="#f12711"/>
            </g>
            <!-- Sparkles -->
            <circle cx="50" cy="50" r="3" fill="#f5af19">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite"/>
            </circle>
            <circle cx="160" cy="140" r="2" fill="#f12711">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="1.5s" repeatCount="indefinite"/>
            </circle>
        </svg>
        
        <?php if (isset($maintenance) && $maintenance): ?>
            <h1>🔧 Maintenance</h1>
            <h2>Sedang Dalam Perawatan</h2>
            <p>Kami sedang melakukan perawatan sistem untuk meningkatkan layanan.<br>Silakan coba lagi dalam beberapa saat.</p>
        <?php else: ?>
            <h1>503</h1>
            <h2>Service Unavailable</h2>
            <p>Layanan sedang tidak tersedia. Tim teknis sedang bekerja untuk memperbaikinya.</p>
        <?php endif; ?>
        
        <button onclick="location.reload()" class="btn">Coba Lagi</button>
        
        <?php if (isset($maintenance) && $maintenance): ?>
            <div class="maintenance-badge">
                ⏱️ Estimasi selesai: Segera
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
