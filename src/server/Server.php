<?php

namespace tourze\workerman\yii2\server;

use tourze\workerman\yii2\Application;
use tourze\workerman\yii2\async\Task;
use tourze\workerman\yii2\Container;
use tourze\workerman\yii2\log\Logger;
use Workerman\Worker;
use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;

/**
 * 基础的server实现
 *
 * @package tourze\workerman\yii2\server
 */
abstract class Server extends Object
{

    /**
     * @var array 当前配置文件
     */
    public $config = [];

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var string 服务器名称
     */
    public $name = 'workerman-server';

    /**
     * @var string 进程文件路径
     */
    public $pidFile;

    /**
     * @var string 主机名
     */
    public $host;

    /**
     * @var int 监听端口
     */
    public $port;

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
     * 运行
     *
     * @param array $config
     */
    abstract public function run($config);

    /**
     * 投递任务
     *
     * @param string $data
     * @param int    $dst_worker_id
     * @return mixed
     */
    public function task($data, $dst_worker_id = -1)
    {
        return Task::runTask($data, $dst_worker_id);
    }

    /**
     * 初始化APP
     *
     * @param array $config
     */
    protected static function prepareApp($config)
    {
        // 关闭Yii2自己实现的异常错误
        defined('YII_ENABLE_ERROR_HANDLER') || define('YII_ENABLE_ERROR_HANDLER', false);

        // 为Yii分配一个新的DI容器
        if (isset($config['persistClasses']))
        {
            Container::$persistClasses = ArrayHelper::merge(Container::$persistClasses, $config['persistClasses']);
            Container::$persistClasses = array_unique(Container::$persistClasses);
        }
        Yii::$container = new Container;

        $yiiConfig = self::loadConfigFile((array) ArrayHelper::getValue($config, 'configFile'));
        if (isset($config['bootstrapRefresh']))
        {
            $yiiConfig['bootstrapRefresh'] = $config['bootstrapRefresh'];
        }

        $root = ArrayHelper::getValue($config, 'root');
        if ( ! isset($yiiConfig['components']['assetManager']['basePath']))
        {
            $yiiConfig['components']['assetManager']['basePath'] = $root . '/assets';
            $yiiConfig['components']['assetManager']['baseUrl'] =  '/assets';
        }
        $yiiConfig['aliases']['@webroot'] = $root;
        $yiiConfig['aliases']['@web'] = '/';

        Application::$workerApp = new Application($yiiConfig);
        Yii::setLogger(new Logger());
        Application::$workerApp->setRootPath($root);
    }

    /**
     * 加载配置文件
     *
     * @param array $configFile
     * @return array
     */
    public static function loadConfigFile($configFile)
    {
        $yiiConfig = [];
        foreach ($configFile as $file)
        {
            $yiiConfig = ArrayHelper::merge($yiiConfig, include $file);
        }
        return $yiiConfig;
    }

    /**
     * 加载bootstrap文件
     *
     * @param array $bootstrapFile
     */
    public static function loadBootstrapFile($bootstrapFile)
    {
        foreach ($bootstrapFile as $file)
        {
            require $file;
        }
    }

    /**
     * 运行HTTP服务器
     *
     * @param array $config
     */
    public static function runAppHttpServer($config)
    {
        $isDebug = ArrayHelper::getValue($config, 'debug', false);
        $root = ArrayHelper::getValue($config, 'root');
        $serverConfig = (array) ArrayHelper::getValue($config, 'server');
        if ($serverConfig)
        {
            $host = ArrayHelper::getValue($serverConfig, 'host', '127.0.0.1');
            $port = ArrayHelper::getValue($serverConfig, 'port', 6677);
            unset($serverConfig['host'], $serverConfig['port']);
            /** @var HttpServer $server */
            $server = new HttpServer([
                'xhprofLink' => ArrayHelper::getValue($config, 'xhprofLink'),
                'app' => Application::$workerApp,
                'host' => $host,
                'port' => $port,
                'debug' => $isDebug,
                'root' => $root,
            ]);
            $server->run($serverConfig);
        }
    }

    /**
     * 运行任务处理服务器
     *
     * @param array $config
     */
    public static function runAppTaskServer($config)
    {
        // 是否开启调试
        $isDebug = ArrayHelper::getValue($config, 'debug', false);
        $taskConfig = (array) ArrayHelper::getValue($config, 'task');
        if ($taskConfig)
        {
            $host = ArrayHelper::getValue($taskConfig, 'host', '127.0.0.1');
            $port = ArrayHelper::getValue($taskConfig, 'port', 6678);
            unset($taskConfig['host'], $taskConfig['port']);
            $task = new TaskServer([
                'app' => Application::$workerApp,
                'host' => $host,
                'port' => $port,
                'debug' => $isDebug,
            ]);
            $task->run($taskConfig);
        }
    }

    /**
     * 执行指定的APP配置
     *
     * @param string $app
     */
    final public static function runApp($app)
    {
        // 加载配置信息
        $config = (array) Yii::$app->params['workermanHttp'][$app];

        // 加载文件和一些初始化配置
        self::loadBootstrapFile((array) ArrayHelper::getValue($config, 'bootstrapFile'));

        // 准备APP信息
        self::prepareApp($config);

        // 日志文件
        Worker::$logFile = ArrayHelper::getValue($config, 'logFile');

        // 执行 HTTP SERVER
        self::runAppHttpServer($config);

        // 执行 TASK SERVER
        self::runAppTaskServer($config);

        Worker::runAll();
    }
}
