<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/9
 * Time: 10:58 PM
 */

namespace bobi\swoole\log;

use Yii;
use yii\helpers\VarDumper;
use yii\log\Dispatcher;

class Logger extends \yii\log\Logger
{
    /**
     * @var bool 是否开启异步记录日志
     */
    public $async = true;

    public function init()
    {
    }

    public function flush($final = false)
    {
        if (false === $this->async) {
            parent::flush($final);
        } else {
            $messages = $this->messages;
            if ($this->dispatcher instanceof Dispatcher) {
                //messages 存在匿名函数
                $messages = array_map([$this, 'format'], $this->messages);
                Yii::$app->task->logDispatch($messages, $final);
            }
        }
        $this->messages = [];
    }

    public function format($message)
    {
        $text = &$message[0];
        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string) $text;
            } else {
                $text = VarDumper::export($text);
            }
        }
        return $message;
    }
}