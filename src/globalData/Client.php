<?php

namespace tourze\workerman\yii2\globalData;

class Client extends \GlobalData\Client
{

    /**
     * 进队列
     *
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public function push($key, $value)
    {
        return array_push($this->{$key}, $value);
    }

    /**
     * 出队列
     *
     * @param string $key
     * @return mixed
     */
    public function pop($key)
    {
        return array_shift($this->{$key});
    }
}
