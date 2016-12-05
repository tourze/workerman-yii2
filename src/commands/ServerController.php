<?php

namespace tourze\workerman\yii2\commands;

use tourze\workerman\yii2\server\Server;
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

        Server::runApp($app);
    }
}
