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

    /**
     * Get all sources with statistics
     */
    public function getAllSources(): array
    {
        return $this->db->fetchAll(
            'SELECT s.*,
                    l.name_ru AS language_name,
                    c.name_ru AS country_name,
                    c.flag_emoji AS country_flag
             FROM sources s
             LEFT JOIN languages l ON l.code = s.language_code
             LEFT JOIN countries c ON c.code = s.country_code
             ORDER BY s.is_enabled DESC, s.name ASC'
        );
    }

    /**
     * Toggle source enabled/disabled status
     */
    public function toggleEnabled(int $sourceId): void
    {
        $this->db->execute(
            'UPDATE sources SET is_enabled = NOT is_enabled, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$sourceId]
        );
    }

    /**
     * Find source by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM sources WHERE id = ?', [$id]);
    }
}
