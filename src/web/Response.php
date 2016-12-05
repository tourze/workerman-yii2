<?php

namespace tourze\workerman\yii2\web;

use tourze\workerman\yii2\Application;
use tourze\workerman\yii2\web\formatter\JsonResponseFormatter;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * 内部实现response
 *
 * @property ConnectionInterface $connection
 */
class Response extends \yii\web\Response
{

    /**
     * @var ConnectionInterface
     */
    protected $_connection;

    /**
     * @return ConnectionInterface
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

    /**
     * @inheritdoc
     */
    protected function defaultFormatters()
    {
        return ArrayHelper::merge(parent::defaultFormatters(), [
            self::FORMAT_JSON => JsonResponseFormatter::className(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function sendHeaders()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendHeaders();
            return;
        }

        $headers = $this->getHeaders();
        if ($headers)
        {
            foreach ($headers as $name => $values)
            {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                if (count($values) == 1)
                {
                    $value = array_shift($values);
                    Http::header("$name: $value");
                }
                else
                {
                    /** @var array $values */
                    foreach ($values as $value)
                    {
                        //echo "$name: $value\n";
                        Http::header("$name: $value");
                    }
                }
            }
        }

        $statusCode = $this->getStatusCode();
        Http::header("HTTP/1.1 {$statusCode} " . Response::$httpStatuses[$statusCode]);
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendCookies();
            return;
        }

        if ($this->getCookies() === null)
        {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation)
        {
            if ($request->cookieValidationKey == '')
            {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie)
        {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey))
            {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            Http::setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * @inheritdoc
     */
    protected function sendContent()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendContent();
            return;
        }
        if ($this->content === null)
        {
            $this->getConnection()->send('');
        }
        else
        {
            $this->getConnection()->send($this->content);
        }
    }
}
