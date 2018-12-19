<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/12/17
 * Time: 10:54 PM
 */

namespace bobi\swoole\task;


class logDispatch
{
    public function execute($app, $messages, $final)
    {
        echo 'abc';
        $app->get('log')->dispatch($messages, $final);
    }
}