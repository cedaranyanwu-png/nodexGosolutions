<?php
declare(strict_types=1);

$uploadDir = __DIR__ . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ==========================================
// 1. SAVE CONVERTED BASE64 FILE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_data'])) {
    header('Content-Type: application/json');

    $base64Data = $_POST['image_data'];
    $originalName = pathinfo($_POST['file_name'], PATHINFO_FILENAME);
    $format = $_POST['format'] ?? 'original';

    // Parse base64 string
    list($type, $data) = explode(';', $base64Data);
    list(, $data)      = explode(',', $data);
    $decodedData = base64_decode($data);

    // Determine File Extension
    $ext = match($format) {
        'webp' => 'webp',
        'jpeg' => 'jpg',
        'png'  => 'png',
        default => strtolower(pathinfo($_POST['file_name'], PATHINFO_EXTENSION))
    };

    $filename = $originalName . '_compressed_' . time() . '.' . $ext;
    $filePath = $uploadDir . $filename;

    file_put_contents($filePath, $decodedData);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $fileUrl = "{$protocol}{$host}{$scriptDir}/uploads/{$filename}";

    echo json_encode([
        'success'   => true,
        'file_url'  => $fileUrl,
        'new_size'  => filesize($filePath)
    ]);
    exit;
}

// ==========================================
// 2. BATCH ZIP DOWNLOAD HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_zip') {
    $filesJson = $_GET['files'] ?? '[]';
    $fileUrls = json_decode($filesJson, true) ?? [];

    if (empty($fileUrls)) {
        die("No valid files to archive.");
    }

    $zip = new ZipArchive();
    $zipFilename = $uploadDir . 'batch_compressed_' . time() . '.zip';

    if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($fileUrls as $url) {
            $baseName = basename($url);
            $localPath = $uploadDir . $baseName;
            if (file_exists($localPath)) {
                $zip->addFile($localPath, $baseName);
            }
        }
        $zip->close();

        // Serve ZIP for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="compressed_images.zip"');
        header('Content-Length: ' . filesize($zipFilename));
        readfile($zipFilename);

        // Cleanup temp zip file
        unlink($zipFilename);
        exit;
    }
}