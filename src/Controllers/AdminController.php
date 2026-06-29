<?php
declare(strict_types=1);

class AdminController
{
    private const ALLOWED_EXT = ['xlsx', 'xls', 'csv'];

    public function showImport(): void
    {
        AuthMiddleware::requireAdmin();
        $pageTitle = 'Import Excel';
        ob_start();
        require BASE_PATH . '/src/Views/pages/admin/import.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function saveTriedenie(): void
    {
        AuthMiddleware::requireAdmin();
        $db      = Database::getInstance();
        $visible = $_POST['visible'] ?? [];
        $showNoTriedenie = isset($_POST['show_no_triedenie']) ? 1 : 0;

        $db->query('UPDATE triedenie_settings SET visible = 0');
        foreach ($visible as $id) {
            $db->prepare('UPDATE triedenie_settings SET visible = 1 WHERE id = ?')
                ->execute([(int)$id]);
        }

        $db->prepare("INSERT INTO app_settings (`key`, `value`) VALUES ('show_no_triedenie', ?) ON DUPLICATE KEY UPDATE `value` = ?")
            ->execute([$showNoTriedenie, $showNoTriedenie]);

        Session::flash('success', 'Nastavenia triedení boli uložené.');
        header('Location: ' . BASE_URL . '/admin');
        exit;
    }

    public function toggleTriedenie(): void
    {
        AuthMiddleware::requireAdmin();
        header('Content-Type: application/json');

        $type    = $_POST['type']    ?? '';
        $checked = ($_POST['checked'] ?? '0') === '1' ? 1 : 0;
        $db      = Database::getInstance();

        try {
            if ($type === 'triedenie') {
                $id = (int)($_POST['id'] ?? 0);
                $db->prepare('UPDATE triedenie_settings SET visible = ? WHERE id = ?')
                    ->execute([$checked, $id]);
            } elseif ($type === 'setting') {
                $key = $_POST['key'] ?? '';
                $db->prepare("INSERT INTO app_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?")
                    ->execute([$key, $checked, $checked]);
            }
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function handleImport(): void
    {
        AuthMiddleware::requireAdmin();

        if (empty($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Súbor nebol nahraný alebo nastala chyba pri nahrávaní.');
            header('Location: ' . BASE_URL . '/admin/import');
            exit;
        }

        $file = $_FILES['excel_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            Session::flash('error', 'Povolené formáty: .xlsx, .xls, .csv');
            header('Location: ' . BASE_URL . '/admin/import');
            exit;
        }

        $tmpPath = sys_get_temp_dir() . '/import_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            Session::flash('error', 'Nepodarilo sa uložiť nahraný súbor.');
            header('Location: ' . BASE_URL . '/admin/import');
            exit;
        }

        try {
            $parsed = ExcelImporter::parse($tmpPath);
        } catch (RuntimeException $e) {
            @unlink($tmpPath);
            Session::flash('error', 'Chyba čítania súboru: ' . htmlspecialchars($e->getMessage()));
            header('Location: ' . BASE_URL . '/admin/import');
            exit;
        } finally {
            @unlink($tmpPath);
        }

        $excelItems  = $parsed['items'];
        $parseErrors = $parsed['errors'];

        if (empty($excelItems) && empty($parseErrors)) {
            Session::flash('error', 'V súbore sa nenašli žiadne dáta.');
            header('Location: ' . BASE_URL . '/admin/import');
            exit;
        }

        $db      = Database::getInstance();
        $dbItems = [];
        foreach ($db->query('SELECT * FROM items')->fetchAll() as $row) {
            $dbItems[$row['sap_num']] = $row;
        }

        $excelSapNums = array_map('trim', array_column($excelItems, 'sap_num'));

        $counts = [
            'inserted' => 0,
            'updated'  => 0,
            'archived' => 0,
            'errors'   => count($parseErrors),
        ];
        $log = $parseErrors;

        foreach ($excelItems as $data) {
            $sap = trim($data['sap_num']);
            $row = $data['_row'];

            try {
                if (!isset($dbItems[$sap])) {
                    // Case 1: New → INSERT
                    $db->prepare('
                        INSERT INTO items
                            (sap_num, category_symbol, category_num1, category_num2,
                             category_num3, write_year, name, triedenie,
                             personal, sap_position, new_sap_position,
                             closet, shelf, note)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ')->execute([
                        $sap,
                        $data['category_symbol']  ?: null,
                        $data['category_num1']    ?: null,
                        $data['category_num2']    ?: null,
                        $data['category_num3']    ?: null,
                        $data['write_year']       ?: null,
                        $data['name'],
                        $data['triedenie']        ?: null,
                        $data['personal']         ?: null,
                        $data['sap_position']     ?: null,
                        $data['new_sap_position'] ?: null,
                        $data['closet']           ?: null,
                        $data['shelf']            ?: null,
                        $data['note']             ?: null,
                    ]);

                    if (!empty($data['triedenie'])) {
                        $db->prepare('INSERT IGNORE INTO triedenie_settings (triedenie) VALUES (?)')
                            ->execute([$data['triedenie']]);
                    }

                    $counts['inserted']++;
                    $log[] = ['status' => 'inserted', 'row' => $row, 'sap' => $sap, 'name' => $data['name'], 'msg' => ''];

                } else {
                    // Case 2: Exists → UPDATE
                    $db->prepare('
                        UPDATE items SET
                            category_symbol  = ?,
                            category_num1    = ?,
                            category_num2    = ?,
                            category_num3    = ?,
                            write_year       = ?,
                            name             = ?,
                            triedenie        = ?,
                            personal         = ?,
                            sap_position     = ?,
                            new_sap_position = ?,
                            closet           = ?,
                            shelf            = ?,
                            note             = ?,
                            archived         = 0
                        WHERE sap_num = ?
                    ')->execute([
                        $data['category_symbol']  ?: null,
                        $data['category_num1']    ?: null,
                        $data['category_num2']    ?: null,
                        $data['category_num3']    ?: null,
                        $data['write_year']       ?: null,
                        $data['name'],
                        $data['triedenie']        ?: null,
                        $data['personal']         ?: null,
                        $data['sap_position']     ?: null,
                        $data['new_sap_position'] ?: null,
                        $data['closet']           ?: null,
                        $data['shelf']            ?: null,
                        $data['note']             ?: null,
                        $sap,
                    ]);

                    if (!empty($data['triedenie'])) {
                        $db->prepare('INSERT IGNORE INTO triedenie_settings (triedenie) VALUES (?)')
                            ->execute([$data['triedenie']]);
                    }

                    $counts['updated']++;
                    $log[] = ['status' => 'updated', 'row' => $row, 'sap' => $sap, 'name' => $data['name'], 'msg' => ''];
                }

            } catch (Throwable $e) {
                $counts['errors']++;
                $log[] = ['status' => 'error', 'row' => $row, 'sap' => $sap, 'name' => $data['name'], 'msg' => $e->getMessage()];
            }
        }

        // Case 3: In DB but not in Excel → ARCHIVE
        foreach ($dbItems as $sap => $existing) {
            if (!in_array(trim((string)$sap), $excelSapNums, false) && !$existing['archived']) {
                try {
                    $db->prepare('UPDATE items SET archived = 1 WHERE sap_num = ?')->execute([$sap]);
                    $counts['archived']++;
                    $log[] = ['status' => 'archived', 'row' => '—', 'sap' => $sap, 'name' => $existing['name'], 'msg' => 'Nie je v súbore — archivované.'];
                } catch (Throwable $e) {
                    $counts['errors']++;
                    $log[] = ['status' => 'error', 'row' => '—', 'sap' => $sap, 'name' => $existing['name'], 'msg' => $e->getMessage()];
                }
            }
        }

        $pageTitle = 'Výsledky importu';
        ob_start();
        require BASE_PATH . '/src/Views/pages/admin/import.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function createUser(): void
    {
        AuthMiddleware::requireAdmin();

        $name         = trim($_POST['name']         ?? '');
        $email        = trim($_POST['email']        ?? '');
        $password     = $_POST['password']          ?? '';
        $role         = $_POST['role']              ?? 'user';
        $abbreviation = trim($_POST['abbreviation'] ?? '');

        if (!in_array($role, ['user', 'admin'], true)) $role = 'user';

        if ($name === '' || $email === '' || $password === '') {
            Session::flash('error', 'Vyplňte všetky polia.');
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        $db   = Database::getInstance();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $db->prepare('INSERT INTO users (name, email, password, role, abbreviation) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $email, $hash, $role, $abbreviation ?: null]);
            Session::flash('success', "Používateľ $name bol vytvorený.");
        } catch (Throwable $e) {
            Session::flash('error', 'Email už existuje alebo nastala chyba.');
        }

        header('Location: ' . BASE_URL . '/admin');
        exit;
    }

    public function editUser(): void
    {
        AuthMiddleware::requireAdmin();

        $id           = (int)($_POST['id']          ?? 0);
        $name         = trim($_POST['name']         ?? '');
        $email        = trim($_POST['email']        ?? '');
        $abbreviation = trim($_POST['abbreviation'] ?? '');
        $role         = $_POST['role']              ?? 'user';
        $active       = isset($_POST['active'])     ? 1 : 0;
        $password     = $_POST['password']          ?? '';

        if (!in_array($role, ['user', 'admin'], true)) $role = 'user';

        if ($name === '' || $email === '') {
            Session::flash('error', 'Meno a email sú povinné.');
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        $db = Database::getInstance();
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare('UPDATE users SET name=?, email=?, abbreviation=?, role=?, active=?, password=? WHERE id=?')
                    ->execute([$name, $email, $abbreviation ?: null, $role, $active, $hash, $id]);
            } else {
                $db->prepare('UPDATE users SET name=?, email=?, abbreviation=?, role=?, active=? WHERE id=?')
                    ->execute([$name, $email, $abbreviation ?: null, $role, $active, $id]);
            }
            Session::flash('success', "Používateľ $name bol aktualizovaný.");
        } catch (Throwable $e) {
            Session::flash('error', 'Email už existuje alebo nastala chyba.');
        }

        header('Location: ' . BASE_URL . '/admin');
        exit;
    }

    public function createRoom(): void
    {
        AuthMiddleware::requireAdmin();

        $newCode = trim($_POST['new_code'] ?? '');
        $name    = trim($_POST['name']     ?? '');

        if ($newCode === '' || $name === '') {
            Session::flash('error', 'Nový kód a názov sú povinné.');
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        $db = Database::getInstance();
        try {
            $db->prepare('INSERT INTO rooms (code, new_code, name) VALUES (NULL, ?, ?)')
                ->execute([$newCode, $name]);
            Session::flash('success', "Miestnosť $name bola pridaná.");
        } catch (Throwable $e) {
            Session::flash('error', 'Kód už existuje alebo nastala chyba.');
        }

        header('Location: ' . BASE_URL . '/admin');
        exit;
    }

    public function updateRoom(): void
    {
        AuthMiddleware::requireAdmin();

        $id      = (int)($_POST['id']      ?? 0);
        $code    = trim($_POST['code']     ?? '');
        $newCode = trim($_POST['new_code'] ?? '');
        $name    = trim($_POST['name']     ?? '');

        if ($name === '') {
            Session::flash('error', 'Názov je povinný.');
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        $db = Database::getInstance();
        try {
            $db->prepare('UPDATE rooms SET code = ?, new_code = ?, name = ? WHERE id = ?')
                ->execute([$code ?: null, $newCode ?: null, $name, $id]);
            Session::flash('success', 'Miestnosť bola aktualizovaná.');
        } catch (Throwable $e) {
            Session::flash('error', 'Nastala chyba: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/admin');
        exit;
    }
}