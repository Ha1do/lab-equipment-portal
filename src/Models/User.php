<?php

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id, name, email, role, abbreviation, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password, string $role = 'user'): bool {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$name, $email, $hash, $role]);
    }

    public function all(): array {
        return $this->db->query('SELECT id, name, email, role, abbreviation, active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}