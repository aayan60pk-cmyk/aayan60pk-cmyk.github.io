<?php
header('Content-Type: application/json');

// Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('DATA_FILE', __DIR__ . '/data/files.json');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Clean expired files
cleanExpiredFiles();

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $file = $_FILES['file'];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File too large. Maximum size is 100MB');
        }

        // Validate file type
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Generate unique file ID
        $fileId = bin2hex(random_bytes(16));
        $originalName = basename($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = $fileId . '.' . $extension;
        $storedPath = UPLOAD_DIR . $storedName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new Exception('Failed to save file');
        }

        // Get expiry time and protection settings
        $expirySeconds = isset($_POST['expiry']) ? (int)$_POST['expiry'] : 3600;
        $expiryTime = time() + $expirySeconds;

        $protectScreenshot = isset($_POST['protectScreenshot']) && $_POST['protectScreenshot'] === '1';
        $protectDownload = isset($_POST['protectDownload']) && $_POST['protectDownload'] === '1';
        $protectCopy = isset($_POST['protectCopy']) && $_POST['protectCopy'] === '1';
        $watermark = isset($_POST['watermark']) && $_POST['watermark'] === '1';

        // Store file metadata
        $fileData = [
            'fileId' => $fileId,
            'filename' => $originalName,
            'storedName' => $storedName,
            'mimeType' => $mimeType,
            'size' => $file['size'],
            'uploadTime' => time(),
            'expiryTime' => $expiryTime,
            'views' => 0,
            'protectScreenshot' => $protectScreenshot,
            'protectDownload' => $protectDownload,
            'protectCopy' => $protectCopy,
            'watermark' => $watermark
        ];

        saveFileData($fileData);

        // Return success response
        echo json_encode([
            'success' => true,
            'fileId' => $fileId,
            'filename' => $originalName,
            'size' => $file['size'],
            'expiryTime' => $expiryTime,
            'protectScreenshot' => $protectScreenshot,
            'protectDownload' => $protectDownload,
            'protectCopy' => $protectCopy,
            'watermark' => $watermark
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

function saveFileData($fileData) {
    $allFiles = [];
    
    if (file_exists(DATA_FILE)) {
        $json = file_get_contents(DATA_FILE);
        $allFiles = json_decode($json, true) ?: [];
    }
    
    $allFiles[$fileData['fileId']] = $fileData;
    
    file_put_contents(DATA_FILE, json_encode($allFiles, JSON_PRETTY_PRINT));
}

function cleanExpiredFiles() {
    if (!file_exists(DATA_FILE)) {
        return;
    }
    
    $json = file_get_contents(DATA_FILE);
    $allFiles = json_decode($json, true) ?: [];
    $currentTime = time();
    $cleaned = false;
    
    foreach ($allFiles as $fileId => $fileData) {
        if ($fileData['expiryTime'] < $currentTime) {
            // Delete physical file
            $filePath = UPLOAD_DIR . $fileData['storedName'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Remove from data
            unset($allFiles[$fileId]);
            $cleaned = true;
        }
    }
    
    if ($cleaned) {
        file_put_contents(DATA_FILE, json_encode($allFiles, JSON_PRETTY_PRINT));
    }
}
?>
