<?php

namespace tourze\workerman\yii2\db;

class Connection extends \yii\db\Connection
{

    /**
     * @var string
     */
    public $commandClass = 'tourze\workerman\yii2\db\Command';
}
