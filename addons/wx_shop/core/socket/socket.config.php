<?php

/**
 * socket server配置文件，重启后生效
 */

// 开发模式开关
define('SOCKET_SERVER_DEBUG', true);

// 设置服务端IP
define('SOCKET_SERVER_IP', 'xcxvip.iiio.top');

// 设置服务端端口
define('SOCKET_SERVER_PORT', '9501');

// 设置是否启用SSL
define('SOCKET_SERVER_SSL', false);

// 设置SSL KEY文件路径
define('SOCKET_SERVER_SSL_KEY_FILE', '');///www/wdlinux/nginx-1.2.9/conf/cert/214394847660647.key

// 设置SSL CERT文件路径
define('SOCKET_SERVER_SSL_CERT_FILE', '');///www/wdlinux/nginx-1.2.9/conf/cert/214394847660647.pem

// 设置启动的worker进程数
define('SOCKET_SERVER_WORKNUM', 8);

// 设置客户端请求IP
define('SOCKET_CLIENT_IP', 'xcxvip.iiio.top');