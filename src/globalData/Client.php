<?php

namespace tourze\workerman\yii2\globalData;

/**
 * 扩展client
 *
 * @package tourze\workerman\yii2\globalData
 */
class Client extends \GlobalData\Client
{

    /**
     * 进队列
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function push($key, $value)
    {
        do
        {
            if ( ! isset($this->$key))
            {
                $this->$key = [];
            }
            $oldValue = $newValue = $this->$key;
            $newValue[] = $value;
        }
        while ( ! $this->cas($key, $oldValue, $newValue));
    }

    /**
     * 出队列
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function pop($key, $default = null)
    {
        if ( ! isset($this->$key))
        {
            return $default;
        }
        do
        {
            $oldValue = $newValue = $this->$key;
            if (empty($oldValue))
            {
                return $default;
            }
            $value = array_shift($newValue);
        }
        while ( ! $this->cas($key, $oldValue, $newValue));
        return $value;
    }
}
