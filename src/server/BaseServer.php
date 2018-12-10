<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/03
 * Time: 15:27
 */

namespace bobi\swoole\server;

use bobi\swoole\exception\InvalidSwooleServerException;
use Swoole\Server;
use yii\base\Component;

abstract class BaseServer extends Component
{
    public $host;

    /**
     * @var integer
     * 端口号
     */
    public $port;

    public $workerNum = 4;

    public $daemonize = 0;

    public $serverName = 'BobiServer';

    public $maxRequest = 500;

    public $bufferOutputSize = 2 * 1024 *1024;

    protected $server;

    /** @var IdWorker */
    protected $worker;

    /**
     * 获取server
     *
     * @return Server
     */
    abstract public function getServer();

    /**
     * Server启动在主进程的主线程回调此函数
     * 已创建了manager进程
     * 已创建了worker子进程
     * 已监听所有TCP/UDP/UnixSocket端口，但未开始Accept连接和请求
     * 已监听了定时器
     *
     * @param Server $server
     */
    public function onStart(Server $server)
    {
        swoole_set_process_name($this->getServerMasterName());
    }

    /**
     * 此事件在Server正常结束时发生
     * 已关闭所有Reactor线程、HeartbeatCheck线程、UdpRecv线程
     * 已关闭所有Worker进程、Task进程、User进程
     * 已close所有TCP/UDP/UnixSocket监听端口
     * 已关闭主Reactor
     *
     * @param Server $server
     */
    public function onShutdown(Server $server)
    {

    }

    /**
     * 此事件在Worker进程/Task进程启动时发生
     *
     * @param Server $server
     * @param int $workerId
     */
    public function onWorkerStart(Server $server, int $workerId)
    {
        if($workerId < $server->setting['worker_num']) {
            swoole_set_process_name($this->getServerWorkerName());
        } else {
            swoole_set_process_name($this->getServerTaskName());
        }
    }

