<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Repository for admin users and sessions management
 */
class AdminRepository
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Find admin by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->database->fetchOne(
            'SELECT * FROM admins WHERE username = :username',
            ['username' => $username]
        );
    }

    /**
     * Find admin by ID
     */
    public function findById(int $id): ?array
    {
        return $this->database->fetchOne(
            'SELECT * FROM admins WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Create new admin user
     */
    public function createAdmin(string $username, string $passwordHash): int
    {
        $this->database->execute(
            'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)',
            [
                'username' => $username,
                'password_hash' => $passwordHash,
            ]
        );

        return (int) $this->database->lastInsertId();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $adminId): void
    {
        $this->database->execute(
            'UPDATE admins SET last_login_at = NOW() WHERE id = :id',
            ['id' => $adminId]
        );
    }

    /**
     * Create new session token
     */
    public function createSession(int $adminId, string $token, int $lifetimeSeconds): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetimeSeconds);

        $this->database->execute(
            'INSERT INTO admin_sessions (admin_id, token, expires_at) VALUES (:admin_id, :token, :expires_at)',
            [
                'admin_id' => $adminId,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Find valid session by token
     */
    public function findValidSession(string $token): ?array
    {
        return $this->database->fetchOne(
            'SELECT s.*, a.username
             FROM admin_sessions s
             JOIN admins a ON a.id = s.admin_id
             WHERE s.token = :token AND s.expires_at > NOW()',
            ['token' => $token]
        );
    }

    /**
     * Delete session by token
     */
    public function deleteSession(string $token): void
    {
        $this->database->execute(
            'DELETE FROM admin_sessions WHERE token = :token',
            ['token' => $token]
        );
    }

    /**
     * Delete all sessions for admin
     */
    public function deleteAllSessions(int $adminId): void
    {
        $this->database->execute(
            'DELETE FROM admin_sessions WHERE admin_id = :admin_id',
            ['admin_id' => $adminId]
        );
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->database->execute(
            'DELETE FROM admin_sessions WHERE expires_at < NOW()'
        );
    }

    /**
     * Get admin count
     */
    public function getAdminCount(): int
    {
        $result = $this->database->fetchOne('SELECT COUNT(*) as count FROM admins');
        return (int) ($result['count'] ?? 0);
    }
}
