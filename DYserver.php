<?php
/**
***
*** 接收client端发送的数据
***
***
**/
define('DY_SYS_VERSION' , '1.0');
include_once("DyHttpServer.php"); 
include_once("RedisClient.php"); 

class DyServer{
	static 		$DyHttpServer 						= '';
    static 		$BaseProcessName 					= 'dy_server_';
	private static $pidFile							= '/var/dy_server_pid';
    public 		$response 							= null;
	private 	$confFile							= './conf/httpConf.php';
	static 		$confFileReviseTime 				= 0;
	public 		$httpConf 							= [];
	public 		$swoole_http_config					= [];
	public 		$redis								= null;
    function __construct ()
    {
        if(isset($argv[1])){
            self::$pidFile = $argv[1];
        }
		
    }
	
	function access_log()
    {
        $args    = func_get_args();
        if(self::$DyHttpServer){
            self::$DyHttpServer->task($args);
        }else{
            throw new \Exception( '服务创建失败' );
        }
        unset($args);
    }
	 /**
     * Fatal Error的捕获
     *
     * @codeCoverageIgnore
     */
    public function handleFatal ()
    {
        $error = error_get_last();
        if (!isset($error['type'])) return;
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $message = $error['message'];
        $file    = $error['file'];
        $line    = $error['line'];
        $log     = "\n异常提示：$message ($file:$line)\nStack trace:\n";
        $trace   = debug_backtrace(1);


        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
        }
        $this->access_log($log);
        if($this->response){
            $this->response->status( 500 );
            $this->response->end( '程序异常' );
        }
        unset($this->response);
		
    }
	 /**
     * 加载配置文件
     *
     * @param bool $forceLoad 是否强制加载
     * @return bool
     */
    function LoadHttpConf ($forceLoad = FALSE)
    {
        try{
            clearstatcache();
            if (file_exists($this->confFile)) {
                if (filemtime($this->confFile) > self::$confFileReviseTime || $forceLoad) {
                    $this->httpConf = include $this->confFile;
                    return TRUE;
                } else {
                    $this->httpConf=self::$DyHttpServer->getHttpConf();
                }
            } else {
                throw new Exception($this->confFile.',file of web config is not find'.PHP_EOL);
            }

            return FALSE;
        }catch(\Exception $e){
            echo '异常：'. $e->getMessage().PHP_EOL;
        }
    }
	/**
     * 设置工作进程
     *
     * @param $num
     */
    function setWorkerNum ($num)
    {
        $this->setHttpConf('worker_num', $num);
    }

    /**
     * 保存用户自定义配置数据
     *
     * @param $data
     * @return bool
     */
    function saveHttpConf ($data)
    {
        return \lib\web::writeHttpConfig($data);
    }
	   /**
     * 设置服务器配置
     *
     * @param $key
     * @param $vale
     */
    function setHttpConf ($key, $vale)
    {
        $this->swoole_http_config[$key] = $vale;
    }
	    /**
     * 获取数组中的值
     *
     * @param      $key
     * @param      $arr
     * @param null $default
     * @return null
     */
    static function getArrVal ($key, $arr, $default = null)
    {
        $index = strpos($key, '.');
        $last  = null;
        if ($index === FALSE) {
            return !empty($arr[$key])?$arr[$key]:false;
        } else {
            $arg  = substr($key, 0, $index);
            $last = substr($key, $index + 1, strlen($key));
        }
        if (isset($arr[$arg])) {
            if ($last && is_array($arr[$arg])) {
                return self::getArrVal($last, $arr[$arg], $default);
            } else {
                return  $arr[$arg];
            }
        }
        unset($index, $last, $arg, $key);
        return $default;

    }
	
	 /**
     * 获取域名
     *
     * @param \swoole_http_request $request
     *
     * @return bool|string
     */
    function getHost(swoole_http_request &$request)
    {
        $host=self::getArrVal('host',$request->header);
        if($host){
            $has = strpos( $host , ':' );
            if($has){
                $host=substr($host,0,$has);
                return $host;
            }else{
                return $host;
            }
        }
        $this->ExceptionLog('获取域名与端口失败',$request);
        return false;
    }
	/**
     * 获取服务端口
     *
     * @param \swoole_http_request $request
     *
     * @return null
     */
    function getServerPort( \swoole_http_request $request )
    {
        return self::getArrVal( 'server_port' , $request->server,80);
    }
	
	/**
     * 保存到redis
     *
     * 
     *
     * 
     */
	function saveToRedis($data,$client_ip){
		$res = json_decode($data,true);
		if(isset($res['uid'])&&!empty($res['uid'])){
				$res['ip']	= $client_ip;
				$this->redis->lpush('datalist',array(json_encode($res)), function($result, $success) {
					if($result!='OK'){
						$this->access_log('save error:'.$result);
					}
				});
		}else{
			$this->access_log('error='.$data);
		}
		
	}
	/**
     * 请求处理
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     * @return bool
     */
    function request( \swoole_http_request $request , \swoole_http_response $response )
    {
        unset($_SERVER);
        unset($_COOKIE);
        
        try{
		   
           $domain		=	$this->getHost($request);
           $hostPort 	= 	$domain.':'.$this->getServerPort($request);
		   $this->access_log(print_r($request->server,true));
		  $client_ip = $request->server['remote_addr'];
		   if(function_exists('wd_decrypt')){
			   $postdata = $request->post;
			   if(!empty($postdata)){
				   foreach($postdata as $key=>$val){
					 $data = wd_decrypt(wd_hextostr($key));	 
					 $this->access_log(print_r($data,true));
					 
					 $this->saveToRedis($data,$client_ip);
					  
				   }
			   }else{
				     $this->access_log(print_r($postdata,true));
			   }
		   }else{
			   $this->access_log('function wd_decrypt no exist');
		   }
           
           $m_st = memory_get_usage();
		   
           $response->end("");


        }catch(Exception $e){
            $this->ExceptionLog( '执行异常'.$e->getMessage());
            $this->responseErrCode($response,500);
        }

    }
	
	public function run(){
		cli_set_process_title(self::$BaseProcessName.'Master');
		register_shutdown_function(array($this, 'handleFatal'));
		if($this->LoadHttpConf(true)){
			$this->redis = new RedisClient(self::getArrVal('redis_ip', $this->httpConf,'127.0.0.1'),self::getArrVal('redis_port', $this->httpConf,6379));
			$listen_ip 			= self::getArrVal('listen_ip', $this->httpConf);
			$port 				= self::getArrVal('port', $this->httpConf);
			self::$DyHttpServer = new DyHttpServer($listen_ip, $port);
			//工作进程数量
            $this->setWorkerNum(self::getArrVal('worker_process', $this->httpConf, 8));
			self::$DyHttpServer->setHttpConf($this->httpConf);
            $this->setHttpConf('task_worker_num', self::getArrVal('task_worker_num',$this->httpConf,4));
            $this->setHttpConf('log_file', self::getArrVal('log_file',$this->httpConf,'/var/log/dyserver.log'));
            $this->setHttpConf('daemonize', self::getArrVal('daemonize',$this->httpConf,false));
            $this->setHttpConf('server','dyserver');

            self::$DyHttpServer->set($this->swoole_http_config);
            //回调事件
            self::$DyHttpServer->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
                $this->request($request, $response);
            });

            self::$DyHttpServer->on('ManagerStart', function () {
                cli_set_process_title(self::$BaseProcessName . 'Manager');
            });
			
            self::$DyHttpServer->on('Task', function (\swoole_server $serv, $task_id, $from_id, $data) {

				
				$args    = $data;
                $logPath = self::getArrVal('access_log', $this->httpConf).date('Y-m-d').'/';
				
                $file    = $logPath.date('Ymd').'.log';
                if (!is_dir($logPath)) {
                    mkdir($logPath, 0755, TRUE);
                }

                $content = date('Y-m-d H:i:s') . ' worker_id='.$from_id.': ';
                if (is_string($args)) {
                    $content .= $args . "\n";
                } else {
                    foreach ($args as $arg) {
                        $content .= print_r($arg, TRUE) . "\n";
                    }
                }
                $fh = fopen($file, 'a+');
                if ($fh) {
                    fwrite($fh, $content . "\r\n");
                    fclose($fh);
                }
                unset($content);

            });
			
            self::$DyHttpServer->on('Finish', function (\swoole_server $serv, $task_id, $data) {
                echo 'onFinish task_id=' . $task_id;
                unset($data);
            });

            self::$DyHttpServer->on('Connect', array($this, 'onConnect'));
            self::$DyHttpServer->on('Close', array($this, 'onClose'));
            self::$DyHttpServer->on('Start', array($this, 'onStart'));
            self::$DyHttpServer->on('WorkerStart', array($this, 'onWorkerStart'));
            self::$DyHttpServer->on('WorkerStop', array($this, 'onWorkerStop'));
            self::$DyHttpServer->on('WorkerError', array($this, 'onWorkerError'));
            self::$DyHttpServer->on('Shutdown', array($this, 'onShutdown'));
            self::$DyHttpServer->start();
			
		}else{
			 echo 'load config file fail';
		}
		
		
	}
	
	
    /**
     * worker 进程启动
     * @param $server
     * @param $worker_id
     */
    function onWorkerStart ($server,$worker_id)
    {
        cli_set_process_title(self::$BaseProcessName . 'worker');
    }

    /**
     * 服务关闭事件
     */
    function onShutdown()
    {
        file_put_contents(self::$pidFile, '');
        echo '服务器关闭'.PHP_EOL;
    }

    /**
     * worker 进程结束
     * @param $server
     * @param $worker_id
     */
    function onWorkerStop ($server, $worker_id)
    {
        //这里不能再写日志，因为在关闭时有警告信息
        //$this->access_log('process stop , worker_id = '.$worker_id);
    }

	
	 /**
     * 工作进程异常错误处理
     * @param \swoole_server $serv
     * @param                $worker_id
     * @param                $worker_pid
     * @param                $exit_code
     */
    function onWorkerError(swoole_server $serv, $worker_id, $worker_pid, $exit_code)
    {
        $this->access_log('worker_id = '.$worker_id.'异常错误，pid='.$worker_pid.'; exit_code='.$exit_code);
    }
	
	
    /**
     * 连接事件
     *
     * @param $server
     * @param $fd
     */
    function onConnect ($server, $fd)
    {
        $this->chkSwooleTable(DyHttpServer::$ConnectNameKey);
    }

    function onStart($server)
    {
        file_put_contents(self::$pidFile, $server->master_pid);
    }

    /**
     * 连接关闭
     * @param $server
     * @param $fd
     */
    function onClose ($server,$fd)
    {
        $this->access_log('closed fd = '.$fd);
        $this->chkSwooleTable(DyHttpServer::$ConnectNameKey,'close');
    }
	/**
     * 对内存表的链接与请求数的增减操作
     * @param        $key
     * @param string $type
     */
    function chkSwooleTable ($key,$type='connect')
    {
        try{
            if ( !empty(DYserver::$DyHttpServer) ) {
                $arr=DYserver::$DyHttpServer->sw_table->get($key);
            }
            if($type=='connect'){
                $arr[$key]++;
            }else if($type=='close'){
                $arr[$key]--;
            }

            $this->access_log($key.' Number='.$arr[$key]);
            if ( !empty(DYserver::$DyHttpServer) ) {
                DYserver::$DyHttpServer->sw_table->lock();
                DYserver::$DyHttpServer->sw_table->set($key, $arr);
                DYserver::$DyHttpServer->sw_table->unlock();
            }
        }catch(Exception $e){

        }

    }
}

date_default_timezone_set('PRC');
$server = new DyServer();
$server->run();

/*

$http = new DyHttpServer("192.168.64.136", 9501);

$http->on('request', function ($request, $response) {
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});
$http->start();

*/
