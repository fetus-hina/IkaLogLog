<?php
return [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'enableStrictParsing' => true,
    'rules' => [
        'login'         => 'user/login',
        'logout'        => 'user/logout',
        'profile'       => 'user/profile',
        'register'      => 'user/register',
        'u/<screen_name:\w+>/<battle:\d+>' => 'show/battle',
        'u/<screen_name:\w+>/<battle:\d+>/edit' => 'show/edit-battle',
        'u/<screen_name:\w+>/<battle:\d+>.atom' => 'ostatus/battle-atom',
        'u/<screen_name:\w+>/<id_from:\d+>-<id_to:\d+>' => 'show/user',
        'u/<screen_name:\w+>/stat/report/<year:\d+>/<month:\d+>' => 'show/user-stat-report',
        'u/<screen_name:\w+>/stat/report/<year:\d+>' => 'show/user-stat-report',
        'u/<screen_name:\w+>/stat/<by:[\w-]+>' => 'show/user-stat-<by>',
        'u/<screen_name:\w+>' => 'show/user',
        'u/<screen_name:\w+>.<lang:[\w-]+>.<type:rss|atom>' => 'feed/user',
        'u/<screen_name:\w+>-ostatus.atom' => 'ostatus/feed',
        'fest/<region:\w+>/<order:\d+>' => 'fest/view',
        'entire/weapons/<weapon:\w+>/<rule:\w+>' => 'entire/weapon',
        'entire/weapons/<weapon:\w+>' => 'entire/weapon',
        'entire/users/combined-<b32name:[A-Za-z2-7]+>' => 'entire/combined-agent',
        'entire/users/<b32name:[A-Za-z2-7]+>' => 'entire/agent',
        'stages/<year:\d+>/<month:\d+>' => 'stage/month',
        'stages/<map:[a-z]+>/<rule:[a-z0-9_]+>.json' => 'stage/map-history-json',
        'stages/<map:[a-z]+>/<rule:[a-z0-9_]+>' => 'stage/map-detail',
        'stages/<map:[a-z]+>' => 'stage/map',
        'stages' => 'stage/index',
        'api/v1/<action:[\w-]+>' => 'api-v1/<action>',
        'GET,HEAD api/v2/battle' => 'api-v2-battle/index',
        'GET,HEAD api/v2/battle/<id:\d+>' => 'api-v2-battle/view',
        'POST api/v2/battle'     => 'api-v2-battle/create',
        'OPTIONS api/v2/battle'  => 'api-v2-battle/options',
        'OPTIONS api/v2/battle/<id:\d+>' => 'api-v2-battle/options',
        'api/v2/<action:[\w-]+>' => 'api-v2/<action>',
        'api/internal/<action:[\w-]+>' => 'api-internal/<action>',
        // 'well-known/host-meta' => 'ostatus/host-meta', // TMP
        // 'well-known/webfinger' => 'ostatus/webfinger', // TMP
        '.well-known/host-meta' => 'ostatus/host-meta',
        '.well-known/webfinger' => 'ostatus/webfinger',
        'api/salmon/<screen_name:\w+>' => 'ostatus/salmon',
        'api/ostatus/subscribe' => 'ostatus/subscribe',
        'downloads' => 'download-stats/index',
        '<action:[\w-]+>'  => 'site/<action>',
        '<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
        'robots.txt'    => 'site/robots',
        ''              => 'site/index',
    ],
];
