<?php

namespace tourze\workerman\yii2\server;

use tourze\workerman\yii2\globalData\Server as GlobalDataServer;

class GlobalServer extends Server
{

    /**
     * @inheritdoc
     */
    public function run($config)
    {
        $global = new GlobalDataServer($this->host, $this->port);
    }
}
