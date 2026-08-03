<?php
declare(strict_types=1);

$jsonFile = __DIR__ . '/links.json';

// Helper: Read JSON database file
function getLinks(string $file): array {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Helper: Save JSON database file
function saveLinks(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Helper: Generate random 6-character code
function generateCode(int $length = 6): string {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// ==========================================
// 1. REDIRECT HANDLER (GET request with ?c=)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['c'])) {
    $code = trim($_GET['c']);
    $links = getLinks($jsonFile);

    if (array_key_exists($code, $links)) {
        header("Location: " . $links[$code], true, 301);
        exit;
    } else {
        http_response_code(404);
        echo "<h3>404 - Short link not found!</h3>";
        exit;
    }
}

// ==========================================
// 2. CREATION HANDLER (POST request from form)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['long_url'])) {
    $longUrl = filter_var(trim($_POST['long_url']), FILTER_VALIDATE_URL);

    if (!$longUrl) {
        die("Invalid URL provided.");
    }

    $links = getLinks($jsonFile);

    // Check if URL already exists to avoid duplicate codes
    $existingCode = array_search($longUrl, $links, true);

    if ($existingCode !== false) {
        $code = $existingCode;
    } else {
        // Generate new unique code
        do {
            $code = generateCode(6);
        } while (array_key_exists($code, $links));

        $links[$code] = $longUrl;
        saveLinks($jsonFile, $links);
    }

    // Redirect back to frontend with generated code
    header("Location: index.html?code=" . urlencode($code));
    exit;
}