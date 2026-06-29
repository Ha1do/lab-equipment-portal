<?php

class AuthMiddleware {
    public static function requireLogin(): void {
        if (!Session::has('user_id')) {
            // Используем BASE_URL, чтобы редиректы работали при размещении в подпапке (например /Portal)
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (Session::get('user_role') !== 'admin') {
            http_response_code(403);
            die('Доступ запрещён.');
        }
    }

    public static function currentUser(): ?array {
        if (!Session::has('user_id')) return null;
        return [
            'id'   => Session::get('user_id'),
            'name' => Session::get('user_name'),
            'role' => Session::get('user_role'),
        ];
    }

    public static function isAdmin(): bool {
        return Session::get('user_role') === 'admin';
    }
}