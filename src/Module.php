<?php

namespace mraminrzn\aggrid;

use yii\base\Module as BaseModule;

/**
 * AG Grid Module
 */
class Module extends BaseModule
{
    public $grids = [];
    public $enableCors = true;
    public $corsConfig = [
        'Origin' => ['*'],
        'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        'Access-Control-Request-Headers' => ['*'],
        'Access-Control-Allow-Credentials' => true,
        'Access-Control-Max-Age' => 86400,
    ];
    public $enableCsrf = false;
    public $controllerNamespace = 'mraminrzn\aggrid\controllers';

    public function init()
    {
        parent::init();
    }
}