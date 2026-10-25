<?php
declare(strict_types=1);

// Quyền theo vai trò, dùng cho cả trang và bộ xử lý POST/API của các module sau này.
return [
    'CHU_CHUOI' => [
        'floor.view','pos.view','checkout.view','kds.view',
        'menu.view','menu.manage','inventory.view','transfer.view','transfer.approve',
        'staff.view','staff.manage','staff.transfer.approve','payroll.view','payroll.manage',
        'bom.view','bom.manage','branches.view','branches.manage',
        'reports.view','reports.chain','accounts.view','accounts.manage',
    ],
    'QUAN_LY' => [
        'floor.view','pos.view','checkout.view','kds.view','inventory.view',
        'staff.view','staff.manage','staff.transfer.request','payroll.view','payroll.manage',
        'reports.view','accounts.view','accounts.manage',
    ],
    'PHUC_VU' => ['floor.view','reservation.manage','pos.view','order.manage','checkout.view','payment.manage'],
    'BEP' => ['kds.view','kitchen.manage','ingredient.shortage.report'],
    'KHO' => ['inventory.view','inventory.manage','transfer.view','transfer.receive','requisition.create'],
];
