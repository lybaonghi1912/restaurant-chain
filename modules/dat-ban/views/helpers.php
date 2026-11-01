<?php
declare(strict_types=1);
if(!defined('APP_ENTRY')){http_response_code(404);exit;}

function reservation_value(array $input,string $key,string $default=''): string
{
    return e(is_string($input[$key]??null)?$input[$key]:$default);
}
function reservation_status(string $status): string
{
    return \Fbn\Reservations\DatBanController::STATES[$status]??$status;
}
function reservation_time(?string $time): string
{
    return $time?date('d/m/Y H:i',strtotime($time)):'Chưa xác định';
}
function reservation_notice(?string $error,?array $flash,array $fields=[]): void
{
    if($error){echo '<div class="res-notice error" role="alert"><strong>'.e($error).'</strong>';foreach($fields as $label)echo '<div>'.e($label).'</div>';echo '</div>';}
    if($flash)echo '<div class="res-notice success" role="status">'.e($flash['message']).'</div>';
}
function reservation_url(array $extra=[]): string
{
    return url('index.php?'.http_build_query(['page'=>'floor']+$extra));
}
