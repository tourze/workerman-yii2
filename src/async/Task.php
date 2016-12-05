<?php

namespace tourze\workerman\yii2\async;

use tourze\workerman\yii2\Application;

/**
 * 使用
 *
 * @package tourze\workerman\yii2\async
 */
class Task
{

    /**
     * @var string
     */
    public static $taskQueueKey = 'task_key';

    /**
     * @var string
     */
    public static $taskCountKey = 'task_count';

    /**
     * 打包数据
     *
     * @param string $function
     * @param array $params
     * @return string
     */
    public static function packData($function, $params)
    {
        $data = serialize([$function, $params]);
        return $data;
    }

    /**
     * 解包数据
     *
     * @param string $data
     * @return mixed
     */
    public static function unpackData($data)
    {
        return (array) unserialize($data);
    }

    /**
     * 增加异步执行任务
     *
     * @param string $function
     * @param array  $params
     * @return int
     * @throws \tourze\workerman\yii2\async\Exception
     */
    public static function pushTask($function, $params = [])
    {
        if ( ! Application::$globalData)
        {
            return 0;
        }
        $data = self::packData($function, $params);
        Application::$globalData->push(self::$taskQueueKey, $data);
        $taskId = Application::$globalData->increment(self::$taskCountKey);
        return $taskId;
    }

    /**
     * 返回一条task数据
     *
     * @return string
     */
    public static function popTask()
    {
        if ( ! Application::$globalData)
        {
            return '';
        }
        return Application::$globalData->pop(self::$taskQueueKey);
    }

    /**
     * 执行任务
     *
     * @param string $data
     * @return mixed
     */
    public static function runTask($data)
    {
        $data = self::unpackData($data);
        $function = array_shift($data);
        $params = (array) array_shift($data);
        if ($function)
        {
            return call_user_func_array($function, $params);
        }
        return null;
    }
}
