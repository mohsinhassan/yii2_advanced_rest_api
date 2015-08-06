<?php

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),    
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'basePath' => '@app/modules/v1',
            'class' => 'api\modules\v1\Module'
        ]
    ],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
        ],
        /*'siteApi' => [
            'class' => 'mongosoft\soapclient\Client',
            'url' => 'http://124.29.246.107:1530/Service.asmx?wsdl',
            'options' => [
                'cache_wsdl' => WSDL_CACHE_NONE,
            ],
        ],*/
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/user',   // our country api rule,
                    'tokens' => [
                                    '{id}' => '<id:\\w+>'
                                ],
                    'extraPatterns' => [
                        'POST login' => 'login',
                        'POST logout' => 'logout',
                        'POST changepass' => 'changepassword',
                        'POST cgtpre' => 'cgtpre',
                        'POST cgtpost' => 'cgtpost',
                        'POST forgotpass' => 'forgotpass',
                        'POST codeverify' => 'codeverify',
                        'POST salesentitygroup' => 'salesentitygroup',
                        'POST commissionmf' => 'commissionstructure_mf',
                        'POST commissionfl' => 'commissionstructure_fl',
                        'POST customers' => 'customers_data',
                        'POST loadearned' => 'loadearned',
                        'POST customerreport' => 'customerreport',
                        'POST balancedetail' => 'balancedetail',
                        'POST aumrep' => 'aumrep',
                        'POST dalrep' => 'dalrep',
                        'POST cprnrep' => 'cprnrep',
                        'POST allgroupmembers' => 'allgroupmembers',
                        'PUT changedistributor' => 'changedistributor',
                        'POST groupcustomers' => 'groupcustomers',
                        'POST groupcustomerfundaum' => 'groupcustomerfundaum',
                        'POST alloverfundaum' => 'alloverfundaum',
                        'POST transactiontrack' => 'transactiontrack',
                        'POST transactionkeyword' => 'transactionkeyword',
                        'POST inflowoutflow' => 'inflowoutflow',
                        'POST notifications' => 'notifications',
                        'PUT notificationread' => 'notificationread',
                        'POST allnotifications' => 'allnotifications',
                        'POST contactus' => 'contactus',
                        'POST profilepicture' => 'profilepicture',
                        'POST grouptransaction' => 'grouptransaction',
                        'POST test' => 'test',
                    ],
                ],
            ],
        ]
    ],
    'params' => $params,
];



