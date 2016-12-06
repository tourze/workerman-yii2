<?php

namespace demo\controllers;

use demo\models\User;
use tourze\workerman\yii2\async\Task;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * API相关的测试控制器
 *
 * @package demo\controllers
 */
class ApiController extends Controller
{

    /**
     * 返回json
     *
     * @return array
     */
    public function actionJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['time' => time(), 'str' => 'hello'];
    }

    /**
     * 查找所有用户
     */
    public function actionGetUsers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $rs = [];

        /** @var User[] $users */
        $users = User::find()->all();
        foreach ($users as $user)
        {
            $rs[] = $user->toArray();
        }
        return $rs;
    }

    /**
     * 测试定时任务
     */
    public function actionTask()
    {
        //Task::pushTask("var_dump", [time()]);
        $id = Task::pushTask("time");
        //Task::pushTask('sleep', [10]);
        return $id;
    }
}
