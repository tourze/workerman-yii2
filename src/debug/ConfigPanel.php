<?php

namespace tourze\workerman\yii2\debug;
use tourze\workerman\yii2\Application;

/**
 * 覆盖原有的Config面板
 *
 * @package tourze\workerman\yii2\debug
 */
class ConfigPanel extends \yii\debug\panels\ConfigPanel
{

    /**
     * @inheritdoc
     */
    public function getPhpInfo()
    {
        if ( ! Application::$workerApp)
        {
            return parent::getPhpInfo();
        }
        // 此时获取得到的PHPINFO是没有混杂html的, 这里加上个pre标签使其显示正常点
        $info = parent::getPhpInfo();
        $info = "<pre>{$info}</pre>";
        return $info;
    }
}
