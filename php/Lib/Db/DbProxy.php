<?php

/*****************************************************
 * File name: DbMysql.php
 * Create date: 2018/09/20
 * Author: smallyang
 * Description: mysqlProxy类库
 * modify: ronzheng 2019/3/29
 *         1、增加 select和find方法注释，对返回结果做详细说明
 *         2、增加对insert方法的注释，对返回结果做详细说明
 *****************************************************/

namespace Lib\Db;

use Lib\Base\Common;

class DbProxy
{
    /**
     * @var \DBProxy
     */
    private $mysql;
    private $queryString;
    private $band_values;
    private $table;
    private $realTable;
    private $where_keys;
    private $limit_keys;
    private $group_keys;
    private $having_keys;
    private $order_keys;

    protected $extracts = array('=', '>', '>=', '<', '<=', '!=', '<>', 'is', 'is not');

    public static $transTimes = 0;

    private static $dbObject;

    private $transSql = array();

    private $transStart = false;

    /**
     * 实例化
     *
     * @param $dbConfig
     * @return $this
     */
    public static function init($dbConfig)
    {
        if (!self::$dbObject) {
            self::$dbObject = new self($dbConfig);
        }
        return self::$dbObject;
    }

    /**
     * 构造方法
     *
     * DbMysql constructor.
     * @param $dbConfig
     */
    private function __construct($dbConfig)
    {
        if (!$this->mysql) {
            $this->mysql = $this->connectDb($dbConfig);
        }
        return $this;
    }

    /**
     * 连接数据库
     *
     * @param $config
     * @return \DBProxy
     */
    private function connectDb($config)
    {
        //配置
        if (!isset($config['instance']) || !isset($config['db_name'])) {
            die('db proxy 配置文件缺失参数，请检查');
        }

        $file = COMMON_CONFIG_PATH . "/commconf_php.cfg";
        $common = parse_ini_file($file, true);
        if (!$config || !$common[$config['instance']]) {
            $this->mysql = null;
            recordAMSLog('db config read error');
            return false;
        } else {
            $instance = $common[$config['instance']];
            try {
                $host = $instance["proxy_ip"];
                if (isset($_SERVER['PROXYNAME'])) {
                    $host = "proxy-yxgw-comm";
                }
                $this->mysql = new \DBProxy($host, $instance["proxy_port"], $config['db_name']);
                recordAMSLog('connect DBProxy：host=' . $host . '，port=' . $instance["proxy_port"]);
            } catch (\Exception $e) {
                recordAMSLog('数据库连接失败: ' . $e->getMessage());
                return false;
            }
        }
        return $this->mysql;
    }

    /**
     * 单纯的执行sql|一般的增删改
     *
     * @param  string $sql
     * @return mixed
     */
    public function exec($sql)
    {
        return $this->executeUpdate($sql);
    }

