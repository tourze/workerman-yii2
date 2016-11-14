<?php

namespace tourze\workerman\yii2\debug;

use tourze\workerman\yii2\Application;
use tourze\workerman\yii2\web\Response;
use Workerman\Protocols\HttpCache;
use Yii;

/**
 * Class RequestPanel
 *
 * @package tourze\workerman\yii2\debug
 */
class RequestPanel extends \yii\debug\panels\RequestPanel
{

    /**
     * @inheritdoc
     */
    public function save()
    {
        $rs = parent::save();
        if ( ! Application::$workerApp)
        {
            return $rs;
        }

        // 直接从workerman的http解析器里面拿
        $headers = HttpCache::$header;
        $rs['responseHeaders'] = array_merge($rs['responseHeaders'], $headers);
        return $rs;
    }
}
