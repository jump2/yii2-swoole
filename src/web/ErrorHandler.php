<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/10
 * Time: 2:40 PM
 */

namespace bobi\swoole\web;

use bobi\swoole\Swl;
use Yii;
use yii\base\ErrorException;
use yii\base\ExitException;

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * Register this error handler.
     */
    public function register()
    {
        ini_set('display_errors', false);
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will simply raise an [[ErrorException]].
     *
     * @param int $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     * @return bool whether the normal error handler continues.
     *
     * @throws ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            $exception = new ErrorException($message, $code, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                }
            }

            $this->exception = $exception;
            // disable error capturing to avoid recursive errors while handling exceptionss
            $this->unregister();

            // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
            // HTTP exceptions will override this value in renderException()
            Swl::$response->status(500);

            $this->logException($exception);
            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);
            if (!YII_ENV_TEST) {
                \Yii::getLogger()->flush(true);
            }

            $this->exception = null;
        }
    }

    /**
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler.
     *
     * @param \Exception $exception the exception that is not caught
     */
    public function handleException($exception)
    {
        $this->exception = $exception;
        // disable error capturing to avoid recursive errors while handling exceptions
        $this->unregister();

        // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
        // HTTP exceptions will override this value in renderException()
        Swl::$response->status(500);

        $this->logException($exception);
        if ($this->discardExistingOutput) {
            $this->clearOutput();
        }
        $this->renderException($exception);
        if (!YII_ENV_TEST) {
            \Yii::getLogger()->flush(true);
        }

        $this->exception = null;
    }
}