    /**
     * 查询
     *
     * @param $sql
     * @param int $row 返回结果集中的row行结果
     * @return array|bool 执行失败返回false，成功返回结果集数组
     */
    private function executeQuery($sql, $row = 0)
    {
        if (!$this->mysql) {
            return false;
        }

        //事务
        if ($this->transStart) {
            $this->transSql[] = $sql;
            return true;
        }

        $this->queryString = $sql;
        $result = [];
        try {
            $ret = $this->mysql->exec_query($sql, $result);
            if ($ret < 0) {
                $msg = $this->mysql->get_err_no() . $this->mysql->get_err_msg();
                recordAMSLog("query error: " . $sql . " result: " . $msg);
                return false;
            } else {
                //$result = \Util::gbk_to_utf8($result);
                if ($row) {
                    $data = empty($result) ? [] : $result[0];
                } else {
                    $data = $result;
                }
                recordAMSLog("query sql: " . $sql . "ret:" . $ret . " result: " . json_encode($result));
                return $data;
            }
        } catch (\Exception $e) {
            recordAMSLog(__METHOD__ . "_Exception: " . $sql . ' exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新
     *
     * @param $sql
     * @return array|bool
     */
    private function executeUpdate($sql)
    {
        if (!$this->mysql) {
            return false;
        }

        //事务
        if ($this->transStart) {
            $this->transSql[] = $sql;
            return true;
        }

        $this->queryString = $sql;
        try {
            $ret = $this->mysql->exec_update($sql);
            recordAMSLog("query sql: " . $sql . " result: " . $ret);
            if ($ret < 0) {
                $msg = $this->mysql->get_err_no() . $this->mysql->get_err_msg();
                recordAMSLog("query error: " . $sql . " result: " . $msg);
                return false;
            }
        } catch (\Exception $e) {
            recordAMSLog(__METHOD__ . "_Exception: " . $sql . ' exception: ' . $e->getMessage());
            return false;
        }
        return $ret;
    }

    /**
     * 单纯的执行sql|一般的查询
     *
     * @param  string $sql
     * @param int $row
     * @return mixed
     */
    public function query($sql, $row = 0)
    {
        return $this->executeQuery($sql, $row);
    }

    /**
     * 指定表名
     *
     * @param string $tableName
     * @return $this
     */
    public function table($tableName)
    {
        $this->table = $tableName;
        $this->realTable = $tableName;
        $this->where_keys = '';
        $this->band_values = [];
        $this->limit_keys = '';
        $this->group_keys = '';
        $this->having_keys = '';
        $this->order_keys = '';
        return $this;
    }

    /**
     * 分开字符转义
     * @param string|array $value
     * @return string
     */
    private function addEscape($value)
    {
        $fieldStr = '`';
        if (is_array($value)) {
            $fieldStr .= implode('`,`', $value);
        } else {
            $fieldStr .= $value;
        }
        $fieldStr .= '`';
        return $fieldStr;
    }

    /**
     * 产生占位符
     *
     * @param  array $data
     * @return string
     */
    private function createPlaceHolder(array $data)
    {
        $holder = '';
        foreach ($data as $value) {

            if (is_int($value)) {
                $holder .= '' . $value . ', ';
            } else {
                $holder .= "'" . $this->real_escape_string($value) . "', ";
            }
        }
        return rtrim(trim($holder), ',');
    }

    /**
     * 插入数据/增加
     *
     * @param $data
     * @param $mode string insert, ignore, replace
     * @return int|bool SQL执行异常返回false。执行insert时如果有自增ID，则返回自增ID，没有自增ID返回0。其他语句执行成功返回0
     * 注意：用 $ret === false 判断SQL执行异常
     */
    public function insert($data, $mode = 'insert')
    {
        if (empty($data)) {
            return false;
        }

        $data_keys = array_keys($data);
        $data_values = array_values($data);

        //插入模式
        $insertMode = 'INSERT INTO ';
        if ($mode == 'ignore') {
            $insertMode = 'INSERT IGNORE INTO ';
        } elseif ($mode == 'replace') {
            $insertMode = 'REPLACE INTO ';
        }
        $sql = $insertMode . $this->table . '(' . $this->addEscape($data_keys) . ') VALUES (' . $this->createPlaceHolder($data_values) . ')';
        return $this->executeUpdate($sql);
    }

    /**
     * 删除
     *
     * @return mixed
     */
    public function delete()
    {
        $sql = "DELETE FROM " . $this->table . $this->where_keys;
        return $this->executeUpdate($sql);
    }

    /**
     * 总数
     */
    public function count()
    {
        $sql = "SELECT count(*) as count FROM " . $this->table . $this->where_keys;
        $row = $this->executeQuery($sql, 1);
        if (isset($row['count'])) {
            return $row['count'];
        } else {
            return $row;
        }
    }

    /**
     * update 修改
     *
     * @param array $data
     * @return int|bool SQL执行失败返回false,SQL执行成功返回影响的行数
     * 注意： 调用该函数时，必须先用函数返回结果$ret与false进行严格比较（$ret===false）来判断是否执行成功
     *        $ret === 0 表示SQL执行成功，但是更新的行数为0
     *        $ret > 0 表示SQL执行成功，且更新到了行
     *        在执行带有版本号的SQL语句时，一定要用$ret > 0 来判断SQL语句是否真正执行成功并且影响到行数
     */
    public function update($data)
    {
        $update_keys = [];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_int($value)) {
                    $update_keys[] = $key . " = " . $value;
                } else {
                    $update_keys[] = $key . " = '" . $this->real_escape_string($value) . "'";
                }
            }
            $update_keys = implode(", ", $update_keys);
        } else {
            $update_keys = $data;
        }

        $sql = "UPDATE " . $this->table . " SET " . $update_keys . $this->where_keys . $this->limit_keys;
        return $this->executeUpdate($sql);
    }

