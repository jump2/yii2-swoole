<?php

namespace bobi\swoole\console\controllers;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ServerController extends Controller
{
    /**
     * 构建Web Server
     *
     * 事例如下
     *
     * ```
     * swoole server start   # 启动Web服务
     * swoole server stop    # 停止Web服务
     * swoole server reload  # 重载Web服务
     * swoole server restart # 重启Web服务
     * ```
     * @param string $action #取值范围[start, stop, reload, restart], 默认start
     * @return int
     */
    public function actionIndex($action = 'start')
    {
        try {
            \Yii::$app->httpServer->run($action);
            $this->stdOutput($action);
        } catch (\Exception $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    protected function stdOutput($action)
    {
        switch ($action) {
            case 'start':
                $message = 'Web server has started successfully.';
                break;
            case 'reload':
                $message = 'Web server has reloaded successfully.';
                break;
            case 'stop':
                $message = 'Web server has stop successfully.';
                break;
            case 'restart':
                $message = 'Web server has restarted successfully.';
                break;
            default:
                $message = 'Unknown param: ' . $action;
                $this->stderr($message . PHP_EOL, Console::FG_RED);
                return;
        }

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);
    }
}
