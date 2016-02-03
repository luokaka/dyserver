<?php
return [
    'port'               => 9502 ,											//�˿�,Ĭ�ϵ�
    'listen_ip'          => '127.0.0.1' ,									//����ip
    'worker_process'     => 1,												//��������
    'task_worker_num'    => 2 ,												//task �����������
    'dy_http_server_key' => 'dydb_http_server' ,							//��$_SERVER ��ר�����������Ӧ�ó���Ľ���
    'max_memory'         => 4 ,												//����ڴ� M
    'log_file'           => '/var/log/dydbserver.log' ,						//�������д�����־
    'daemonize'          => 1 ,												//�ػ�����
    'host'				 => 'server.dbwangdun.com',							//����
	'access_log'		 => '/data/swooleserver/dyserver/log/',				//������־
	'access_log_open'    => true,											//�Ƿ���������־
	'redis_ip'			 => '127.0.0.1',									//redis ������
	'redis_port'		 =>  6379,											//redis �˿�
	'rcount'			 =>	 10,											#һ����ȡ����������
	
	'mysql_host'		 => '127.0.0.1',
    'mysql_db'			 => 'dyclient',
    'mysql_user'		 => 'root',
    'mysql_pwd'			 => 'root',
	'mysql_port'		 => '3306',
	
];
