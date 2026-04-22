<?php
return [
    'sms_templats' => [
        'filename' => 'Sms_Template.csv',
        'headers'  => [
            'Name',
            'Title',
            'Type(0 sms,1 mms,2 email)',
            'Content',
            'Sort',
        ],
    ],
    'linkman' => [
        'filename' => 'Contact_Template.csv',
        'headers' => [
            'Last Name',
            'First Name',
            'Phone',
            'Email',
            'Birthday',
        ]
    ],
    'group_linkman' => [
        'filename' => 'Group_Template.csv',
        'headers' => [
            'Last Name',
            'First Name',
            'Phone',
            'Email',
            'Birthday',
        ]
    ],
    // 会员导入模板
    'members' => [
        'filename' => 'members_import_template.csv',
        'headers'  => [
            'First Name',
            'Last Name',
            'Membership Number',
            'Phone Number',
            'Member Email',
            'Member Birthday (YYYY-MM-DD)',
            'Member Balance (USD)',
            'Member Points',
            'Remarks',
            'Referrer',
        ],
        'notes' => [
            'Fields marked with * are required.',
            'Birthday format must be YYYY-MM-DD.',
        ],
        'examples' => [
            ['John', 'Doe', 'M0001', '(123)456-7890', 'john@example.com', '1990-01-20', '100.50', '200', 'VIP', 'Alice'],
        ],
        'bom' => true,
        // 可选：分隔符等
        // 'delimiter' => ',',
        // 'enclosure' => '"',
        // 'escape'    => '\\',
        // 'eol'       => "\r\n",
    ],

    // 商品导入模板（示例）
    'goods' => [
        'filename' => 'goods_import_template.csv',
        'headers'  => ['SKU', 'Name', 'Price (USD)', 'Tax Code', 'Category', 'Stock', 'Remarks'],
        'notes'    => ['Price accepts numbers like 12.99 (no currency symbol).'],
        'examples' => [
            ['SKU-1001', 'Shampoo 500ml', '12.99', 'TAX001', 'Hair', '120', ''],
        ],
        'bom' => true,
    ],
];
