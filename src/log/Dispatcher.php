<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/12/23
 * Time: 6:54 PM
 */

namespace bobi\swoole\log;


class Dispatcher extends \yii\log\Dispatcher
{
    /**
     * Dispatches the logged messages to [[targets]].
     * @param array $messages the logged messages
     * @param bool $final whether this method is called at the end of the current application
     */
    public function dispatch($messages, $final)
    {
        $targetErrors = [];
        foreach ($this->targets as $target) {
            if ($target->enabled) {
                try {
                    $target->collect($messages, $final);
                    //在application最后一次调用该方法的时候，target的$messages需要清空
                    if ($final) {
                        $target->messages = [];
                    }
                } catch (\Exception $e) {
                    $target->enabled = false;
                    $targetErrors[] = [
                        'Unable to send log via ' . get_class($target) . ': ' . ErrorHandler::convertExceptionToVerboseString($e),
                        Logger::LEVEL_WARNING,
                        __METHOD__,
                        microtime(true),
                        [],
                    ];
                }
            }
        }

        if (!empty($targetErrors)) {
            $this->dispatch($targetErrors, true);
        }
    }
}