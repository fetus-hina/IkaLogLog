<?php

declare(strict_types=1);

use app\components\web\Session;
use yii\web\Cookie;

return [
    'class' => Session::class,
    'cacheLimiter' => 'nocache',
    'cookieParams' => [
        'httponly' => true,
        'samesite' => Cookie::SAME_SITE_STRICT,
        'secure' => (bool)preg_match(
            '/(?:^|\.)stat\.ink$/i',
            $_SERVER['HTTP_HOST'] ?? ''
        ),
    ],
    'name' => YII_ENV_DEV ? 'SESSID_DEVENV' : 'PHPSESSID',
    'timeout' => 86400,
];
