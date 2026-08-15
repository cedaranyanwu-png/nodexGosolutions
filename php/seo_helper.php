<?php
/**
 * php/seo_helper.php
 *
 * Dynamic Schema.org & JSON-LD Generator Engine.
 * Inspects page context, constructs exact Schema types (Person, Organization, WebSite,
 * ImageGallery, ItemList, BreadcrumbList, WebPage, etc.), converts relative image URLs to
 * absolute canonical URLs, and protects private/admin pages from public SEO exposure.
 */

declare(strict_types=1);

/**
 * Converts any relative path into a fully qualified absolute URL.
 *
 * @param string $path Relative path or existing absolute URL.
 * @param string $baseUrl Base domain URL.
 * @return string Canonical absolute URL string.
 */
function toAbsoluteUrl(string $path, string $baseUrl = 'https://nodexplatform.com.ng'): string {
    $path = trim($path);
    if (empty($path)) {
        return $baseUrl;
    }
    // Remove cache-busting query strings for canonical schema URLs
    $cleanPath = explode('?', $path)[0];

    if (str_starts_with($cleanPath, 'http://') || str_starts_with($cleanPath, 'https://')) {
        return $cleanPath;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($cleanPath, '/');
}

/**
 * Determines whether a given request URI is public and indexable for SEO structured data.
 * Returns false for private, admin, dashboard, authentication, or API routes.
 *
 * @param string $requestUri Target route URI path.
 * @return bool True if public and indexable, false if private/admin.
 */
function isPublicSeoAllowed(string $requestUri = ''): bool {
    if (empty($requestUri) && isset($_SERVER['REQUEST_URI'])) {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    }

    $path = strtolower(trim($requestUri));

    // Private / Admin / System path prefixes
    $privatePrefixes = [
        '/admin',
        '/user',
        '/login',
        '/register',
        '/verify',
        '/profile',
        '/php',
        '/cms/admin',
        '/cms/save_page'
    ];

    foreach ($privatePrefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return false;
        }
    }

    return true;
}

/**
 * Normalizes an image parameter (string or array) into an array of absolute image URLs.
 *
 * @param string|array $images Image URL(s).
 * @param string $baseUrl Base site URL.
 * @return array Array of absolute image URLs.
 */
function normalizeImageUrls(string|array $images, string $baseUrl = 'https://nodexplatform.com.ng'): array {
    $rawList = is_array($images) ? $images : [$images];
    $normalized = [];

    foreach ($rawList as $img) {
        if (!is_string($img) || trim($img) === '') {
            continue;
        }
        $abs = toAbsoluteUrl($img, $baseUrl);
        if (!in_array($abs, $normalized, true)) {
            $normalized[] = $abs;
        }
    }

    return $normalized;
}

/**
 * Extracts all <img> tag src attributes from raw HTML/PHP content.
 *
 * @param string $html Content body.
 * @param string $baseUrl Base site URL.
 * @return array Array of unique absolute image URLs.
 */
function extractPageImagesFromHtml(string $html, string $baseUrl = 'https://nodexplatform.com.ng'): array {
    if (empty($html)) {
        return [];
    }

    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);
    $found = $matches[1] ?? [];

    return normalizeImageUrls($found, $baseUrl);
}

/**
 * Builds Person JSON-LD Schema array structure.
 */
function getPersonSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $images = normalizeImageUrls($params['image'] ?? [$baseUrl . '/main/assets/images/cedar-anyanwu.jpg'], $baseUrl);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $params['name'] ?? 'Cedar Anyanwu',
        'url' => toAbsoluteUrl($params['url'] ?? $baseUrl, $baseUrl),
        'image' => count($images) === 1 ? $images[0] : $images,
        'jobTitle' => $params['jobTitle'] ?? 'Chief Executive Officer',
        'worksFor' => [
            '@type' => 'Organization',
            'name' => $params['organization'] ?? 'Nodexplatform'
        ],
        'sameAs' => $params['sameAs'] ?? [
            'https://linkedin.com'
        ]
    ];
}

/**
 * Builds Organization JSON-LD Schema array structure.
 */
function getOrganizationSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $images = normalizeImageUrls($params['image'] ?? [
        '/main/assets/images/logo.png',
        '/main/assets/images/about-banner-1.jpg',
        '/main/assets/images/about-banner-2.jpg'
    ], $baseUrl);

    $founder = getPersonSchema($params['person_params'] ?? []);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $params['name'] ?? 'nodexGosolutions',
        'url' => toAbsoluteUrl($params['url'] ?? $baseUrl, $baseUrl),
        'logo' => toAbsoluteUrl('/main/assets/images/logo.png', $baseUrl),
        'image' => count($images) === 1 ? $images[0] : $images,
        'description' => $params['description'] ?? 'nodexGosolutions deploys high-speed modular web frameworks, maps satellite telemetry data, and powers modern creative entertainment platforms.',
        'founder' => $founder,
        'sameAs' => $params['sameAs'] ?? [
            'https://linkedin.com',
            'https://twitter.com'
        ]
    ];
}

/**
 * Builds WebSite JSON-LD Schema array structure.
 */
function getWebSiteSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $orgSchema = getOrganizationSchema($params);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $params['title'] ?? 'nodexGosolutions | Bridging Earth & Space Tech',
        'url' => toAbsoluteUrl($params['url'] ?? $baseUrl, $baseUrl),
        'publisher' => $orgSchema
    ];
}

/**
 * Builds ImageGallery JSON-LD Schema array structure.
 */
function getImageGallerySchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $images = normalizeImageUrls($params['image'] ?? [], $baseUrl);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'ImageGallery',
        'name' => $params['title'] ?? 'Corporate Gallery | nodexGosolutions',
        'description' => $params['description'] ?? 'Visual highlights of our space telemetry downlinks, agritech spatial maps, and platform deployments.',
        'url' => toAbsoluteUrl($params['url'] ?? ($baseUrl . '/gallary'), $baseUrl),
        'image' => $images
    ];
}

/**
 * Builds ItemList JSON-LD Schema array structure.
 */
function getItemListSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $items = $params['items'] ?? [
        ['name' => 'Modular CMS Router', 'description' => 'A framework-free single-entry routing engine enabling users to instantly deploy subdomains.'],
        ['name' => 'GIS Imagery Processing', 'description' => 'Downlinking and processing raw satellite land telemetry feeds for agritech cooperatives.']
    ];

    $listElements = [];
    $pos = 1;
    foreach ($items as $item) {
        $listElements[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $item['name'] ?? 'Deployment Item',
            'description' => $item['description'] ?? ''
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $params['title'] ?? 'Executive Portfolio | nodexGosolutions',
        'itemListElement' => $listElements
    ];
}

/**
 * Builds BreadcrumbList JSON-LD Schema array structure.
 */
function getBreadcrumbListSchema(array $breadcrumbs = [], string $baseUrl = 'https://nodexplatform.com.ng'): array {
    $list = [];
    $pos = 1;

    // Default Home breadcrumb
    $list[] = [
        '@type' => 'ListItem',
        'position' => $pos++,
        'name' => 'Home',
        'item' => toAbsoluteUrl('/', $baseUrl)
    ];

    foreach ($breadcrumbs as $crumb) {
        if (empty($crumb['name'])) continue;
        $list[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $crumb['name'],
            'item' => toAbsoluteUrl($crumb['url'] ?? '/', $baseUrl)
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list
    ];
}

/**
 * Builds generic WebPage JSON-LD Schema array structure.
 */
function getWebPageSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    $images = normalizeImageUrls($params['image'] ?? [$baseUrl . '/main/assets/images/logo.png'], $baseUrl);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $params['title'] ?? 'nodexGosolutions',
        'description' => $params['description'] ?? '',
        'url' => toAbsoluteUrl($params['url'] ?? $baseUrl, $baseUrl),
        'image' => count($images) === 1 ? $images[0] : $images
    ];
}

/**
 * Main SEO Head Generator.
 * Dynamically determines schema type, validates JSON payload, canonicalizes image URLs,
 * builds BreadcrumbList schema, and respects private route boundaries.
 */
