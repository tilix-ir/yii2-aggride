<?php

namespace mraminrzn\aggrid;

use yii\base\BootstrapInterface;

/**
 * Bootstrap class for AG Grid extension
 */
class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if (!isset($app->modules['aggrid'])) {
            $app->setModule('aggrid', [
                'class' => 'mraminrzn\aggrid\Module',
            ]);
        }

        if ($app instanceof \yii\web\Application) {
            $app->getUrlManager()->addRules([
                'GET,POST aggrid/<grid:\w+>' => 'aggrid/grid/data',
                'GET aggrid/<grid:\w+>/columns' => 'aggrid/grid/columns',
                'POST aggrid/<grid:\w+>/export' => 'aggrid/grid/export',
            ], false);
        }
    }
}