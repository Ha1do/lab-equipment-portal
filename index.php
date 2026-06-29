<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/config.php';

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$appUrl    = rtrim(APP_URL, '/');
$appPath   = parse_url($appUrl, PHP_URL_PATH) ?: '';
$baseUrl   = $appUrl;
if ($scriptDir && $scriptDir !== '/') {
    if ($appPath !== '' && str_ends_with($appPath, $scriptDir)) {
        $baseUrl = $appUrl;
    } else {
        $baseUrl = $appUrl . $scriptDir;
    }
}
define('BASE_URL', $baseUrl);

spl_autoload_register(function (string $class): void {
    $dirs = [
        BASE_PATH . '/src/Core/',
        BASE_PATH . '/src/Controllers/',
        BASE_PATH . '/src/Models/',
        BASE_PATH . '/src/Middleware/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

Session::start();

$router = new Router();

// --- Auth ---
$router->get( '/login',  [AuthController::class, 'showLogin']);
$router->post('/login',  [AuthController::class, 'login']);
$router->get( '/logout', [AuthController::class, 'logout']);

// --- Profile ---
$router->get( '/profile',        [ProfileController::class, 'show']);
$router->post('/profile/update', [ProfileController::class, 'update']);

// --- Items ---
$router->get( '/',             [ItemController::class, 'index']);
$router->get( '/items',        [ItemController::class, 'index']);
$router->get( '/items/show',   [ItemController::class, 'show']);
$router->post('/items/borrow', [ItemController::class, 'borrow']);
$router->post('/items/return', [ItemController::class, 'return']);
$router->get('/items/export', [ItemController::class, 'export']);
$router->post('/items/comment', [ItemController::class, 'addComment']);
$router->post('/items/location', [ItemController::class, 'updateLocation']);
$router->post('/items/status',   [ItemController::class, 'updateStatus']);
$router->post('/items/vyucba', [ItemController::class, 'toggleVyucba']);

// --- Admin ---
$router->get('/admin', function () {
    AuthMiddleware::requireAdmin();
    $stats      = (new Item())->stats();
    $users      = (new User())->all();
    $triedenie  = Database::getInstance()->query('SELECT * FROM triedenie_settings ORDER BY triedenie')->fetchAll();
    $rooms     = Database::getInstance()->query('SELECT * FROM rooms ORDER BY name')->fetchAll();
    $showNoTriedenie = Database::getInstance()->query("SELECT value FROM app_settings WHERE `key` = 'show_no_triedenie'")->fetchColumn();
    $pageTitle  = 'Panel správcu';
    ob_start();
    require BASE_PATH . '/src/Views/pages/admin/dashboard.php';
    $content = ob_get_clean();
    require BASE_PATH . '/src/Views/layouts/main.php';
});

$router->get( '/admin/import',            [AdminController::class, 'showImport']);
$router->post('/admin/import',            [AdminController::class, 'handleImport']);
$router->post('/admin/triedenie-settings',[AdminController::class, 'saveTriedenie']);
$router->post('/admin/triedenie-toggle', [AdminController::class, 'toggleTriedenie']);
$router->post('/admin/users/create',      [AdminController::class, 'createUser']);
$router->post('/admin/users/edit', [AdminController::class, 'editUser']);
$router->post('/admin/rooms/create', [AdminController::class, 'createRoom']);
$router->post('/admin/rooms/update', [AdminController::class, 'updateRoom']);
$router->dispatch();