<?php

namespace App\Repository;

use App\Core\Database;

class SourceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getEnabledSources(): array
    {
        return $this->db->fetchAll('SELECT * FROM sources WHERE is_enabled = 1');
    }

    public function updateSourceLastFetched(int $sourceId): void
    {
        $this->db->execute('UPDATE sources SET last_fetched_at = NOW(), last_error = NULL WHERE id = ?', [$sourceId]);
    }

    public function updateSourceLastError(int $sourceId, string $error): void
    {
        $this->db->execute('UPDATE sources SET last_error = ? WHERE id = ?', [$error, $sourceId]);
    }
}
