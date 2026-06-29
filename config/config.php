<?php
// Скопируй в config.php и заполни своими данными
// config.php добавлен в .gitignore — не попадёт в репозиторий

// --- База данных ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'nazov_databazy');
define('DB_USER', 'pouzivatel');
define('DB_PASS', 'heslo');
define('DB_CHARSET', 'utf8mb4');


// --- Приложение ---
define('APP_NAME', 'nazov');

// Полный URL до папки с index.php (без слеша в конце)
// Например: https://majetok.student10-ws2-ktpe.website.tuke.sk
define('APP_URL', 'https://vas-domen.sk');

define('APP_ENV', 'development'); // 'development' или 'production'

// --- Сессия ---
define('SESSION_NAME',     'uni_portal_sess');
define('SESSION_LIFETIME', 7200);

// --- Отображение ошибок ---
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/storage/logs/error.log');
}