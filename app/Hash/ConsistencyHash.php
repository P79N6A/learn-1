<?php/** * Created by PhpStorm. * User: donggege * Date: 2019-05-16 * Time: 13:42 */namespace App\Hash;interface ConsistencyHash{    //哈希算法    public function hash($str);    //添加服务器节点    public function addServer($ip);    //删除服务器节点    public function removeServer($ip);    //找到应该访问的服务器节点    public function find($key);}