<?php
/**
 * 自动加载
 * User: wangwenfan
 * Date: 2017/6/16 0016
 * Time: 17:51
 */
define('ZAN_APP',__DIR__.'/');
require ZAN_APP.'db/Db.php';
spl_autoload_register( function ($class)
{
    $classes = ZAN_APP.'class/'.$class.'.class.php';
    if(file_exists($classes)) include $classes;
});
