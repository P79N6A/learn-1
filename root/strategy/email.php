<?php
namespace root\strategy;
class email implements Notification{
    public function send()
    {
        echo '邮件通知';
    }
}