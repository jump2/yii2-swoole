<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/9
 * Time: 10:58 PM
 */

namespace bobi\swoole\log;

use Yii;
use yii\log\Dispatcher;

class Logger extends \yii\log\Logger
{
    public function init()
    {
    }

    public function flush($final = false)
    {
        $messages = $this->messages;
        $this->messages = [];
        if ($this->dispatcher instanceof Dispatcher) {
            Yii::$app->task->logDispatch($messages, $final);
        }
    }
}