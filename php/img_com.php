<?php
/**
 * img_com.php
 *
 * Implements server-side image compression persistence and batch ZIP download packaging services.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Define physical storage directory path for uploaded images
$uploadDir = __DIR__ . '/uploads/';

// Ensure directory exists recursively with standard access permissions
if (!is_dir($uploadDir)) {
    // Create directory
    mkdir($uploadDir, 0755, true);
}

// ==========================================
// 1. SAVE CONVERTED BASE64 FILE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_data'])) {
    // Send response header
    header('Content-Type: application/json');

    // Extract base64 image data payload
    $base64Data = $_POST['image_data'];
    // Extract sanitized original name
    $originalName = pathinfo($_POST['file_name'], PATHINFO_FILENAME);
    // Fetch requested output compression format
    $format = $_POST['format'] ?? 'original';

    // Parse base64 string parts
    list($type, $data) = explode(';', $base64Data);
    // Extract raw base64 data section
    list(, $data)      = explode(',', $data);
    // Decode base64 into raw binary content
    $decodedData = base64_decode($data);

    // Determine targeted extension
    $ext = match($format) {
        'webp' => 'webp',
        'jpeg' => 'jpg',
        'png'  => 'png',
        default => strtolower(pathinfo($_POST['file_name'], PATHINFO_EXTENSION))
    };

    // Format unique compressed filename
    $filename = $originalName . '_compressed_' . time() . '.' . $ext;
    // Format full physical filePath boundary
    $filePath = $uploadDir . $filename;

    // Persist decoded binary data on disk
    file_put_contents($filePath, $decodedData);

    // Determine active connection protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    // Capture host header
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Clean directory path
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Format fully qualified URL to load compressed image
    $fileUrl = "{$protocol}{$host}/php/uploads/{$filename}";

    // Return success response JSON payload
    echo json_encode([
        'success'   => true,
        'file_url'  => $fileUrl,
        'new_size'  => filesize($filePath)
    ]);
    // Terminate
    exit;
}

// ==========================================
// 2. BATCH ZIP DOWNLOAD HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_zip') {
    // Decode file URLs JSON parameter lists
    $filesJson = $_GET['files'] ?? '[]';
    $fileUrls = json_decode($filesJson, true) ?? [];

    // Abort if list is empty
    if (empty($fileUrls)) {
        // Stop execution
        die("No valid files to archive.");
    }

    // Instantiate ZipArchive utility
    $zip = new ZipArchive();
    // Format temporary batch zip filename
    $zipFilename = $uploadDir . 'batch_compressed_' . time() . '.zip';

    // Open target ZIP archive creation
    if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Iterate through requested file URLs
        foreach ($fileUrls as $url) {
            // Extract basename
            $baseName = basename($url);
            // Resolve local path
            $localPath = $uploadDir . $baseName;
            // Add file to ZIP archive if it exists on disk
            if (file_exists($localPath)) {
                // Insert file into ZIP
                $zip->addFile($localPath, $baseName);
            }
        }
        // Save ZIP archive
        $zip->close();

        // Send streaming attachment download headers
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="compressed_images.zip"');
        header('Content-Length: ' . filesize($zipFilename));
        // Read file contents to browser
        readfile($zipFilename);

        // Delete temporary zip file from server
        unlink($zipFilename);
        // Terminate
        exit;
    }
}
?>
