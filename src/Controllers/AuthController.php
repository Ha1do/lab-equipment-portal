<?php

class AuthController {
    public function showLogin(): void {
        $pageTitle = 'Prihlásenie';
        ob_start();
        require BASE_PATH . '/src/Views/pages/auth/login.php';
        $content = ob_get_clean();
        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    public function login(): void {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if (!$user || !$userModel->verifyPassword($password, $user['password'])) {
            Session::flash('error', 'Nesprávny email alebo heslo.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if (!$user['active']) {
            Session::flash('error', 'Váš účet bol deaktivovaný. Kontaktujte administrátora.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        Session::set('user_id',   $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_role', $user['role']);
        session_regenerate_id(true);

        header('Location: ' . BASE_URL . '/');
        exit;
    }

    public function logout(): void {
        Session::destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}