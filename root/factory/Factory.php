<?php
namespace root\factory;

class Factory
{
    public function a()
    {

    }

    static function make(string $class)
    {
        $class = ucfirst($class);
        if (class_exists($class)) {
            return new $class;
        }
        throw new \Exception('class undefined!');
    }
}