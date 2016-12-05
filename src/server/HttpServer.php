<?php

namespace tourze\workerman\yii2\server;

use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http;
use Workerman\Worker;
use Yii;
use yii\base\ErrorException;
use yii\helpers\FileHelper;

/**
 * HTTP服务器
 *
 * @package tourze\workerman\yii2\server
 */
class HttpServer extends Server
{

    /**
     * @var string 缺省文件名
     */
    public $indexFile = 'index.php';

    /**
     * @var string
     */
    public $root;

    /**
     * @var string
     */
    public $sessionKey = 'JSESSIONID';

    /**
     * @inheritdoc
     */
    public function run($config)
    {
        $this->server = new Worker("http://{$this->host}:{$this->port}");
        foreach ($config as $k => $v)
        {
            $this->server->{$k} = $v;
        }

        $this->server->onWorkerStart = [$this, 'onWorkerStart'];
        $this->server->onWorkerReload = [$this, 'onWorkerReload'];
        $this->server->onWorkerStop = [$this, 'onWorkerStop'];
        $this->server->onMessage = [$this, 'onMessage'];

        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    /**
     * Worker启动时触发
     *
     * @param Worker $worker
     */
    public function onWorkerStart($worker)
    {
        $this->setProcessTitle($this->name . ': worker');

        $_SERVER = [
            'QUERY_STRING'         => '',
            'REQUEST_METHOD'       => '',
            'REQUEST_URI'          => '',
            'SERVER_PROTOCOL'      => '',
            'SERVER_NAME'          => '',
            'HTTP_HOST'            => '',
            'HTTP_USER_AGENT'      => '',
            'HTTP_ACCEPT'          => '',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_COOKIE'          => '',
            'HTTP_CONNECTION'      => '',
            'REMOTE_ADDR'          => '',
            'REMOTE_PORT'          => '0',
        ];

        $file = $this->root . '/' . $this->indexFile;
        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['DOCUMENT_ROOT'] = $this->root;
        $_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = '/' . $this->indexFile;
        $file = $this->root . '/' . $this->indexFile;
        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['DOCUMENT_ROOT'] = $this->root;
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = '/' . $this->indexFile;

        $this->app = clone $this->app;
        $this->app->setServer($this->server);
        $this->app->prepare();
    }

    /**
     * @param Worker $worker
     */
    public function onWorkerReload($worker)
    {
    }

    /**
     * @param Worker $worker
     */
    public function onWorkerStop($worker)
    {
    }

    /**
     * 执行请求
     *
     * @param ConnectionInterface $connection
     * @param mixed               $data
     * @return bool|void
     */
    public function onMessage($connection, $data)
    {
//        $id = posix_getpid();
//        echo "id: $id\n";
//        $t = '<pre>';
//        $t .= print_r($_SERVER, true);
//        $t .= '</pre>';
//        return $connection->send($t);

        //xdebug_start_trace();

        if ($this->debug)
        {
            xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);
        }

        $_SERVER['REQUEST_SCHEME'] = 'http';
        $urlInfo = parse_url($_SERVER['REQUEST_URI']);
        //var_dump($urlInfo);
        $uri = $_SERVER['ORIG_PATH_INFO'] = $urlInfo['path'];
        $file = $this->root . $uri;

        if ($uri != '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) != 'php')
        {
            // 非php文件, 最好使用nginx来输出
            Http::header('Content-Type: ' . FileHelper::getMimeTypeByExtension($file));
            Http::header('Content-Length: ' . filesize($file));
            $connection->close(file_get_contents($file));
            return;
        }
        else
        {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            $_SERVER['REQUEST_TIME'] = time();

            // 使用clone, 原型模式
            // 所有请求都clone一个原生$app对象
            $app = clone $this->app;
            Yii::$app =& $app;
            $app->setConnection($connection);
            $app->setRequest(clone $this->app->getRequest());
            $app->setResponse(clone $this->app->getResponse());
            //$app->getResponse()->clear();
            $app->setView(clone $this->app->getView());
            //$app->getView()->clear();
            $app->setSession(clone $this->app->getSession());
            $app->setUser(clone $this->app->getUser());

            try
            {
                $app->run();
                $app->afterRun();
            }
            catch (ErrorException $e)
            {
                $app->afterRun();
                if ($this->debug)
                {
                    echo (string) $e;
                    echo "\n";
                    $connection->send('');
                }
                else
                {
                    $app->getErrorHandler()->handleException($e);
                }
            }
            catch (\Exception $e)
            {
                $app->afterRun();
                if ($this->debug)
                {
                    echo (string) $e;
                    echo "\n";
                    $connection->send('');
                }
                else
                {
                    $app->getErrorHandler()->handleException($e);
                }
            }
            // 还原环境变量
            Yii::$app = $this->app;
            unset($app);
        }

        //xdebug_stop_trace();
        //xdebug_print_function_stack();

        if ($this->debug)
        {
            $xhprofData = xhprof_disable();
            $xhprofRuns = new \XHProfRuns_Default();
            $runId = $xhprofRuns->save_run($xhprofData, 'xhprof_test');
            echo "http://127.0.0.1/xhprof/xhprof_html/index.php?run=" . $runId . '&source=xhprof_test'."\n";
        }
    }
}
