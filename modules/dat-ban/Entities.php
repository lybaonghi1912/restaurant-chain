<?php
declare(strict_types=1);
namespace Fbn\Reservations;
require_once __DIR__.'/models/ChiNhanh.php';
class_alias(ChiNhanh::class,__NAMESPACE__.'\\ChiNhanhEntity');
require_once __DIR__.'/models/Ban.php';
class_alias(Ban::class,__NAMESPACE__.'\\BanEntity');
require_once __DIR__.'/models/DatBan.php';
class_alias(DatBan::class,__NAMESPACE__.'\\DatBanEntity');