    /**
     * where 条件
     *
     * @param  array|string $data
     * @param string $relation
     * @return $this
     */
    public function where($data = '', $relation = 'AND')
    {
        if (empty($data)) {
            return $this;
        }
        if (is_array($data)) {
            $where_keys = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (!in_array($value[0], $this->extracts)) {
                        recordAMSLog('不支持 ' . $value[0]);
                        return false;
                    }
                    if (is_int($value[1])) {
                        $where_keys[] = $key . " " . $value[0] . " '" . $value[1] . "'";
                    } else {
                        $where_keys[] = $key . " " . $value[0] . " '" . $this->real_escape_string($value[1]) . "'";
                    }
                } else {
                    if (is_int($value)) {
                        $where_keys[] = $key . " = '" . $value . "'";
                    } else {
                        $where_keys[] = $key . " = '" . $this->real_escape_string($value) . "'";
                    }
                }

            }
            $this->where_keys = ' WHERE ' . implode(' ' . strtoupper($relation) . ' ', $where_keys);
        } else {
            $this->where_keys = ' WHERE ' . $this->real_escape_string($data);
        }
        return $this;
    }

    /**
     * 查询/多条
     *
     * @param  string $field
     * @return array|bool 成功返回结果集数组(如果没有满足条件的查询则返回一个空数组)，失败返回false
     * 注意：如果用$ret表示返回结果
     *      1、必须先用$ret === false来判断SQL是否执行异常
     *      2、再用empty($ret)为true来判断是否有满足条件的结果集，根据empty的结果true or false来处理不同的业务逻辑
     *
     */
    public function select($field = '*')
    {
        $sql = "SELECT " . $field . " FROM " . $this->table . $this->where_keys . $this->group_keys . $this->having_keys . $this->order_keys . $this->limit_keys;
        return $this->executeQuery($sql);
    }

    /**
     * 查询/一条
     *
     * @param  string $field
     * @return array|bool 成功返回结果集中的第一条记录(如果没有满足条件的查询则返回一个空数组)，失败返回false
     * 注意：如果用$ret表示返回结果
     *      1、必须先用$ret === false来判断SQL是否执行异常
     *      2、再用empty($ret)来判断是否有满足条件的结果集，根据empty的结果true or false来处理不同的业务逻辑
     *
     */
    public function find($field = '*')
    {
        $sql = "SELECT " . $field . " FROM " . $this->table . $this->where_keys . $this->group_keys . $this->having_keys . $this->order_keys . $this->limit_keys;
        return $this->executeQuery($sql, 1);
    }

    /**
     * group 分组
     *
     * @param  string $field
     * @return $this
     */
    public function group($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $this->group_keys = ' GROUP BY ' . $field;
        return $this;
    }

    /**
     * having 字句，分组后筛选
     *
     * @param  string $field
     * @return $this
     */
    public function having($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $this->having_keys = ' HAVING ' . $field;
        return $this;
    }

    /**
     * 排序order by
     *
     * @param  string $field
     * @param  string $order
     * @return $this
     */
    public function order($field = '', $order = 'DESC')
    {
        if (empty($field)) {
            return $this;
        }
        if (!in_array(strtoupper($order), ['ESC', 'DESC'])) {
            $order = 'DESC';
        }
        $this->order_keys = ' ORDER BY ' . $field . " " . strtoupper($order);
        return $this;
    }

    /**
     * limit分页
     *
     * @param  $start
     * @param  $end
     * @return $this
     */
    public function limit($start = '', $end = '')
    {
        if (empty($start)) {
            return $this;
        }
        if ($end) {
            $limit = " LIMIT {$start}, {$end}";
        } else {
            $limit = " LIMIT {$start}";
        }
        $this->limit_keys = $limit;
        return $this;
    }

    /**
     * 获得最后执行的sql
     *
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryString;
    }

    /**
     * 获取表table名
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * real_escape_string
     *
     * @param $string
     * @return mixed|string
     */
    private function real_escape_string($string)
    {
        $string = addslashes($string);
        return str_replace('`', '\`', $string);
    }

    /**
     * 分表
     *
     * @param $str
     * @param int $count
     * @return $this
     */
    public function sub($str, $count = 10)
    {
        $num = Common::time33($str);
        $this->table = $this->realTable . '_' . fmod($num, $count);
        return $this;
    }

    /**
     * 启动事务
     *
     * @return $this
     */
    public function begin()
    {
        //数据rollback 支持
        if (!$this->mysql) {
            return false;
        }
        $this->rollback();
        $this->transStart = true;
        recordAMSLog("start trans");
        return $this;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return bool
     */
    public function commit()
    {
        //数据rollback 支持
        if (!$this->mysql) {
            return false;
        }
        if ($this->transStart && is_array($this->transSql) && $this->transSql) {
            $ret = $this->mysql->exec_trans($this->transSql);
            if ($ret < 0) {
                $msg = $this->mysql->get_err_no() . $this->mysql->get_err_msg();
                recordAMSLog("commit error: " . json_encode($this->transSql) . " result: " . $msg);
                recordAMSLog("rollback success");
                return false;
            } else {
                recordAMSLog("commit sql: " . json_encode($this->transSql) . " ret: " . $ret);
                recordAMSLog("commit success");
                $this->rollback();
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollback()
    {
        if (!$this->mysql) {
            return false;
        }
        $this->transSql = [];
        $this->transStart = false;
        return true;
    }
}
