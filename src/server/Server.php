<?php

namespace tourze\workerman\yii2\server;

use tourze\workerman\yii2\Application;
use tourze\workerman\yii2\async\Task;
use Workerman\Worker;
use yii\base\Component;

/**
 * 基础的server实现
 *
 * @package tourze\workerman\yii2\server
 */
abstract class Server extends Component
{

    /**
     * @var string 服务器名称
     */
    public $name = 'workerman-server';

    /**
     * @var string 进程文件路径
     */
    public $pidFile;

    /**
     * @var Worker
     */
    public $server;

    /**
     * @var Application
     */
    public $app;

    /**
     * 设置进程标题
     *
     * @param string $name
     */
    protected function setProcessTitle($name)
    {
        @cli_set_process_title($name . ': master');
    }

    /**
     * 运行服务器
     *
     * @param string $app
     */
    abstract public function run($app);

    /**
     * 投递任务
     *
     * @param mixed $data
     * @param int $dst_worker_id
     * @return bool
     */
    public function task($data, $dst_worker_id = -1)
    {
        Task::runTask($data, $dst_worker_id);
    }
}
