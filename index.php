<?php
require "vendor/autoload.php";

ini_set("display_errors", "On");

error_reporting(E_ALL | E_STRICT);

class User
{
    protected $name;

    private $a;

    public function __set($name, $value)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('set a property not exists.');
        }
        $this->$name = 'aaaa';
        // TODO: Implement __set() method.
    }

    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new Exception('get a property not exists.');
        }
        return $this->$name;
        // TODO: Implement __get() method.
    }

    public static function create()
    {
        return new static();
    }

    public function __construct()
    {
        echo __FUNCTION__;

    }
}

class Users extends User
{
    public function aaa()
    {
        echo __FUNCTION__;
    }
}

//$user = new Users();
//$users = Users::create();

//$user->aaa();


echo date('Y-m-d H:i:s',1561517074);


//$user->names = 'sdfaf';

//$user->a = 'sdfa';

//echo $user->a;
