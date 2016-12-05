<?php

namespace tourze\workerman\yii2\server;

use tourze\workerman\yii2\async\Task;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

/**
 * TASK服务器
 *
 * @package tourze\workerman\yii2\server
 */
class TaskServer extends Server
{

    public $timeInterval = 0.01;

    /**
     * @inheritdoc
     */
    public function run($config)
    {
        $this->server = new Worker("Text://{$this->host}:{$this->port}");
        foreach ($config as $k => $v)
        {
            $this->server->{$k} = $v;
        }
        $this->server->onWorkerStart = [$this, 'onWorkerStart'];
        $this->server->onMessage = [$this, 'onMessage'];
    }

    public function onWorkerStart($worker)
    {
        //echo __METHOD__ . "\n";
        Timer::add($this->timeInterval, function () {
            $data = Task::popTask();
            Task::runTask($data);
        });
    }

    /**
     * 收到第三方发来的消息，执行任务
     *
     * @param TcpConnection $connection
     * @param mixed $taskData
     */
    public function onMessage($connection, $taskData)
    {
        $rs = Task::runTask($taskData);
        $connection->send((string) $rs);
    }
}
