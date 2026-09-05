<?php

declare(strict_types=1);

namespace Main\Services;

use Main\Config\DomainConfig;

class DomainScanner
{
    private string $rootDir;

    public function __construct(?string $rootDir = null)
    {
        $this->rootDir = $rootDir ?? dirname(__DIR__, 2);
    }

    public function scanForHardcodedMainDomain(?string $targetDomain = null): array
    {
        $domainToFind = $targetDomain ?? DomainConfig::mainDomain();
        $findings = [];

        $ignoredDirs = ['.git', 'storage', 'vendor', 'node_modules', 'cache', 'logs'];
        $ignoredFiles = ['domain_config.json', 'DomainScanner.php', 'test_architecture.php'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->rootDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            $relativePath = str_replace($this->rootDir . '/', '', $file->getPathname());
            $fileName = $file->getFilename();

            // Skip ignored directories
            $pathParts = explode('/', $relativePath);
            if (array_intersect($pathParts, $ignoredDirs)) {
                continue;
            }

            if (in_array($fileName, $ignoredFiles, true)) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['php', 'json', 'js', 'html', 'css'], true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            if (str_contains(strtolower($content), strtolower($domainToFind))) {
                // Find line numbers
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (str_contains(strtolower($line), strtolower($domainToFind))) {
                        $findings[] = [
                            'file' => $relativePath,
                            'line' => $lineNum + 1,
                            'content' => trim($line),
                        ];
                    }
                }
            }
        }

        return $findings;
    }
}
