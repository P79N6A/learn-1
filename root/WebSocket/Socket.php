<?php
namespace root\WebSocket;

Class Socket
{
    //搭建webSocket 聊天室
    public function __construct($url = '127.0.0.1', $port = '8888')
    {
        $this->master = $this->instance($url, $port);
    }

    //创建socket实例
    public function instance($url, $port)
    {
        socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    }
}