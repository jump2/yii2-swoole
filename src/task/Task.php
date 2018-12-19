<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/12/17
 * Time: 10:59 PM
 */

namespace bobi\swoole\task;

use bobi\swoole\Swl;

class Task extends \yii\base\Component
{
    public $taskMap = [];

    public function __call($name, $arguments)
    {
        if (!isset($this->taskMap[$name])) {
            throw new \Exception("Task '$name' does not exist");
        }
        array_unshift($arguments, $this->taskMap[$name]);
        Swl::$server->task($arguments);
    }
}