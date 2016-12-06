<?php

namespace tourze\workerman\yii2\redis;

class Connection extends \yii\redis\Connection
{

    /**
     * @inheritdoc
     */
    public function open()
    {
        // 链接还生效的话，就不重连了
        if ($this->getIsActive())
        {
            return;
        }
        parent::open();
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        // 不主动关闭redis连接
    }

    /**
     * 真实关闭链接
     */
    public function realClose()
    {
        parent::close();
    }
}
