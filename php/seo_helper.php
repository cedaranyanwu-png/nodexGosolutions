<?php
/**
 * php/seo_helper.php
 *
 * Reusable SEO Metadata & JSON-LD Schema Helper Module.
 * Provides functions to generate dynamic Open Graph meta tags, Twitter card tags,
 * JSON-LD Person/ProfilePage schemas, and standardized entity image HTML tags.
 */

declare(strict_types=1);

/**
 * Builds the Person JSON-LD Schema array structure.
 *
 * @param array $params Custom override values for person fields.
 * @return array JSON-LD Person schema structure.
 */
function getPersonSchema(array $params = []): array {
    $baseUrl = $params['site_url'] ?? 'https://nodexplatform.com.ng';
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $params['name'] ?? 'Cedar Anyanwu',
        'url' => $params['url'] ?? $baseUrl,
        'image' => $params['image'] ?? $baseUrl,
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
 * Builds the ProfilePage JSON-LD Schema array structure with an embedded Person mainEntity.
 *
 * @param array $personParams Custom override values for person fields.
 * @param array $pageParams Custom override values for profile page fields.
 * @return array JSON-LD ProfilePage schema structure.
 */
function getProfilePageSchema(array $personParams = [], array $pageParams = []): array {
    $personSchema = getPersonSchema($personParams);
    return [
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        'name' => $pageParams['title'] ?? ($personSchema['name'] . ' - Profile'),
        'mainEntity' => $personSchema
    ];
}

/**
 * Renders the HTML metadata tags, Open Graph tags, Twitter tags, and dynamic JSON-LD Schema script
 * to be injected directly into the <head> block of any page.
 *
 * @param array $options Configuration map for page titles, descriptions, images, and schema.
 * @return string Safe HTML string ready to be output in <head>.
 */
function renderSeoHead(array $options = []): string {
    $siteUrl = $options['site_url'] ?? 'https://nodexplatform.com.ng';
    $title = $options['title'] ?? 'nodexGosolutions | Bridging Earth & Space Tech';
    $description = $options['description'] ?? 'nodexGosolutions deploys high-speed modular web frameworks, maps satellite telemetry data, and powers modern creative entertainment platforms.';
    $url = $options['url'] ?? $siteUrl;

    $ogTitle = $options['og_title'] ?? $title;
    $ogDescription = $options['og_description'] ?? $description;
    $ogImage = $options['og_image'] ?? ($siteUrl . '/main/assets/images/cedar-anyanwu.jpg');
    $ogType = $options['og_type'] ?? 'profile';

    // Ensure ogImage is a full absolute URL for external crawlers/scrapers
    if (str_starts_with($ogImage, '/')) {
        $ogImage = rtrim($siteUrl, '/') . $ogImage;
    }

    // Determine JSON-LD Schema payload
    $schemaData = $options['schema_data'] ?? null;
    if ($schemaData === null) {
        $schemaType = strtolower((string)($options['schema_type'] ?? 'person'));
        if ($schemaType === 'profilepage') {
            $schemaData = getProfilePageSchema($options['person_params'] ?? [], ['title' => $title]);
        } else {
            // Default to Person schema
            $schemaData = getPersonSchema($options['person_params'] ?? []);
        }
    }

    $jsonLd = json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $faviconUrl = function_exists('assetUrl') ? assetUrl('/main/assets/images/favicon.png') : '/main/assets/images/favicon.png?v=2';

    $html = [];
    $html[] = '<meta charset="UTF-8">';
    $html[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html[] = sprintf('<title>%s</title>', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($description, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:title" content="%s">', htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:description" content="%s">', htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:image" content="%s">', htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:url" content="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta property="og:type" content="%s">', htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8'));
    $html[] = '<meta name="twitter:card" content="summary_large_image">';
    $html[] = sprintf('<meta name="twitter:title" content="%s">', htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="twitter:description" content="%s">', htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<meta name="twitter:image" content="%s">', htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'));
    $html[] = sprintf('<link rel="icon" type="image/png" href="%s">', htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'));
    $html[] = '<script type="application/ld+json">';
    $html[] = $jsonLd;
    $html[] = '</script>';

    return implode("\n    ", $html);
}

/**
 * Standardized Entity Image HTML Generator.
 * Enforces standardized entity image naming, alt tag attributes, and title attributes.
 * Format: <img src="cedar-anyanwu.jpg" alt="Cedar Anyanwu - CEO of Nodexplatform" title="Cedar Anyanwu">
 *
 * @param string $entityName Full name of the entity (e.g. "Cedar Anyanwu").
 * @param string $imagePath Asset path to image matching entity filename (e.g. "/main/assets/images/cedar-anyanwu.jpg").
 * @param string $role Designation or title suffix (e.g. "CEO of Nodexplatform").
 * @param string $class CSS classes to apply.
 * @param string $extraAttrs Additional raw HTML attributes (e.g. onclick handler).
 * @return string HTML <img> tag string.
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