function renderSeoHead(array $options = []): string {
    $requestUri = $options['request_uri'] ?? ($_SERVER['REQUEST_URI'] ?? '/');

    // Private / Admin / System pages check: output noindex and block public SEO schema
    if (!isPublicSeoAllowed($requestUri)) {
        return '<meta name="robots" content="noindex, nofollow">';
    }

    $siteUrl = $options['site_url'] ?? 'https://nodexplatform.com.ng';
    $title = $options['title'] ?? 'nodexGosolutions | Bridging Earth & Space Tech';
    $description = $options['description'] ?? 'nodexGosolutions deploys high-speed modular web frameworks, maps satellite telemetry data, and powers modern creative entertainment platforms.';
    $pageUrl = toAbsoluteUrl($options['url'] ?? $requestUri, $siteUrl);

    $ogTitle = $options['og_title'] ?? $title;
    $ogDescription = $options['og_description'] ?? $description;

    // Extract or normalize image URLs
    $pageImages = [];
    if (!empty($options['images'])) {
        $pageImages = normalizeImageUrls($options['images'], $siteUrl);
    } elseif (!empty($options['og_image'])) {
        $pageImages = normalizeImageUrls($options['og_image'], $siteUrl);
    } else {
        $pageImages = normalizeImageUrls(['/main/assets/images/cedar-anyanwu.jpg', '/main/assets/images/logo.png'], $siteUrl);
    }

    $ogImagePrimary = $pageImages[0] ?? toAbsoluteUrl('/main/assets/images/cedar-anyanwu.jpg', $siteUrl);
    $ogType = $options['og_type'] ?? 'website';

    // Build core schema payload based on explicit or inferred schema_type
    $schemaData = $options['schema_data'] ?? null;
    if ($schemaData === null) {
        $schemaType = strtolower((string)($options['schema_type'] ?? 'webpage'));
        $options['site_url'] = $siteUrl;
        $options['image'] = $pageImages;

        switch ($schemaType) {
            case 'person':
            case 'profile':
                $schemaData = getPersonSchema($options);
                break;
            case 'organization':
            case 'company':
                $schemaData = getOrganizationSchema($options);
                break;
            case 'website':
                $schemaData = getWebSiteSchema($options);
                break;
            case 'imagegallery':
            case 'gallery':
                $schemaData = getImageGallerySchema($options);
                break;
            case 'itemlist':
            case 'portfolio':
                $schemaData = getItemListSchema($options);
                break;
            default:
                $schemaData = getWebPageSchema($options);
                break;
        }
    }

    // Build BreadcrumbList schema if breadcrumbs provided or derived
    $breadcrumbs = $options['breadcrumbs'] ?? [];
    $breadcrumbSchema = !empty($breadcrumbs) ? getBreadcrumbListSchema($breadcrumbs, $siteUrl) : null;

    // Assemble final JSON-LD structures
    $schemasToOutput = [];
    if (!empty($schemaData)) {
        $schemasToOutput[] = $schemaData;
    }
    if (!empty($breadcrumbSchema)) {
        $schemasToOutput[] = $breadcrumbSchema;
    }

    $jsonLdString = json_encode(
        count($schemasToOutput) === 1 ? $schemasToOutput[0] : $schemasToOutput,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    $faviconUrl = function_exists('assetUrl') ? assetUrl('/main/assets/images/favicon.png') : '/main/assets/images/favicon.png?v=2';

    $html = [];
    $html[] = '<meta charset="UTF-8">';
    $html[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html[] = sprintf('<title>%s</title>', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($description, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:title" content="%s">', htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:description" content="%s">', htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:image" content="%s">', htmlspecialchars($ogImagePrimary, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:url" content="%s">', htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:type" content="%s">', htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8'));
    $html[] = '<meta name="twitter:card" content="summary_large_image">';
    $html[] = sprintf('<meta name="twitter:title" content="%s">', htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="twitter:description" content="%s">', htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="twitter:image" content="%s">', htmlspecialchars($ogImagePrimary, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<link rel="icon" type="image/png" href="%s">', htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'));
    $html[] = '<script type="application/ld+json">';
    $html[] = $jsonLdString;
    $html[] = '</script>';

    return implode("\n    ", $html);
}

/**
 * Standardized Entity Image HTML Generator.
 */
function renderEntityImage(
    string $entityName,
    string $imagePath,
    string $role = 'CEO of Nodexplatform',
    string $class = '',
    string $extraAttrs = ''
): string {
    $alt = trim($entityName . ($role !== '' ? ' - ' . $role : ''));
    $title = $entityName;
    $src = function_exists('assetUrl') ? assetUrl($imagePath) : $imagePath;

    $classAttr = !empty($class) ? sprintf(' class="%s"', htmlspecialchars($class, ENT_QUOTES, 'UTF-8')) : '';
    $extraAttrStr = !empty($extraAttrs) ? ' ' . trim($extraAttrs) : '';

    return sprintf(
        '<img src="%s" alt="%s" title="%s"%s%s />',
        htmlspecialchars((string)$src, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$alt, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8'),
        $classAttr,
        $extraAttrStr
    );
}
