<?php
declare(strict_types=1);
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
require_once __DIR__.'/controllers/DatBanController.php';
(new \Fbn\Reservations\DatBanController(db()))->hienThiTrang($pageKey);
