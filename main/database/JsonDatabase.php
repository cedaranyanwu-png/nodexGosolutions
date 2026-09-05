<?php

declare(strict_types=1);

namespace Main\Database;

use Exception;
use InvalidArgumentException;
use RuntimeException;

class JsonDatabase
{
    private string $storagePath;
    private ?string $currentTable = null;
    private array $whereConditions = [];
    private ?string $orderByColumn = null;
    private string $orderDirection = 'asc';
    private ?int $limitValue = null;
    private int $offsetValue = 0;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? __DIR__ . '/../storage/db/';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function table(string $tableName): self
    {
        $clone = clone $this;
        $clone->currentTable = $tableName;
        $clone->resetQuery();
        return $clone;
    }

    private function resetQuery(): void
    {
        $this->whereConditions = [];
        $this->orderByColumn = null;
        $this->orderDirection = 'asc';
        $this->limitValue = null;
        $this->offsetValue = 0;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $operator = '=';
            $val = $operatorOrValue;
        } else {
            $operator = strtolower((string)$operatorOrValue);
            $val = $value;
        }

        $this->whereConditions[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $val,
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderByColumn = $column;
        $this->orderDirection = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limitValue = $limit;
        $this->offsetValue = $offset;
        return $this;
    }

    private function getFilePath(): string
    {
        if (!$this->currentTable) {
            throw new RuntimeException('No table specified for JsonDatabase operation.');
        }

        // Sanitize table name to prevent path traversal
        $safeTable = preg_replace('/[^a-zA-Z0-9_-]/', '', $this->currentTable);
        return $this->storagePath . '/' . $safeTable . '.json';
    }

    private function readTableData(): array
    {
        $filePath = $this->getFilePath();
        if (!file_exists($filePath)) {
            return [];
        }

        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            throw new RuntimeException("Unable to open database file: {$filePath}");
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON decode error in file {$filePath}: " . json_last_error_msg());
        }

        return is_array($data) ? $data : [];
    }

    private function writeTableData(array $data): void
    {
        $filePath = $this->getFilePath();
        $lockFile = $filePath . '.lock';

        $lockFp = fopen($lockFile, 'c+');
        if (!$lockFp) {
            throw new RuntimeException("Unable to open lock file: {$lockFile}");
        }

        if (!flock($lockFp, LOCK_EX)) {
            fclose($lockFp);
            throw new RuntimeException("Unable to acquire exclusive lock for table: {$this->currentTable}");
        }

        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException("JSON encode error: " . json_last_error_msg());
            }

            $tmpFile = $filePath . '.' . uniqid('tmp_', true);
            if (file_put_contents($tmpFile, $json) === false) {
                throw new RuntimeException("Failed to write to temporary file: {$tmpFile}");
            }

            if (!rename($tmpFile, $filePath)) {
                @unlink($tmpFile);
                throw new RuntimeException("Failed to atomically replace database file: {$filePath}");
            }
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    private function executeQuery(): array
    {
        $rows = $this->readTableData();

        if (!empty($this->whereConditions)) {
            $rows = array_filter($rows, function ($row) {
                foreach ($this->whereConditions as $condition) {
                    $column = $condition['column'];
                    $op = $condition['operator'];
                    $val = $condition['value'];

                    $rowVal = $row[$column] ?? null;

                    $matches = match ($op) {
                        '=', '==' => $rowVal == $val,
                        '===' => $rowVal === $val,
                        '!=', '<>' => $rowVal != $val,
                        '!==' => $rowVal !== $val,
                        '>' => $rowVal > $val,
                        '>=' => $rowVal >= $val,
                        '<' => $rowVal < $val,
                        '<=' => $rowVal <= $val,
                        'like' => is_string($rowVal) && str_contains(strtolower($rowVal), strtolower((string)$val)),
                        'in' => is_array($val) && in_array($rowVal, $val, true),
                        'not_in' => is_array($val) && !in_array($rowVal, $val, true),
                        default => false,
                    };

                    if (!$matches) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($this->orderByColumn !== null) {
            $col = $this->orderByColumn;
            $dir = $this->orderDirection;

            usort($rows, function ($a, $b) use ($col, $dir) {
                $valA = $a[$col] ?? null;
                $valB = $b[$col] ?? null;

                if ($valA === $valB) {
                    return 0;
                }

                if ($dir === 'asc') {
                    return $valA <=> $valB;
                }
                return $valB <=> $valA;
            });
        }

        if ($this->limitValue !== null) {
            $rows = array_slice($rows, $this->offsetValue, $this->limitValue);
        }

        return array_values($rows);
    }

    public function get(): array
    {
        $results = $this->executeQuery();
        $this->resetQuery();
        return $results;
    }

    public function all(): array
    {
        $this->resetQuery();
        return $this->get();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->executeQuery();
        $this->resetQuery();
        return $results[0] ?? null;
    }

    public function find(string|int $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    public function count(): int
    {
        $results = $this->executeQuery();
        $this->resetQuery();
        return count($results);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $data): array
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Data to insert cannot be empty.");
        }

        $allData = $this->readTableData();

        if (!isset($data['id'])) {
            $data['id'] = $this->generateUniqueId();
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $allData[] = $data;
        $this->writeTableData($allData);
        $this->resetQuery();

        return $data;
    }

    public function update(string|int $id, array $data, string $primaryKey = 'id'): bool
    {
        $allData = $this->readTableData();
        $updated = false;

        foreach ($allData as $index => $row) {
            if (isset($row[$primaryKey]) && $row[$primaryKey] == $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $allData[$index] = array_merge($row, $data);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->writeTableData($allData);
        }

        $this->resetQuery();
        return $updated;
    }

    public function updateWhere(array $data): int
    {
        $allData = $this->readTableData();
        $matchingIndices = [];

        foreach ($allData as $index => $row) {
            $matches = true;
            foreach ($this->whereConditions as $condition) {
                $column = $condition['column'];
                $op = $condition['operator'];
                $val = $condition['value'];
                $rowVal = $row[$column] ?? null;

                $match = match ($op) {
                    '=', '==' => $rowVal == $val,
                    '===' => $rowVal === $val,
                    '!=', '<>' => $rowVal != $val,
                    default => false,
                };
                if (!$match) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $matchingIndices[] = $index;
            }
        }

        if (!empty($matchingIndices)) {
            foreach ($matchingIndices as $index) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $allData[$index] = array_merge($allData[$index], $data);
            }
            $this->writeTableData($allData);
        }

        $count = count($matchingIndices);
        $this->resetQuery();
        return $count;
    }

    public function delete(string|int $id, string $primaryKey = 'id'): bool
    {
        $allData = $this->readTableData();
        $initialCount = count($allData);

        $allData = array_filter($allData, function ($row) use ($id, $primaryKey) {
            return !isset($row[$primaryKey]) || $row[$primaryKey] != $id;
        });

        if (count($allData) < $initialCount) {
            $this->writeTableData(array_values($allData));
            $this->resetQuery();
            return true;
        }

        $this->resetQuery();
        return false;
    }

    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;

        $items = $this->limit($perPage, $offset)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    private function generateUniqueId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
