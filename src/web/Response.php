<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/6
 * Time: 8:27 AM
 */

namespace bobi\swoole\web;

use Yii;
use yii\base\InvalidConfigException;

class Response extends \yii\web\Response
{
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_SEND, [$this, 'beforeSend']);
    }

    public function beforeSend($event)
    {
        $response = $event->sender;
        $data = $response->data;

        if (isset($data['code'])) {
            if ($data['code'] != 0) {
                $status = $data['code'];
            } elseif (isset($data['status'])) {
                $status = $data['status'];
            } else {
                $status = $response->statusCode;
            }

            if ($status > 1) {
                $status *= -1;
            }

            if (Yii::$app->canGetProperty('beginTime', true, false)) {
                $beginTime = Yii::$app->beginTime;
            } else {
                $beginTime = YII_BEGIN_TIME;
            }
            $response->data = [
                'time_taken' => bcsub(microtime(true), $beginTime, 8),
                'status' => $status,
                'status_txt' => $data['message'] ?? $response->statusText,
                'results' => []
            ];

            $response->statusCode = 200;
            if (YII_DEBUG) {
                $response->data['results'] = $data;
            }
        }
    }

    /**
     * Sends the response headers to the client.
     */
    public function sendHeaders()
    {
        $headers = $this->getHeaders();
        if ($headers) {
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                foreach ($values as $value) {
                    Yii::$app->swooleHttpResponse->header($name, $value);
                }
            }
        }
        $statusCode = $this->getStatusCode();
        Yii::$app->swooleHttpResponse->status($statusCode);
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if ($this->getCookies() === null) {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            Yii::$app->swooleHttpResponse->cookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * Sends the response content to the client.
     */
    protected function sendContent()
    {
        if ($this->stream === null) {
            Yii::$app->swooleHttpResponse->write($this->content);

            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                Yii::$app->swooleHttpResponse->write(fread($handle, $chunkSize));
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                Yii::$app->swooleHttpResponse->write(fread($this->stream, $chunkSize));
            }
            fclose($this->stream);
        }
    }
}