<?php
/**
 * User: aaron.woo <707230686@qq.com>
 * Date: 2018/11/4
 * Time: 4:50 PM
 */

namespace bobi\swoole\server;

use Yii;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use bobi\swoole\web\Application;
use yii\web\UploadedFile;

class HttpServer extends BaseServer
{
    protected $config;

    public function getServer()
    {
        $server = new Server($this->host, $this->port);
        $server->set([
            'worker_num'         => $this->workerNum,
            'daemonize'          => $this->daemonize,
            'max_request'        => $this->maxRequest,
            'buffer_output_size' => $this->bufferOutputSize,
        ]);
        $server->on('start', [$this, 'onStart']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('managerStart', [$this, 'onManagerStart']);
        $server->on('workerStart', [$this, 'onWorkerStart']);

        return $server;
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        parent::onWorkerStart($server, $workerId);

        $this->config = require __DIR__ . '/../../../../../config/web.php';
    }

    public function onRequest(Request $request, Response $response)
    {
        $this->initData($request);
        $this->config['swooleHttpResponse'] = $response;
        $app = new Application($this->config);
        $app->set('response', Yii::createObject([
            'class'  => \bobi\swoole\web\Response::class,
            'format' => 'json'
        ]));
        $app->run();

        $response->end();
    }

    public function initData(Request $request)
    {
        Yii::setLogger(null);
        $this->initGetPost($request);
        $this->initServer($request);
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
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PWD'] . '/web/index.php';
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['PWD'] . '/web';
        $_SERVER['DOCUMENT_URI'] = '/index.php';
    }

    public function initGetPost(Request $request)
    {
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_FILES = $request->files ?? [];
        UploadedFile::reset();
    }
}