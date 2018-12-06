<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/4
 * Time: 17:17
 */

namespace bobi\swoole\exception;

use yii\base\Exception;

class InvalidSwooleServerException extends Exception
{
    public function getName()
    {
        return 'Invalid Swoole Server';
    }
}