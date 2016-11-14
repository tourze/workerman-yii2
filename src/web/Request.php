<?php

namespace tourze\workerman\yii2\web;

use Workerman\Connection\ConnectionInterface;

/**
 * @property ConnectionInterface $connection
 */
class Request extends \yii\web\Request
{

    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function setConnection($connection)
    {
        $this->_connection = $connection;
    }
}
