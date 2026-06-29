<?php

class ItemController {
    public function index(): void {
        AuthMiddleware::requireLogin();
        $itemModel = new Item();

        $allowed_sort = ['sap_num', 'category', 'name', 'room_name'];
        $sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'name';
        $dir  = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'room'   => $_GET['room']   ?? '',
            'sort'   => $sort,
            'dir'    => $dir,
        ];
        $items     = $itemModel->all($filters);
        $rooms     = $itemModel->allRooms();
        $pageTitle = 'Vybavenie';
        ob_start();
        require BASE_PATH . '/src/Views/pages/items/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function show(): void {
        AuthMiddleware::requireLogin();
        $id        = (int)($_GET['id'] ?? 0);
        $itemModel = new Item();
        $loanModel = new Loan();
        $item      = $itemModel->findById($id);
        if (!$item) { http_response_code(404); die('Nenájdené.'); }
        $activeLoan = $loanModel->activeForItem($id);
        $history    = $loanModel->historyForItem($id);
        $rooms      = $itemModel->allRooms();
        $db         = Database::getInstance();
        $stmt       = $db->prepare('
            SELECT c.*, u.name AS user_name
            FROM comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.item_id = ?
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$id]);
        $comments  = $stmt->fetchAll();
        $pageTitle = htmlspecialchars($item['name']);
        ob_start();
        require BASE_PATH . '/src/Views/pages/items/show.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function borrow(): void {
        AuthMiddleware::requireLogin();
        $itemId = (int)($_POST['item_id'] ?? 0);
        $userId = Session::get('user_id');
        $notes  = $_POST['notes'] ?? null;

        $item = (new Item())->findById($itemId);
        if (!$item || $item['status'] !== 'available') {
            Session::flash('error', 'Vybavenie nie je dostupné na požičanie.');
            header('Location: ' . BASE_URL . "/items/show?id=$itemId");
            exit;
        }

        if ((new Loan())->borrow($itemId, $userId, $notes)) {
            Session::flash('success', 'Úspešne ste si požičali: ' . $item['name']);
        } else {
            Session::flash('error', 'Chyba pri spracovaní výpožičky.');
        }
        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function return(): void {
        AuthMiddleware::requireLogin();
        $loanId = (int)($_POST['loan_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);

        if ((new Loan())->returnItem($loanId, $itemId)) {
            Session::flash('success', 'Vybavenie bolo vrátené.');
        } else {
            Session::flash('error', 'Chyba pri vrátení.');
        }
        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function addComment(): void {
        AuthMiddleware::requireLogin();
        $itemId  = (int)($_POST['item_id'] ?? 0);
        $userId  = Session::get('user_id');
        $comment = trim($_POST['comment'] ?? '');

        if ($comment === '') {
            Session::flash('error', 'Komentár nemôže byť prázdny.');
            header('Location: ' . BASE_URL . "/items/show?id=$itemId");
            exit;
        }

        $db = Database::getInstance();
        $db->prepare('INSERT INTO comments (item_id, user_id, comment) VALUES (?, ?, ?)')
            ->execute([$itemId, $userId, $comment]);

        Session::flash('success', 'Komentár bol pridaný.');
        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function updateLocation(): void {
        AuthMiddleware::requireLogin();
        $itemId    = (int)($_POST['item_id'] ?? 0);
        $clearTemp = isset($_POST['clear_temp']);

        $db = Database::getInstance();
        if ($clearTemp) {
            $db->prepare('UPDATE items SET temp_room = NULL, temp_closet = NULL, temp_shelf = NULL WHERE id = ?')
                ->execute([$itemId]);
        } else {
            $tempRoom   = trim($_POST['temp_room']   ?? '');
            $tempCloset = trim($_POST['temp_closet'] ?? '');
            $tempShelf  = trim($_POST['temp_shelf']  ?? '');
            $db->prepare('UPDATE items SET temp_room = ?, temp_closet = ?, temp_shelf = ? WHERE id = ?')
                ->execute([$tempRoom ?: null, $tempCloset ?: null, $tempShelf ?: null, $itemId]);
        }

        Session::flash('success', 'Dočasné umiestnenie bolo uložené.');
        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function updateStatus(): void {
        AuthMiddleware::requireAdmin();
        $itemId = (int)($_POST['item_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['available', 'borrowed', 'maintenance'], true)) {
            Session::flash('error', 'Neplatný stav.');
            header('Location: ' . BASE_URL . "/items/show?id=$itemId");
            exit;
        }

        $db    = Database::getInstance();
        $label = $status === 'maintenance' ? ($_POST['vyucba_label'] ?? null) : null;
        $db->prepare('UPDATE items SET status = ?, vyucba_label = ? WHERE id = ?')
            ->execute([$status, $label, $itemId]);

        Session::flash('success', 'Stav bol zmenený.');
        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function toggleVyucba(): void {
        AuthMiddleware::requireLogin();
        $itemId = (int)($_POST['item_id'] ?? 0);
        $db     = Database::getInstance();

        $item = (new Item())->findById($itemId);
        if (!$item) {
            header('Location: ' . BASE_URL . "/items/show?id=$itemId");
            exit;
        }

        $userId = Session::get('user_id');
        $stmt   = $db->prepare('SELECT abbreviation, name FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user  = $stmt->fetch();
        $label = $user['abbreviation'] ?: $user['name'];

        if ($item['status'] === 'maintenance') {
            $db->prepare('UPDATE items SET status = "available", vyucba_label = NULL WHERE id = ?')
                ->execute([$itemId]);
            Session::flash('success', 'Zariadenie bolo uvoľnené z výučby.');
        } else {
            $db->prepare('UPDATE items SET status = "maintenance", vyucba_label = ? WHERE id = ?')
                ->execute([$label, $itemId]);
            Session::flash('success', 'Zariadenie označené ako Výučba: ' . $label);
        }

        header('Location: ' . BASE_URL . "/items/show?id=$itemId");
        exit;
    }

    public function export(): void {
        AuthMiddleware::requireLogin();
        $itemModel = new Item();

        $allowed_sort = ['sap_num', 'category', 'name', 'room_name'];
        $sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'name';
        $dir  = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'room'   => $_GET['room']   ?? '',
            'sort'   => $sort,
            'dir'    => $dir,
        ];

        $items    = $itemModel->all($filters);
        $filename = 'export_' . date('Y-m-d_H-i') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['SAP č.', 'Inventárne č.', 'Názov', 'Stav', 'Miestnosť', 'Skriňa', 'Polica', 'Poznámka'], ';');

        foreach ($items as $item) {
            if (!empty($item['personal'])) continue;

            $catParts = array_filter([
                $item['category_symbol'] ?? '',
                $item['category_num1']   ?? '',
                $item['category_num2']   ?? '',
                $item['category_num3']   ?? '',
            ]);

            $status = match($item['status']) {
                'available'   => 'Dostupné',
                'borrowed'    => 'Požičané',
                'maintenance' => 'Výučba',
                default       => $item['status'],
            };

            $displayCloset = $item['temp_closet'] ?: $item['closet'];
            $displayShelf  = $item['temp_shelf']  ?: $item['shelf'];
            $displayRoom   = !empty($item['temp_room']) ? ($item['temp_room_name'] ?? $item['temp_room']) : ($item['room_name'] ?? $item['sap_position'] ?? '');

            fputcsv($out, [
                $item['sap_num'] ?? '',
                implode(' / ', $catParts),
                $item['name'],
                $status,
                $displayRoom,
                $displayCloset ?? '',
                $displayShelf  ?? '',
                $item['note']  ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }
}