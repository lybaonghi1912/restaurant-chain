<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Allow: POST');http_response_code(405);exit('Vui lòng dùng nút Đăng xuất.'); }
require_csrf();
$_SESSION=[];
$params=session_get_cookie_params();
setcookie(session_name(),'',[
    'expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],
    'httponly'=>true,'samesite'=>'Lax',
]);
session_destroy();
redirect_to('login.php?logged_out=1');
