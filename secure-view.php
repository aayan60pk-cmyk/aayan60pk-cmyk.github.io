<?php
// Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('DATA_FILE', __DIR__ . '/data/files.json');

// Get file ID from URL
$fileId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$fileId) {
    showError('Invalid link');
    exit;
}

// Load file data
if (!file_exists(DATA_FILE)) {
    showError('File not found');
    exit;
}

$json = file_get_contents(DATA_FILE);
$allFiles = json_decode($json, true) ?: [];

if (!isset($allFiles[$fileId])) {
    showError('File not found');
    exit;
}

$fileData = $allFiles[$fileId];

// Check if expired
if ($fileData['expiryTime'] < time()) {
    // Delete expired file
    $filePath = UPLOAD_DIR . $fileData['storedName'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Remove from data
    unset($allFiles[$fileId]);
    file_put_contents(DATA_FILE, json_encode($allFiles, JSON_PRETTY_PRINT));
    
    showError('This file has expired and is no longer available');
    exit;
}

// Update view count
$fileData['views']++;
$allFiles[$fileId] = $fileData;
file_put_contents(DATA_FILE, json_encode($allFiles, JSON_PRETTY_PRINT));

// File path
$filePath = UPLOAD_DIR . $fileData['storedName'];

if (!file_exists($filePath)) {
    showError('File not found on server');
    exit;
}

// Get file content based on type
$fileContent = '';
$fileType = strtolower(pathinfo($fileData['storedName'], PATHINFO_EXTENSION));

if ($fileType === 'pdf') {
    $fileContent = base64_encode(file_get_contents($filePath));
} elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
    $fileContent = base64_encode(file_get_contents($filePath));
} elseif (in_array($fileType, ['txt', 'doc', 'docx'])) {
    if ($fileType === 'txt') {
        $fileContent = file_get_contents($filePath);
    } else {
        $fileContent = '[Document preview not available. Content is protected from download.]';
    }
}

// Calculate time remaining
$timeRemaining = $fileData['expiryTime'] - time();
$expiryText = '';
if ($timeRemaining < 60) {
    $expiryText = $timeRemaining . ' seconds';
} elseif ($timeRemaining < 3600) {
    $expiryText = floor($timeRemaining / 60) . ' minutes';
} else {
    $expiryText = floor($timeRemaining / 3600) . ' hours';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($fileData['filename']); ?> - SecureShare</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
    
    <?php if ($fileData['protectScreenshot']): ?>
    <!-- Screenshot protection meta tags -->
    <meta name="screenshot" content="disabled">
    <meta name="screen-capture" content="disabled">
    <?php endif; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            <?php if ($fileData['protectCopy']): ?>
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            <?php endif; ?>
        }
        
        body {
            font-family: 'Space Mono', monospace;
            background: #0a0e27;
            color: #e8e8e8;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        <?php if ($fileData['protectCopy']): ?>
        body {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        <?php endif; ?>

        .protection-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #ff4466;
            font-size: 2rem;
            text-align: center;
            padding: 20px;
        }

        .protection-overlay.show {
            display: flex;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #2a2f4a;
            padding: 15px 20px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .file-name {
            font-family: 'Syne', sans-serif;
            color: #00ff88;
            font-size: 1.1rem;
            word-break: break-all;
        }

        .expiry-badge {
            background: rgba(255, 170, 0, 0.2);
            border: 2px solid #ffaa00;
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 0.85rem;
            color: #ffaa00;
        }

        .viewer-container {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
            padding: 20px;
        }

        .content-wrapper {
            max-width: 1200px;
            width: 100%;
            height: 100%;
            position: relative;
        }

        <?php if ($fileData['watermark']): ?>
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(0, 255, 136, 0.05);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            pointer-events: none;
            z-index: 100;
            white-space: nowrap;
            animation: watermarkPulse 3s ease-in-out infinite;
        }

        @keyframes watermarkPulse {
            0%, 100% { opacity: 0.05; }
            50% { opacity: 0.08; }
        }

        .watermark-small {
            position: fixed;
            font-size: 1rem;
            color: rgba(0, 255, 136, 0.3);
            font-family: 'Space Mono', monospace;
            pointer-events: none;
            z-index: 101;
            animation: watermarkMove 20s linear infinite;
        }

        @keyframes watermarkMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100vw, 100vh); }
        }
        <?php endif; ?>

        .pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
            border-radius: 8px;
        }

        .image-viewer {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
            <?php if ($fileData['protectDownload']): ?>
            pointer-events: none;
            <?php endif; ?>
        }

        .text-viewer {
            background: #1a1f3a;
            border: 2px solid #2a2f4a;
            border-radius: 8px;
            padding: 30px;
            color: #e8e8e8;
            font-family: 'Space Mono', monospace;
            white-space: pre-wrap;
            overflow: auto;
            max-height: calc(100vh - 150px);
            line-height: 1.8;
        }

        .protection-notice {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(26, 31, 58, 0.95);
            border: 2px solid #00ff88;
            border-radius: 8px;
            padding: 15px;
            font-size: 0.85rem;
            color: #8b92b8;
            max-width: 300px;
            z-index: 1001;
        }

        .shield-icon {
            color: #00ff88;
            font-size: 1.2rem;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .header {
                font-size: 0.9rem;
            }
            
            .file-name {
                font-size: 1rem;
            }
            
            .watermark {
                font-size: 4rem;
            }

            .viewer-container {
                top: 90px;
            }
        }
    </style>
