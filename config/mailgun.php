<?php

return [
    'domain' => getenv('MAILGUN_DOMAIN'),
    'secret' => getenv('MAILGUN_SECRET'),
    'endpoint' => getenv('MAILGUN_ENDPOINT'),
    'from' => getenv('MAIL_FROM_ADDRESS')
];
