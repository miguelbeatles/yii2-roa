<?php

namespace tecnocen\roa\actions;

use Yii;
use yii\base\Model;
use yii\web\ServerErrorHttpException;

class Create extends Action
{
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * Creates a new model.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
        $request = Yii::$app->getRequest();
        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);
        $model->load($request->queryParams(), '');
        $this->checkAccess($model, $request->getQueryParams());
        $model->load($request->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $response->getHeaders()->set('Location', $model->getSelfLink());
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException(
                'Failed to create the object for unknown reason.'
            );
        }
        return $model;
    }
}
