<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/16 0016
 * Time: 15:26
 */
return [
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'post_set'=>'post_set',
    ],
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'root',
        'database' => 'test',
    ],
    'dbTable' => [
        'form_post_user' => 'post_user',//文章点赞数据表
        'form_post_set' => 'post_set'//用户点赞记录表
    ],
    'log' =>'zan.log'
];