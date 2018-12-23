<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/4
 * Time: 4:50 PM
 */

namespace bobi\swoole\server;

use bobi\swoole\Swl;
use Yii;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use bobi\swoole\web\Application;
use yii\web\UploadedFile;

class HttpServer extends BaseServer
{
    public $config;

    /**
     * @var Application
     */
    public $app;

    public function getServer()
    {
        $server = new Server($this->host, $this->port);
        $server->set([
            'worker_num'         => $this->workerNum,
            'task_worker_num'    => $this->taskWorkerNum,
            'daemonize'          => $this->daemonize,
            'max_request'        => $this->maxRequest,
            'buffer_output_size' => $this->bufferOutputSize,
        ]);
        $server->on('start', [$this, 'onStart']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('managerStart', [$this, 'onManagerStart']);
        $server->on('workerStart', [$this, 'onWorkerStart']);
        $server->on('task', [$this, 'onTask']);
        $server->on('finish', [$this, 'onFinish']);

        return $server;
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        parent::onWorkerStart($server, $workerId);
        Swl::$server = $server;

        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PWD'] . '/web/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['PWD'] . '/web';
        $_SERVER['DOCUMENT_URI'] = '/index.php';
        Yii::setLogger(Yii::createObject('bobi\swoole\log\Logger'));
        $this->app = new Application($this->config);
    }

    public function onRequest(Request $request, Response $response)
    {
        $this->initData($request, $response);
        $app = clone $this->app;
        $app->run();

        $response->end();
    }

    public function initData(Request $request, Response $response)
    {
        $this->initGetPost($request);
        $this->initServer($request);
        Swl::$response = $response;
    }

    public function initServer(Request $request)
    {
        foreach ($request->header as $key => $value) {
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
        }
        foreach ($request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
        if (!isset($_SERVER['QUERY_STRING'])) {
            $_SERVER['QUERY_STRING'] = '';
        }
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }

    public function initGetPost(Request $request)
    {
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_FILES = $request->files ?? [];
        UploadedFile::reset();
    }
}