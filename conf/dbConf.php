<?php
return [
    'port'               => 9502 ,											//端口,默认的
    'listen_ip'          => '127.0.0.1' ,									//监听ip
    'worker_process'     => 1,												//工作进程
    'task_worker_num'    => 2 ,												//task 任务进程数量
    'dy_http_server_key' => 'dydb_http_server' ,							//在$_SERVER 下专用这个保存与应用程序的交互
    'max_memory'         => 4 ,												//最大内存 M
    'log_file'           => '/var/log/dydbserver.log' ,						//服务运行错误日志
    'daemonize'          => 1 ,												//守护进程
    'host'				 => 'server.dbwangdun.com',							//域名
	'access_log'		 => '/data/swooleserver/dyserver/log/',				//访问日志
	'access_log_open'    => true,											//是否开启访问日志
	'redis_ip'			 => '127.0.0.1',									//redis 服务器
	'redis_port'		 =>  6379,											//redis 端口
	'rcount'			 =>	 1000,											#一次提取的数据数量
];
