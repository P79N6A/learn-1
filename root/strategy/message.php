<?php
namespace root\strategy;

class message implements Notification
{
    public function send()
    {
        echo '短信通知';
    }
}