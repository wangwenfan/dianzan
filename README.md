# dianzan
mysql和redis实现的点赞功能
---

> 点赞其实是个很有意思的功能，普通的mysql也可以实现，但是遇到高并发性能上不是很好。目前我觉得比较好的方式是用Redis作缓存来实现，从而减轻数据库的负担。

- 下面这张图用来描述下我实现的思路。
![reids流程图](http://qn.wangwenfan.top/4324324234.png)

- redis中有一个`post_set`的集合记录所有文章ID,每接受到一次请求，就`sadd`到`post_Set`。
- `post_user_like_set_{$PID}`集合用来存放该文章下点赞用户的`UID`。
- `post_user_like_{$PID}_{$UID}`HASH用来存放点赞记录，如点赞状态，点赞时间，更新点赞时间，用户ID，文章ID等。
- `post_{$counts}_counts`用来记录redis中文章的点赞数量，点赞加1，取消点赞减1。
- `list_post`的列表用来存放队列数据。

1. 当客户端触发点赞时把UID和PID发送给服务器。
2. 服务器拿到这两个参数去redis中查询是否有`post_user_like_{$PID}_{$UID}`，有的话得到对象中的状态值反值。没有的话去mysql中查询是否有点赞记录。有的话返回对象中的状态值反值。没有的话，点赞状态为1。redis中有缓存的话，更新缓存，没有的话新增一个`post_user_like_{$PID}_{$UID}`。
3. 根据得到的点赞状态给`post_{$counts}_counts`加1或者减1。
4. 将`UID`添加到`post_user_like_set_{$PID}`中。
5. 将PID加入队列。并返回文章总点赞数量，和当前用户点赞状态给客户端。
6. 出队脚本运行时。拿到PID遍历`post_user_like_set_{$PID}`，根据里面的UID，拿到点赞数量和`post_user_like_{$PID}_{$UID}`更新数据到mysql，并销毁redis中的数据。
- 目录结构
```
-plug
|__class
|    |__ Like.class.php 基类 
|    |__ Zan.class.php 队列功能类
|——config
|    |__ config.php 数据库配置文件
|——db
|    |__ Db.php mysql基类
|    |__ zan.sql 数据表导入sql
|__log 日志记录文件
|__ demo demo文件 
|__ autoload.php 自动加载文件
```
- 使用方法：

1. 下载解压到项目，引入autoload.php。
2. 修改配置文件/config/config.php。
3. 将/db/zan.sql 导入到mysql中。

```
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
```
4.实例化Like.class.php 
```
require '../autoload.php';
$like = new Like();
$user_id = $_POST['user_id'];
$post_id = $_POST['post_id'];
$r = $like->giveFavour($user_id,$post_id);
echo json_encode($r);
```
5.出队脚本使用。

```
require '../autoload.php';
$zan = new Zan();
while (TRUE) {
    $zan->execute(10);
}
```
然后cli运行脚本文件，点赞后输出successfully就OK了。
![cli](http://qn.wangwenfan.top/546546546.png)

- demo里的效果。


![demo](http://qn.wangwenfan.top/534354676587.png)

- 下载地址
[https://github.com/wangwenfan/dianzan](https://github.com/wangwenfan/dianzan)
