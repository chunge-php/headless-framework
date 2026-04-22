<?php
return [
    'enable' => true,
    'origin' => ['*'],
    'allow_headers' => ['Authorization', 'Content-Type', 'X-Requested-With', 'Accept', 'Origin','Accept-Language'],
    'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'expose_headers' => [],
    'max_age' => 86400,
    'credentials' => true,
];