<?php
declare(strict_types=1);

class Item
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $filters = []): array
    {
        $sql = '
            SELECT i.*,
                   r.name  AS room_name,
                   tr.name AS temp_room_name
            FROM items i
            LEFT JOIN rooms r  ON r.code  = i.sap_position OR r.new_code = i.sap_position
            LEFT JOIN rooms tr ON tr.code = i.temp_room    OR tr.new_code = i.temp_room
            WHERE i.archived = 0
        ';

        $showNoTriedenie = $this->db->query("SELECT value FROM app_settings WHERE `key` = 'show_no_triedenie'")->fetchColumn();
        $noTriedeniePart = $showNoTriedenie ? 'OR i.triedenie IS NULL OR i.triedenie = \'\'' : '';
        $sql .= " AND (i.triedenie IN (SELECT triedenie FROM triedenie_settings WHERE visible = 1) $noTriedeniePart)";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (
                i.name LIKE ? OR
                i.triedenie LIKE ? OR
                CONCAT(
                    COALESCE(i.category_symbol,""),
                    COALESCE(i.category_num1,""), "/",
                    COALESCE(i.category_num2,""), "/",
                    COALESCE(i.category_num3,"")
                ) LIKE ?
            )';
            $like   = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if (!empty($filters['status'])) {
            $sql     .= ' AND i.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['room'])) {
            $sql     .= ' AND i.sap_position = ?';
            $params[] = $filters['room'];
        }

        $sortCol = match($filters['sort'] ?? 'name') {
            'sap_num'   => 'i.sap_num',
            'category'  => 'CONCAT(COALESCE(i.category_symbol,""), COALESCE(i.category_num1,""))',
            'room_name' => 'r.name',
            default     => 'i.name',
        };
        $sortDir = ($filters['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY $sortCol $sortDir";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT i.*,
                   r.name  AS room_name,
                   tr.name AS temp_room_name
            FROM items i
            LEFT JOIN rooms r  ON r.code  = i.sap_position OR r.new_code = i.sap_position
            LEFT JOIN rooms tr ON tr.code = i.temp_room    OR tr.new_code = i.temp_room
            WHERE i.id = ? AND i.archived = 0
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySapNum(string $sapNum): ?array
    {
        if ($sapNum === '') return null;
        $stmt = $this->db->prepare('SELECT * FROM items WHERE sap_num = ? LIMIT 1');
        $stmt->execute([$sapNum]);
        return $stmt->fetch() ?: null;
    }

    public function stats(): array
    {
        return $this->db->query('
            SELECT
                COUNT(*)                  AS total,
                SUM(status="available")   AS available,
                SUM(status="borrowed")    AS borrowed,
                SUM(status="maintenance") AS maintenance,
                SUM(archived=1)           AS archived
            FROM items
        ')->fetch();
    }

    public function distinctTriedenie(): array
    {
        return $this->db->query('
            SELECT DISTINCT triedenie FROM items
            WHERE archived = 0 AND triedenie IS NOT NULL AND triedenie <> \'\'
            ORDER BY triedenie
        ')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function allTriedenie(): array
    {
        return $this->db->query('SELECT * FROM triedenie_settings ORDER BY triedenie')->fetchAll();
    }

    public function distinctClosets(): array
    {
        return $this->db->query('
            SELECT DISTINCT closet FROM items
            WHERE archived = 0 AND closet IS NOT NULL AND closet <> \'\'
            ORDER BY closet
        ')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function allRooms(): array
    {
        return $this->db->query('SELECT code, new_code, name FROM rooms ORDER BY name')->fetchAll();
    }

    private function nullifyEmpties(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        return $data;
    }
}