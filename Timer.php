<?php
/**
**��ʱ������������
***/

$config = array(
	'isdaemon'				 => false,												#�Ƿ�Ϊ�ػ�����
	'time'				     => 1000,												#��ʱ���
	'count'					 => 1000,												#ÿ��ȡ����
);


#����һ������
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







