<?php
return [
    'port'               => 9501 ,											//�˿�,Ĭ�ϵ�
    'listen_ip'          => '192.168.64.136' ,								//����ip
    'worker_process'     => 1,												//��������
    'task_worker_num'    => 4 ,												//task �����������
    'dy_http_server_key' => 'dy_http_server' ,								//��$_SERVER ��ר�����������Ӧ�ó���Ľ���
    'max_memory'         => 4 ,												//����ڴ� M
    'log_file'           => '/var/log/dyserver.log' ,						//�������д�����־
    'daemonize'          => 1 ,												//�ػ�����
    'host'				 => 'server.wangdun.com',							//����
	'access_log'		 => '/var/log/',										//������־
	'access_log_open'    => true,											//�Ƿ���������־
	'redis_ip'			 => '127.0.0.1',									//redis ������
	'redis_port'		 =>  6379,											//redis �˿�
];
