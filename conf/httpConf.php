<?php
return [
    'port'               => 9501 ,											//�˿�,Ĭ�ϵ�
    'listen_ip'          => '192.168.64.136' ,								//����ip
    'worker_process'     => 1,												//��������
    'task_worker_num'    => 4 ,												//task �����������
    'php_map'            => 'php' ,											//php�ļ�����ӳ��,���� html ���� index.html ӳ�䵽index.php�ļ�
    'dy_http_server_key' => 'dy_http_server' ,								//��$_SERVER ��ר�����������Ӧ�ó���Ľ���
    'max_memory'         => 4 ,												//����ڴ� M
    'log_file'           => '/var/log/dyserver.log' ,						//�������д�����־
    'daemonize'          => 1 ,												//�ػ�����
    'host'				 => 'server.wangdun.com',							//����
	'access_log'		 => '/var/log/'
];
