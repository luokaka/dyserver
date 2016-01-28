<?php
/**
**定时触发处理数据
***/

$config = array(
	'isdaemon'				 => false,												#是否为守护进程
	'time'				     => 1000,												#定时间隔
	'count'					 => 1000,												#每次取条数
);


#开启一个进程
$process 	= new swoole_process('callback_function', $config['isdaemon']);
$pid 		= $process->start();

function callback_function(swoole_process $worker)
{
	global $config;

	
    swoole_timer_tick($config['time'], function(){
		$fp = stream_socket_client("tcp://127.0.0.1:9502", $code, $msg,1);
		$http_request = "GET / HTTP/1.1\r\n\r\n";
		fwrite($fp, $http_request);
		fclose($fp);
	});
}