    /**
     * 此事件在worker进程终止时发生, 在此函数中可以回收worker进程申请的各类资源
     *
     * @param Server $server
     * @param int $workerId
     */
    public function onWorkerStop(Server $server, int $workerId)
    {
        echo $this->getServerWorkerName() . $workerId . ' stopped' . PHP_EOL;
    }

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, int $fd, int $reactorId)
    {

    }

    /**
     * 在task_worker进程内被调用
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
     * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，
     * 当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task。
     *
     * 函数执行时遇到致命错误退出，或者被外部进程强制kill，当前的任务会被丢弃，但不会影响其他正在排队的Task
     *
     * 在onTask函数中 return字符串，表示将此内容返回给worker进程。worker进程中会触发onFinish函数，表示投递的task已完成。
     *
     * @param Server $server
     * @param int $taskId 任务ID，由swoole扩展内自动生成，用于区分不同的任务
     * $taskId和$srcWorkerId组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
     * @param int $srcWorkerId 源自哪个worker进程投递的task
     * @param $data 任务的内容
     */
    public function onTask(Server $server, int $taskId, int $srcWorkerId, $data)
    {

    }

    /**
     * 当worker进程投递的任务在task_worker中完成时，task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程。
     *
     * task进程的onTask事件中没有调用finish方法或者return结果，worker进程不会触发onFinish
     * 执行onFinish逻辑的worker进程与下发task任务的worker进程是同一个进程
     *
     * @param Server $server
     * @param int $taskId
     * @param $data
     */
    public function onFinish(Server $server, int $taskId, $data)
    {

    }

    /**
     * 当worker/task_worker进程发生异常后会在Manager进程内回调此函数。
     * 主要用于报警和监控，一旦发现Worker进程异常退出，那么很有可能是遇到了致命错误或者进程CoreDump。
     * 通过记录日志或者发送报警的信息来提示开发者进行相应的处理。
     *
     * @param Server $server
     * @param int $workerId 异常进程的编号
     * @param int $workerPid 异常进程的ID
     * @param int $exitCode 退出的状态码，范围是 1 ～255
     * @param int $signal 进程退出的信号
     */
    public function onWorkerError(Server $server, int $workerId, int $workerPid, int $exitCode, int $signal)
    {

    }

    /**
     * 当管理进程启动时调用它
     * manager进程中不能添加定时器
     * manager进程中可以调用sendMessage接口向其他工作进程发送消息
     *
     * @param Server $server
     */
    public function onManagerStart(Server $server)
    {
        swoole_set_process_name($this->getServerManagerName());
    }

    /**
     * 当管理进程结束时调用它
     *
     * @param Server $server
     */
    public function onManagerStop(Server $server)
    {
        echo $this->getServerManagerName() . ' stopped' . PHP_EOL;
    }

    /**
     * 获取master进程名称
     *
     * @return string
     */
    protected function getServerMasterName() : string
    {
        return $this->serverName . '-Master';
    }

    /**
     * 获取manager进程名称
     *
     * @return string
     */
    protected function getServerManagerName() : string
    {
        return $this->serverName . '-Manager';
    }

    /**
     * 获取worker进程名称
     *
     * @return string
     */
    protected function getServerWorkerName() : string
    {
        return $this->serverName . '-Worker';
    }

    /**
     * 获取task进程名称
     *
     * @return string
     */
    protected function getServerTaskName() : string
    {
        return $this->serverName . '-Task';
    }

    public function __call($name, $params)
    {
        throw new \Exception('Unknown param: ' . $name);
    }

    /**
     * This method should be called in order to start|stop|reload the server.
     *
     * @return void
     */
    public function run($action)
    {
        return $this->$action();
    }

    /**
     * 停止服务
     *
     * @throws \Exception
     */
    protected function stop()
    {
        $masterPid = exec('ps -ef | grep ' . $this->getServerMasterName() . " | grep -v 'grep ' | awk '{print $2}'");
        if (empty($masterPid)) {
            throw new \Exception('Server ' . $this->serverName . ' not run');
        }

        // Send stop signal to master process.
        $masterPid && posix_kill($masterPid, SIGTERM);
        // Timeout.
        $timeout = 40;
        $startTime = time();
        // Check master process is still alive?
        while (1) {
            $masterIsAlive = $masterPid && posix_kill($masterPid, 0);
            if ($masterIsAlive) {
                // Timeout?
                if (time() - $startTime >= $timeout) {
                    throw new \Exception('Server ' . $this->serverName . 'stop failed');
                }
                // Waiting amoment.
                usleep(10000);
                continue;
            }
            // Stop success.
            break;
        }
    }

    /**
     * 重载服务
     *
     * @throws \Exception
     */
    protected function reload()
    {
        $masterPid = exec('ps -ef | grep ' . $this->getServerMasterName() . " | grep -v 'grep ' | awk '{print $2}'");
        $managerPid = exec('ps -ef | grep ' . $this->getServerManagerName() . " | grep -v 'grep ' | awk '{print $2}'");
        if (empty($masterPid)) {
            throw new \Exception('Server ' . $this->serverName . ' not run');
        }
        posix_kill($managerPid, SIGUSR1);
    }

    /**
     * 重启服务
     *
     * @throws \Exception
     */
    protected function restart()
    {
        $masterPid = exec('ps -ef | grep ' . $this->getServerMasterName() . " | grep -v 'grep ' | awk '{print $2}'");
        if (empty($masterPid)) {
            throw new \Exception('Server ' . $this->serverName . ' not run');
        }
        $this->stop();
        $this->start();
    }

    /**
     * 启动服务
     *
     * @throws \Exception
     */
    public function start()
    {
        $masterPid = exec('ps -ef | grep ' . $this->getServerMasterName() . " | grep -v 'grep ' | awk '{print $2}'");
        if (!empty($masterPid)) {
            throw new \Exception($this->serverName . ' server already running');
        }
        $this->server = $this->getServer();
        $this->server->start();
    }
}