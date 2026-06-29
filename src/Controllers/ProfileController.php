<?php
declare(strict_types=1);

class ProfileController
{
    public function show(): void
    {
        AuthMiddleware::requireLogin();
        $userId    = Session::get('user_id');
        $db        = Database::getInstance();

        $user = $db->prepare('SELECT * FROM users WHERE id = ?');
        $user->execute([$userId]);
        $user = $user->fetch();

        // Borrowed items
        $borrowed = $db->prepare('
        SELECT i.*, l.borrowed_at,
               r.name  AS room_name,
               tr.name AS temp_room_name
        FROM loans l
        JOIN items i ON i.id = l.item_id
        LEFT JOIN rooms r  ON r.code  = i.sap_position OR r.new_code = i.sap_position
        LEFT JOIN rooms tr ON tr.code = i.temp_room    OR tr.new_code = i.temp_room
        WHERE l.user_id = ? AND l.returned_at IS NULL
        ');
        $borrowed->execute([$userId]);
        $borrowedItems = $borrowed->fetchAll();

        // Personal items (assigned by personal field)
        $personal = $db->prepare('
        SELECT i.*, r.name AS room_name
        FROM items i
        LEFT JOIN rooms r ON r.code = i.sap_position
        WHERE i.personal = ? AND i.archived = 0
        ');
        $personal->execute([$user['abbreviation'] ?? '']);
        $personalItems = $personal->fetchAll();

        $pageTitle = 'Môj profil';
        ob_start();
        require BASE_PATH . '/src/Views/pages/profile.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function update(): void
    {
        AuthMiddleware::requireLogin();
        $userId = Session::get('user_id');
        $db     = Database::getInstance();

        $name         = trim($_POST['name']             ?? '');
        $email        = trim($_POST['email']            ?? '');
        $abbreviation = trim($_POST['abbreviation']     ?? '');
        $password     = $_POST['password']              ?? '';
        $confirm      = $_POST['password_confirm']      ?? '';

        if ($name === '' || $email === '') {
            Session::flash('error', 'Meno a email sú povinné.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }

        if ($password !== '' && $password !== $confirm) {
            Session::flash('error', 'Heslá sa nezhodujú.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }

        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare('UPDATE users SET name = ?, email = ?, password = ?, abbreviation = ? WHERE id = ?')
                    ->execute([$name, $email, $hash, $abbreviation ?: null, $userId]);
            } else {
                $db->prepare('UPDATE users SET name = ?, email = ?, abbreviation = ? WHERE id = ?')
                    ->execute([$name, $email, $abbreviation ?: null, $userId]);
            }

            Session::set('user_name', $name);
            Session::flash('success', 'Profil bol aktualizovaný.');
        } catch (Throwable $e) {
            Session::flash('error', 'Email už existuje alebo nastala chyba.');
        }

        header('Location: ' . BASE_URL . '/profile');
        exit;
    }
}