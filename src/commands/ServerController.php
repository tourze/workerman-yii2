<?php

namespace tourze\workerman\yii2\commands;

use tourze\workerman\yii2\server\HttpServer;
use Workerman\Worker;
use Yii;
use yii\console\Controller;

class ServerController extends Controller
{

    /**
     * Run workerman http server
     *
     * @param string $app Running app
     * @throws \yii\base\InvalidConfigException
     */
    public function actionHttp($app)
    {
        // 重新组装一次 $argv
        global $argv;
        unset($argv[0]);
        unset($argv[2]);
        $argv = array_values($argv);
        //print_r($argv);
        //return;

        /** @var HttpServer $server */
        $server = new HttpServer;
        $server->run($app);

        Worker::runAll();
    }
}
