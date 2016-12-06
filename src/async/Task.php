<?php

namespace tourze\workerman\yii2\async;

use Yii;
use yii\redis\Connection;

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
     * @return Connection
     */
    public static function getRedis()
    {
        return Yii::$app->get('redis');
    }

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
        $data = self::packData($function, $params);
        self::getRedis()->rpush(self::$taskQueueKey, $data);
        $taskId = self::getRedis()->incr(self::$taskCountKey);
        return $taskId;
    }

    /**
     * 返回一条task数据
     *
     * @return string
     */
    public static function popTask()
    {
        return self::getRedis()->lpop(self::$taskQueueKey);
    }

    /**
     * 执行任务
     *
     * @param string $data
     * @param int $taskId
     * @return mixed
     */
    public static function runTask($data, $taskId = null)
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
