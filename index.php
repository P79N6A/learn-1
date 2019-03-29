<?php
require "vendor/autoload.php";

ini_set("display_errors", "On");

error_reporting(E_ALL | E_STRICT);
/*$a = md5(md5('123456'));
dd($a);
dd(md5($a.'gGz9TUd3'));
$obj = new \App\Pay\Demo(new \App\Pay\Wetch());

$obj->transfer();
echo 'sdfasdfsadf';*/

$a['dd'] = 0;
if(isset($a['dd'])){
    dd(1);
}
dd('sdf');