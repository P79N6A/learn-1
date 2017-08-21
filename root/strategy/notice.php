<?php
namespace root\strategy;

class notice
{
    private $notice;

    public function __construct(Notification $notification)
    {
        $this->notice = $notification;
    }

    public function send()
    {
        $this->notice->send();
    }
}