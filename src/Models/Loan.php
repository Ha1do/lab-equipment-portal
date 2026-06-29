<?php

class Loan {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function activeForItem(int $itemId): ?array {
        $stmt = $this->db->prepare('
            SELECT l.*, u.name AS user_name
            FROM loans l JOIN users u ON u.id = l.user_id
            WHERE l.item_id = ? AND l.returned_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
    }

    public function borrow(int $itemId, int $userId, ?string $notes): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
            INSERT INTO loans (item_id, user_id, notes) VALUES (?, ?, ?)
        ');
            $stmt->execute([$itemId, $userId, $notes ?: null]);
            $this->db->prepare('UPDATE items SET status="borrowed" WHERE id=?')->execute([$itemId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function returnItem(int $loanId, int $itemId): bool {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE loans SET returned_at=NOW() WHERE id=?')->execute([$loanId]);
            $this->db->prepare('UPDATE items SET status="available" WHERE id=?')->execute([$itemId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function historyForItem(int $itemId): array {
        $stmt = $this->db->prepare('
            SELECT l.*, u.name AS user_name
            FROM loans l JOIN users u ON u.id = l.user_id
            WHERE l.item_id = ?
            ORDER BY l.borrowed_at DESC
        ');
        $stmt->execute([$itemId]);
        return $stmt->fetchAll();
    }

    public function myLoans(int $userId): array {
        $stmt = $this->db->prepare('
            SELECT l.*, i.name AS item_name, i.sap_num
            FROM loans l JOIN items i ON i.id = l.item_id
            WHERE l.user_id = ?
            ORDER BY l.borrowed_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}