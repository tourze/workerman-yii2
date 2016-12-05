# workerman-yii2

## 项目说明

我在另外一个项目 https://github.com/tourze/swoole-yii2 中实现了 swoole+yii2 的组合.

我最早接触的是 workerman. 第一次有 "PHP原来也可以这么炫" 这种感觉, 也是从阅读 workerman 的实现开始的.

个人认为, 不管你是 swoole 还是 workerman 的用户, 既然研究 PHP 已经到这种需要考虑高性能场景的时候了, 你就不应该仅仅满足使用, 而应该尝试下了解你所选方案的具体实现了.

swoole 用 C 写的, 看不懂... 那现在有用 PHP 实现的 workerman 了, 总不能说看不懂了吧? :)

## 适用人群

1. 对 PHP 项目有高性能要求的开发者
2. 熟悉 swoole 或 workerman
3. 阅读过 http://doc3.workerman.net/
4. 对于Yii2的核心概念和实现, 有一定掌握.

## 已经完成的工作

* Http Server 的实现
* Request 组件的兼容处理
* Response 组件的兼容处理
* Session 组件的兼容处理
* 增加异步任务助手类
* Debug 模块的兼容处理
* Container 支持实例持久化
* Db 组件的自动重连
* 压力测试文档

## 进行中的工作

* swoole 任务投递的优化
* 增加单元测试
* 兼容swoole

## 使用方法

首先执行 `composer require tourze/workerman-yii2`

下面的配置描述, 基本上就是基于 https://github.com/yiisoft/yii2-app-advanced 这个官方 DEMO 来说明的.
建议在阅读前先大概了解下这个项目.

### console配置

在 `console/config/main.php` 中加入类似以下的代码:

```
    'id' => 'app-console',
    'controllerNamespace' => 'console\controllers',
    'controllerMap' => [
        // 在下面指定定义command控制器
        'server' => \tourze\workerman\yii2\commands\ServerController::className(),
    ],
```

此时执行 `./yii`, 应该可以在底部看到 server 相关命令.

### frontend/backend配置

我们建议 frontend 部分使用 swoole 来运行, backend 部分依然使用已有的 php-fpm 模式来运行.

使用本项目, frontend 和 backend 的运行方式会有所变更.

在以前的方式中, 我们会在入口文件 include 所有配置, 然后 new Application 使系统运行起来.
在现在的新方式中, 我们的配置会在服务运行起来时就加载到内存, 节省了上面加载配置的时间.

我们需要在 `console/config/params.php` 中加入类似以下的代码:

```
<?php
return [
    'workermanHttp' => [
        'frontend' => [
            'host' => '127.0.0.1',
            'port' => '6677',
            'root' => realpath(__DIR__ . '/../../frontend/web'),
            // 在这里定义一些常用的可以常驻与内存的组件
            'persistClasses' => [
                'dmstr\web\AdminLteAsset',
                'dmstr\widgets\Alert',
            ],
            // bootstrap文件, 只会引入一次
            'bootstrapFile' => [
                __DIR__ . '/../../common/config/aliases.php',
                __DIR__ . '/../../admin/config/aliases.php',
            ],
            // Yii的配置文件, 只会引入一次
            'configFile' => [
                __DIR__ . '/../../common/config/main.php',
                __DIR__ . '/../../frontend/config/main.php'
            ],
            // 有一些模块比较特殊, 无法实现Refreshable接口, 此时唯有在这里指定他的类名
            'bootstrapRefresh' => [
                'xxx\backend\Bootstrap',
            ],
            // 配置参考 http://doc3.workerman.net/worker-development/property.html
            'server' => [
                'count' => 4,
            ],
            'logFile' => __DIR__ . '/../runtime/workerman.log',
        ],
    ],
];
```

配置好后, 我们执行 `./yii server/http frontend`, 就可以启动 swoole 服务器了.
