<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/app.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
start_app_session();
set_exception_handler(function (Throwable $error): void {
    error_log('FBN: ' . $error->getMessage());
    http_response_code(503);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Tạm thời chưa kết nối được</title><body><h1>Tạm thời chưa kết nối được hệ thống</h1><p>Kiểm tra MySQL trong Laragon và cấu hình database của dự án rồi thử lại.</p></body></html>';
});
