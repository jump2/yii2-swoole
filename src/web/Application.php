<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/6
 * Time: 8:18 AM
 */

namespace bobi\swoole\web;

use bobi\swoole\Swl;
use Swoole\Http\Response as SwooleHttpResponse;
use yii\base\ExitException;
use yii\base\UserException;

class Application extends \yii\web\Application
{
    /**
     * Gets the application start timestamp.
     */
    public $beginTime;

    public function init()
    {
        parent::init();

        $this->beginTime = microtime(true);
    }

    public function run()
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;
        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        } catch (\Exception $e) {
            $this->getErrorHandler()->handleException($e);
            return 0;
        } finally {
            \Yii::getLogger()->flush(true);
        }
    }

    /**
     * Terminates the application.
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * before terminating the application.
     * @param int $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode
     */
    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ?: $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        }

        Swl::$response->end();
    }

    /**
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        if (true) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                exit(1);
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }

    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request'      => ['class' => 'bobi\swoole\web\Request'],
            'response'     => ['class' => 'bobi\swoole\web\Response'],
            'session'      => ['class' => 'yii\web\Session'],
            'user'         => ['class' => 'yii\web\User'],
            'errorHandler' => ['class' => 'bobi\swoole\web\ErrorHandler'],
        ]);
    }
}