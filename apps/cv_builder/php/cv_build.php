<?php
declare(strict_types=1);

/**
 * Server-side fallback for generating and serving PDF attachments.
 * Expects POST data with raw rendered HTML content.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resume_html'])) {
    $htmlContent = $_POST['resume_html'];
    $candidateName = preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['candidate_name'] ?? 'Resume');

    // Headers for streaming printable document
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"{$candidateName}_CV.html\"");

    // Output clean document container for local printing
    echo "<!DOCTYPE html><html><head><title>Resume Export</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body class='bg-white p-5'>";
    echo $htmlContent;
    echo "</body></html>";
    exit;
}