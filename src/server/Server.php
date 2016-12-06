<?php

namespace tourze\workerman\yii2\server;

use tourze\workerman\yii2\Application;
use tourze\workerman\yii2\async\Task;
use tourze\workerman\yii2\Container;
use tourze\workerman\yii2\globalData\Client;
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
     * @param array $config
     * @param string $host
     * @param bool $isDebug
     */
    public static function runAppGlobalData($config, $host, $isDebug)
    {
        $globalConfig = ArrayHelper::getValue($config, 'global');
        if ( ! $globalConfig)
        {
            $globalConfig = [
                'port' => 2207,
            ];
        }
        $globalDataHost = ArrayHelper::getValue($globalConfig, 'host', $host);
        $globalDataPort = ArrayHelper::getValue($globalConfig, 'port', 2207);
        $task = new GlobalServer([
            'app' => Application::$workerApp,
            'host' => $globalDataHost,
            'port' => $globalDataPort,
            'debug' => $isDebug,
        ]);
        $task->run($globalConfig);
        Application::$globalData = new Client("{$globalDataHost}:{$globalDataPort}");
    }

    /**
     * @param $config
     * @param $host
     * @param $port
     * @param $root
     * @param $isDebug
     */
    public static function runAppHttpServer($config, $host, $port, $root, $isDebug)
    {
        $serverConfig = ArrayHelper::getValue($config, 'server');
        if ($serverConfig)
        {
            /** @var HttpServer $server */
            $server = new HttpServer([
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
     * @param $config
     * @param $host
     * @param $isDebug
     */
    public static function runAppTaskServer($config, $host, $isDebug)
    {
        $taskConfig = ArrayHelper::getValue($config, 'task');
        if ( ! $taskConfig)
        {
            $taskConfig = [
                'port' => 2208,
            ];
        }
        $task = new TaskServer([
            'app' => Application::$workerApp,
            'host' => ArrayHelper::getValue($taskConfig, 'host', $host), // 默认跟http服务同一个主机名
            'port' => ArrayHelper::getValue($taskConfig, 'port', 2208), // 默认任务使用的2208端口
            'debug' => $isDebug,
        ]);
        $task->run($taskConfig);
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

        // 是否开启调试
        $isDebug = ArrayHelper::getValue($config, 'debug', false);

        $root = ArrayHelper::getValue($config, 'root');
        $host = ArrayHelper::getValue($config, 'host');
        $port = ArrayHelper::getValue($config, 'port');

        // 全局数据
        self::runAppGlobalData($config, $host, $isDebug);

        // 执行 HTTP SERVER
        self::runAppHttpServer($config, $host, $port, $root, $isDebug);

        // 执行 TASK SERVER
        self::runAppTaskServer($config, $host, $isDebug);

        Worker::runAll();
    }
}
