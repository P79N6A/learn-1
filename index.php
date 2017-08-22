<?php
require 'vendor/autoload.php';
//require 'root\phpqrcode\phpqrcode.php';
use root\strategy\mobileShow;
use root\strategy\computerShow;
use root\strategy\show;

function exception_handler(Throwable $e)
{
    if ($e instanceof Error) {
        echo $e->getMessage();
    } else {
        echo $e->getMessage();
    }
}

set_exception_handler('exception_handler');
/*function _exception_handler(Throwable $e)
{
    if ($e instanceof Error)
    {
        echo "catch Error: " . $e->getCode() . '   ' . $e->getMessage() . '<br>';
    }
    else
    {
        echo "catch Exception: " . $e->getCode() . '   ' . $e->getMessage() . '<br>';
    }
}*/

//set_exception_handler('exception_handler');    // 注册异常处理方法来捕获异常
//set_exception_handler()
$arr = [];
/*$sort = bubblingSort($arr);
print_r($sort);*/
//$a = new \root\Learn();
//$a->test;
$a = 0;
/*$a = 'computer';
if ($a == 'mobile') {
    $show = new mobileShow();
} else {
    $show = new computerShow();
}
$showGoods = new show($show);
$showGoods->showGoods();*/
//策略模式
/*$message = new \root\strategy\email();
$notice = new \root\strategy\notice($message);
$notice->send();*/

$arr = [
    ['name' => '1号奖', 'p' => 2],
    ['name' => '2号奖', 'p' => 5],
    ['name' => '3号奖', 'p' => 7],
    ['name' => '4号奖', 'p' => 8],
    ['name' => '5号奖', 'p' => 10],
    ['name' => '5号奖', 'p' => 23],
    ['name' => '5号奖', 'p' => 45],
];
/*$probability = new \root\probability\Probability($arr);
echo "开始时间" . runtime()->start() . "</br>";
for ($i = 0; $i <= 100000; $i++) {
    1;
}
echo '结束时间' . runtime()->stop() . ' \n'
echo '运行时间:' . runtime()->spent();*/
//$factory  = \root\factory\Factory::make('action');
//$factory-

/*$a = 'Original';
$my_array = array("a" => "Cat", "b" => "Dog", "c" => "Horse","1" => "Horse1");
extract($my_array);
echo "\$a = $a; \$b = $b; \$c = $a";*/
// QRcode::png('http://68g97w.natappfree.cc/');
//echo "<span style='font-size: 200px'> 请输入你的银行卡号!东哥一哈哈给你转账 </span>";
//echo 'hello!';
/* $s = 0;
 $n = 8;$m = 3;
 for($i=1;$i<=$n;$i++) {
     $s = ($s+$m)%$i;
 }
 echo $s+1 . "<br />";*/
//throw new Exception('this is exception');

function myfunction($v)
{
    if ($v === "Dog") {
        return "Fido";
    }
    return $v;
}

/*$a = array("Horse", "Dog", "Cat");
print_r(array_walk($a,function ($v,$k){
    echo $v;
}));*/

//echo min(array(1,2,4,6,5,3,4,5));

/*$arr = ['a','b','c'];
$str = '';
$str = array_walk($arr,function($v) use(&$str){
    $str .= 'where ' . $v . '=' . 1;
});

echo $str;*/


function getDataValues($paramTime)
{

    $currentTime = time();  //当前时间
    $subTime = $currentTime - $paramTime;
    $dateArray = [3600 * 24, 3600, 60, 1];
    $stringArray = ['天', '小时', '分钟', '刚刚'];

    foreach (array_combine($dateArray, $stringArray) as $key => $val) {
        if ($key <= $subTime) {
            $str = floor($subTime / $key) . $val;
            return $key === 1 ? $val : $str;
        }
    }
    //return $arrtime;
    /*if ($arrtime[0] < 7) {

        if ($arrtime[0] > 0 && $arrtime[0] < 7) {
            $datestring = $arrtime[0] . "天前";
        } elseif ($arrtime[1] > 0) {
            $datestring = $arrtime[1] . "小时前";
        } elseif ($arrtime[2] > 0) {
            $datestring = $arrtime[2] . "分钟前";
        } else {
            $datestring = '刚刚';
            //$datestring=$arrtime[3]."秒前";
        }

    } else {
        $datestring = date("m-d H:i", $stime);
    }

    return $datestring;*/
}

/*
$s = file_get_contents ("http://www.php.net");
strip_tags ($s, array ('p'));
echo count ($s);*/
//var_dump(getDataValues(time() - 7199));

/*$client = new \WebSocket\Client('ws://learn.net:8080');
$client->send('hello web socket!');*/
$service = new \WebSocket\Server();
echo $client->receive();


