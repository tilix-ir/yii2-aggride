<?php

namespace mraminrzn\aggrid\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\Cors;
use yii\filters\ContentNegotiator;
use mraminrzn\aggrid\AgGridDataProvider;
use mraminrzn\aggrid\Module;

/**
 * Grid Controller
 */
class GridController extends Controller
{
    public function behaviors()
    {
        $behaviors = [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];

        /** @var Module $module */
        $module = $this->module;

        if ($module->enableCors) {
            $behaviors['corsFilter'] = [
                'class' => Cors::class,
                'cors' => $module->corsConfig,
            ];
        }

        return $behaviors;
    }

    public function beforeAction($action)
    {
        /** @var Module $module */
        $module = $this->module;
        
        $this->enableCsrfValidation = $module->enableCsrf;
        
        return parent::beforeAction($action);
    }

    public function actionData($grid)
    {
        /** @var Module $module */
        $module = $this->module;

        if (!isset($module->grids[$grid])) {
            throw new NotFoundHttpException("Grid '{$grid}' not found.");
        }

        $request = Yii::$app->request;
        $params = $request->isPost ? $request->getBodyParams() : $request->getQueryParams();

        if (!isset($params['startRow']) || !isset($params['endRow'])) {
            throw new BadRequestHttpException('Missing required parameters: startRow, endRow');
        }

        try {
            $gridClass = $module->grids[$grid];
            $gridConfig = Yii::createObject($gridClass);
            
            $provider = AgGridDataProvider::fromConfig($gridConfig);
            $data = $provider->getData($params);
            
            return [
                'success' => true,
                'rows' => $data['rows'],
                'lastRow' => $data['lastRow'],
            ];
            
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            
            return [
                'success' => false,
                'error' => YII_DEBUG ? $e->getMessage() : 'An error occurred while fetching data.',
            ];
        }
    }

    public function actionColumns($grid)
    {
        /** @var Module $module */
        $module = $this->module;

        if (!isset($module->grids[$grid])) {
            throw new NotFoundHttpException("Grid '{$grid}' not found.");
        }

        try {
            $gridClass = $module->grids[$grid];
            $gridConfig = Yii::createObject($gridClass);
            
            $provider = AgGridDataProvider::fromConfig($gridConfig);
            $columns = $provider->getColumns();
            
            return [
                'success' => true,
                'columns' => $columns,
            ];
            
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            
            return [
                'success' => false,
                'error' => YII_DEBUG ? $e->getMessage() : 'An error occurred.',
            ];
        }
    }

    public function actionExport($grid)
    {
        /** @var Module $module */
        $module = $this->module;

        if (!isset($module->grids[$grid])) {
            throw new NotFoundHttpException("Grid '{$grid}' not found.");
        }

        $request = Yii::$app->request;
        $params = array_merge($request->getBodyParams(), $request->getQueryParams());
        
        $params['startRow'] = 0;
        $params['endRow'] = PHP_INT_MAX;

        try {
            $gridClass = $module->grids[$grid];
            $gridConfig = Yii::createObject($gridClass);
            
            $provider = AgGridDataProvider::fromConfig($gridConfig);
            $data = $provider->getData($params);

            $filename = $grid . '_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            if (!empty($data['rows'])) {
                fputcsv($output, array_keys($data['rows'][0]));
                
                foreach ($data['rows'] as $row) {
                    $flatRow = array_map(function($value) {
                        if (is_array($value)) {
                            return json_encode($value);
                        }
                        return $value;
                    }, $row);
                    
                    fputcsv($output, $flatRow);
                }
            }
            
            fclose($output);
            Yii::$app->end();
            
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            throw new \yii\web\ServerErrorHttpException('Export failed.');
        }
    }
}