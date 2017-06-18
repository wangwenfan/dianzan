<?php
/**
 * 操作mysql数据封装
 * User: wangwenfan
 * Date: 2017/6/16 0016
 * Time: 13:28
 */
class Db{

    public $mysql;

    /**
     * 加载配置文件实例化mysql
     * DataBase constructor.
     * @param array $mysqlConfig
     */
    public function __construct($mysqlConfig=[])
    {
        $mysql_port = $mysqlConfig['port'] ? $mysqlConfig['port'] : 3306;
        $this->mysql = mysqli_connect($mysqlConfig['host'], $mysqlConfig['username'], $mysqlConfig['password'], $mysqlConfig['database'], $mysql_port);
    }

    /**
     * 查询所有数据
     * @param string $sql
     * @return array
     */
    public function findAll($sql='')
    {
        return $this->fetchs_array($this->query($sql));
    }

    /**
     * 查询一条数据
     * @param string $sql
     * @return array
     */
    public function findOne($sql='')
    {
       return $this->fetchs_array($this->query($sql),true);

    }

    /**
     * 执行sql语句
     * @param $sql
     * @return bool|mysqli_result
     */
    public function query($sql)
    {

        return $this->mysql->query($sql);
    }

    /**
     * 获取结果集
     * @param array $dataObject
     * @param bool $one
     * @return array
     */
    protected function fetchs_array($dataObject=[], $one=false)
    {
        $rows=[];
        while ($row = $dataObject->fetch_assoc()) {
            if($one) return $row;
            $rows=[$row];
        }
        return $rows;
    }

    /**
     *销毁对象
     */
    public function __destruct()
    {
        mysqli_close($this->mysql);
    }
}