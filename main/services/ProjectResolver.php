<?php

declare(strict_types=1);

namespace Main\Services;

use Main\Database\JsonDatabase;

class ProjectResolver
{
    private JsonDatabase $db;
    private string $publicPath;

    public function __construct(?JsonDatabase $db = null, ?string $publicPath = null)
    {
        $this->db = $db ?? new JsonDatabase();
        $this->publicPath = $publicPath ?? dirname(__DIR__, 2) . '/public';
    }

    public function resolveProject(string $projectId): array
    {
        $project = $this->db->table('projects')->where('id', $projectId)->first();

        if (!$project) {
            return [
                'success' => false,
                'reason' => 'PROJECT_NOT_FOUND',
                'message' => "Project record '{$projectId}' not found in registry.",
            ];
        }

        $folder = $project['folder'] ?? $projectId;
        $folderPath = $this->publicPath . '/' . $folder;

        if (!is_dir($folderPath)) {
            return [
                'success' => false,
                'reason' => 'FOLDER_MISSING',
                'message' => "Physical directory for project '{$projectId}' does not exist.",
            ];
        }

        $status = strtoupper($project['status'] ?? 'CREATED');
        if ($status !== 'ACTIVE') {
            return [
                'success' => false,
                'reason' => 'PROJECT_' . $status,
                'message' => "Tenant project '{$projectId}' is currently {$status}.",
                'project' => $project,
            ];
        }

        return [
            'success' => true,
            'project' => $project,
            'folder_path' => $folderPath,
            'entry_file' => file_exists($folderPath . '/index.php') ? $folderPath . '/index.php' : null,
        ];
    }

    public function isFolderRegistered(string $folderName): bool
    {
        return $this->db->table('projects')->where('folder', $folderName)->exists();
    }
}