</head>
<body>
    <div class="protection-overlay" id="protectionOverlay">
        <div>🚫 SCREENSHOT BLOCKED</div>
        <div style="font-size: 1rem; margin-top: 20px;">This content is protected and cannot be captured</div>
    </div>

    <div class="header">
        <div class="file-name">🔒 <?php echo htmlspecialchars($fileData['filename']); ?></div>
        <div class="expiry-badge">⏰ Auto-deletes in <?php echo $expiryText; ?></div>
    </div>

    <?php if ($fileData['watermark']): ?>
    <div class="watermark">PROTECTED</div>
    <?php 
    // Generate random positioned watermarks
    for ($i = 0; $i < 5; $i++) {
        $top = rand(10, 90);
        $left = rand(10, 90);
        echo "<div class='watermark-small' style='top: {$top}%; left: {$left}%;'>SECURE VIEW</div>";
    }
    ?>
    <?php endif; ?>

    <div class="viewer-container">
        <div class="content-wrapper">
            <?php if ($fileType === 'pdf'): ?>
                <iframe id="pdfViewer" class="pdf-viewer"></iframe>
            <?php elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <img src="data:image/<?php echo $fileType; ?>;base64,<?php echo $fileContent; ?>" 
                     alt="<?php echo htmlspecialchars($fileData['filename']); ?>" 
                     class="image-viewer"
                     id="imageViewer">
            <?php else: ?>
                <div class="text-viewer"><?php echo htmlspecialchars($fileContent); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="protection-notice">
        <span class="shield-icon">🛡️</span>
        <strong>Protected Content</strong><br>
        <?php if ($fileData['protectScreenshot']): ?>• Screenshots blocked<br><?php endif; ?>
        <?php if ($fileData['protectDownload']): ?>• Download disabled<br><?php endif; ?>
        <?php if ($fileData['protectCopy']): ?>• Copy disabled<br><?php endif; ?>
        Views: <?php echo $fileData['views']; ?>
    </div>

    <script>
        <?php if ($fileData['protectCopy']): ?>
        // Disable right-click
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showProtectionWarning();
        });

        // Disable keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+S, Ctrl+C, Ctrl+P, Ctrl+A, Print Screen
            if ((e.ctrlKey && (e.key === 's' || e.key === 'c' || e.key === 'p' || e.key === 'a')) || 
                e.key === 'PrintScreen' || e.key === 'F12') {
                e.preventDefault();
                showProtectionWarning();
            }
        });

        // Disable text selection
        document.onselectstart = () => false;
        <?php endif; ?>

        <?php if ($fileData['protectScreenshot']): ?>
        // Detect screenshot attempts
        document.addEventListener('keyup', (e) => {
            if (e.key === 'PrintScreen') {
                showProtectionWarning();
                navigator.clipboard.writeText(''); // Clear clipboard
            }
        });

        // Detect dev tools
        let devtoolsOpen = false;
        const threshold = 160;
        setInterval(() => {
            if (window.outerWidth - window.innerWidth > threshold || 
                window.outerHeight - window.innerHeight > threshold) {
                if (!devtoolsOpen) {
                    devtoolsOpen = true;
                    showProtectionWarning();
                }
            } else {
                devtoolsOpen = false;
            }
        }, 1000);

        // Blur detection (tab switching, screen capture apps)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                document.body.style.filter = 'blur(20px)';
            } else {
                document.body.style.filter = 'none';
            }
        });

        window.addEventListener('blur', () => {
            document.body.style.filter = 'blur(20px)';
        });

        window.addEventListener('focus', () => {
            document.body.style.filter = 'none';
        });
        <?php endif; ?>

        <?php if ($fileData['protectDownload'] && in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])): ?>
        // Prevent image dragging
        document.getElementById('imageViewer').addEventListener('dragstart', (e) => {
            e.preventDefault();
            showProtectionWarning();
        });
        <?php endif; ?>

        <?php if ($fileType === 'pdf'): ?>
        // Load PDF in iframe with protection
        const pdfData = 'data:application/pdf;base64,<?php echo $fileContent; ?>';
        const iframe = document.getElementById('pdfViewer');
        iframe.src = pdfData;
        
        // Prevent PDF download attempts
        iframe.addEventListener('load', () => {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                <?php if ($fileData['protectDownload']): ?>
                iframeDoc.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    showProtectionWarning();
                });
                <?php endif; ?>
            } catch(e) {
                console.log('PDF loaded with browser protection');
            }
        });
        <?php endif; ?>

        function showProtectionWarning() {
            const overlay = document.getElementById('protectionOverlay');
            overlay.classList.add('show');
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 2000);
        }

        // Auto-redirect on expiry
        const expiryTime = <?php echo $fileData['expiryTime']; ?> * 1000;
        const checkExpiry = setInterval(() => {
            if (Date.now() >= expiryTime) {
                clearInterval(checkExpiry);
                alert('This file has expired and will be deleted.');
                window.location.href = 'index.html';
            }
        }, 5000);

        // Watermark animation
        <?php if ($fileData['watermark']): ?>
        setInterval(() => {
            const watermarks = document.querySelectorAll('.watermark-small');
            watermarks.forEach(wm => {
                wm.style.top = Math.random() * 90 + '%';
                wm.style.left = Math.random() * 90 + '%';
            });
        }, 10000);
        <?php endif; ?>

        console.log('%c🛡️ PROTECTED CONTENT', 'color: #00ff88; font-size: 20px; font-weight: bold;');
        console.log('%cThis content is protected. Unauthorized access, screenshots, or downloads are prevented.', 'color: #ffaa00; font-size: 12px;');
    </script>
</body>
</html>

<?php
function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SecureShare - Error</title>
        <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Space Mono', monospace;
                background: #0a0e27;
                color: #e8e8e8;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                max-width: 500px;
                text-align: center;
                background: #1a1f3a;
                border: 2px solid #ff4466;
                border-radius: 16px;
                padding: 40px;
            }
            .error-icon { font-size: 4rem; margin-bottom: 20px; }
            h1 {
                font-family: 'Syne', sans-serif;
                font-size: 2rem;
                color: #ff4466;
                margin-bottom: 15px;
            }
            p {
                color: #8b92b8;
                font-size: 1rem;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .btn {
                background: linear-gradient(135deg, #00ff88, #00d4ff);
                color: #0a0e27;
                border: none;
                padding: 12px 30px;
                border-radius: 8px;
                font-family: 'Syne', sans-serif;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: transform 0.3s ease;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🔒</div>
            <h1>Content Unavailable</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="index.html" class="btn">Upload New File</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
