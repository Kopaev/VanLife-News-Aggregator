<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Repository\AdminRepository;

/**
 * Authentication and session management service
 */
class AuthService
{
    private const COOKIE_NAME = 'vanlife_admin_session';
    private const TOKEN_LENGTH = 64;

    private AdminRepository $adminRepository;
    private Config $config;
    private ?array $currentAdmin = null;

    public function __construct(AdminRepository $adminRepository, Config $config)
    {
        $this->adminRepository = $adminRepository;
        $this->config = $config;
    }

    /**
     * Attempt to login with username and password
     */
    public function login(string $username, string $password): bool
    {
        // First, check if admin exists in database
        $admin = $this->adminRepository->findByUsername($username);

        if ($admin) {
            // Admin exists in DB - verify password hash
            if (!password_verify($password, $admin['password_hash'])) {
                return false;
            }
        } else {
            // No admin in DB - check against env credentials and create
            $envUsername = $this->config->get('admin.username');
            $envPassword = $this->config->get('admin.password');

            if ($username !== $envUsername || $password !== $envPassword) {
                return false;
            }

            // Create admin in database
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $adminId = $this->adminRepository->createAdmin($username, $passwordHash);
            $admin = $this->adminRepository->findById($adminId);

            if (!$admin) {
                return false;
            }
        }

        // Create session
        $token = $this->generateToken();
        $lifetime = (int) $this->config->get('session.lifetime', 86400);

        $this->adminRepository->createSession((int) $admin['id'], $token, $lifetime);
        $this->adminRepository->updateLastLogin((int) $admin['id']);

        // Set cookie
        $this->setSessionCookie($token, $lifetime);

        $this->currentAdmin = $admin;

        return true;
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        $token = $this->getSessionToken();

        if ($token) {
            $this->adminRepository->deleteSession($token);
        }

        $this->clearSessionCookie();
        $this->currentAdmin = null;
    }

    /**
     * Check if current request is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->getCurrentAdmin() !== null;
    }

    /**
     * Get current authenticated admin
     */
    public function getCurrentAdmin(): ?array
    {
        if ($this->currentAdmin !== null) {
            return $this->currentAdmin;
        }

        $token = $this->getSessionToken();

        if (!$token) {
            return null;
        }

        $session = $this->adminRepository->findValidSession($token);

        if (!$session) {
            $this->clearSessionCookie();
            return null;
        }

        $this->currentAdmin = [
            'id' => $session['admin_id'],
            'username' => $session['username'],
        ];

        return $this->currentAdmin;
    }

    /**
     * Ensure admin exists (for initial setup)
     */
    public function ensureAdminExists(): void
    {
        if ($this->adminRepository->getAdminCount() > 0) {
            return;
        }

        $username = $this->config->get('admin.username');
        $password = $this->config->get('admin.password');

        if (!$username || !$password) {
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $this->adminRepository->createAdmin($username, $passwordHash);
    }

    /**
     * Generate secure random token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }

    /**
     * Get session token from cookie
     */
    private function getSessionToken(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }

    /**
     * Set session cookie
     */
    private function setSessionCookie(string $token, int $lifetime): void
    {
        $secure = (bool) $this->config->get('session.secure', true);
        $expires = time() + $lifetime;

        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        // Also set for current request
        $_COOKIE[self::COOKIE_NAME] = $token;
    }

    /**
     * Clear session cookie
     */
    private function clearSessionCookie(): void
    {
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => (bool) $this->config->get('session.secure', true),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Clean up expired sessions (call from cron)
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->adminRepository->cleanupExpiredSessions();
    }
}
