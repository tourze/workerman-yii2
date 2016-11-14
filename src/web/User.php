<?php

namespace tourze\workerman\yii2\web;

use tourze\workerman\yii2\Application;
use Yii;

class User extends \yii\web\User
{

    /**
     * @inheritdoc
     */
    protected function renewAuthStatus()
    {
        if (Application::$workerApp)
        {
            // 手动open
            Yii::$app->session->open();
        }
        parent::renewAuthStatus();
    }
}
