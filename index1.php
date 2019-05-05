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

/*$a['dd'] = 0;
if(isset($a['dd'])){
    dd(1);


}*/
//dd('hello jenkins!!!');


/*$sum = 0;
srand(microtime(true));

$rand = rand(10,13);

echo $rand;

echo '</br>';

$t = microtime(true);

for ($i = 0; $i<40000+$r;$i++){
    for ($j=0;$j<=40000;$j++){
        $sum = $sum+$i+$j;
    }
}

echo $sum;

echo '</br>';

$end = microtime(true);



echo $end-$t;*/

function removeDuplicates(&$nums) {
    $count = count($nums);
    for($i = 0 ; $i <$count -1;$i++){
        for($s = $i+1 ; $s <$count;$s++){
            if($nums[$i] == $nums[$s]){
                unset($nums[$i]);
                $i ++;

            }
        }
    }
    return $count;
}

$arr = [0, 0, 1, 1, 1, 2, 2, 3, 3, 4];
dd(removeDuplicates($arr));
die();

$s = '(){[]}';

function isValid($s)
{
    $arr = [];

    for ($i = 0; $i < strlen($s); $i++) {
        if ($s[$i] == '(') {
            array_push($arr, ')');
        } else if ($s[$i] == '{') {
            array_push($arr, '}');
        } else if ($s[$i] == '[') {
            array_push($arr, ']');
        } else if (empty($arr) || array_pop($arr) != $s[$i]) {
            return false;
        }
    }
    return empty($arr);
}
echo $s;
dd(isValid($s));

