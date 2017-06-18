<?php

/**
 * 点赞出队列脚本
 * User: wangwenfan
 * Date: 2017/6/16 0016
 * Time: 16:20
 */
class Zan extends Like
{
    protected $time;

    /**
     * 执行出队操作
     * @param int $time_out
     */
    public function execute($time_out = 0)
    {
        $this->time = time();
        $post = $this->queueOut($time_out);
        if ($post_id = $post[1]) {
            $this->mysqli->mysql->autocommit(false);
            try {
                $this->post_id = $post_id;
                //获取redis中该文章下所有用户id
                $user = $this->findIsPostUser($this->post_id);
                //获取redis中文章点赞数量
                $redisNum = $this->findRedisCounts();
                //更新数据库中文章点赞数量
                $this->updateDbPostSet($redisNum);
                //循环更新数据到mysql
                for ($i = 0; $i < count($user); $i++) {
                    $this->user_id = $user[$i];//拿到用户ID
                    //查询该文章hash数据
                    $userSatateData = $this->findPostUserHash();
                    //更新mysql中post_user表数据
                    $a = $this->updateDbPostUser($userSatateData);
                    //删除该文章下redis中的用户
                    $this->unsetRedisUserCounts();
                    //删除redis中该文章hash数据
                    $this->unsetRedisPostHash();
                }
                $this->mysqli->mysql->commit();
                print ($redisNum . '--successfully');
            } catch (Exception $exception) {
                $this->mysqli->mysql->rollback();
                //写入日志
                $this->writeLog();
            }

        }

    }

    /**
     * 更新db post_set数据
     * @param string $redisNum
     * @return bool|int
     */
    protected function updateDbPostSet($redisNum = '')
    {
        //更新mysql文章总点赞表
        if ($this->findPostCountsAll()) {
            $sql = "update {$this->post_set} set zan_count=zan_count+{$redisNum},update_at={$this->time} where post_id={$this->post_id}";
        } else {
            $sql = "insert into {$this->post_set} (post_id,zan_count,update_at) values ({$this->post_id},{$redisNum},{$this->time})";
        }
        //更新数据后销毁redis文章数量
        if ($this->mysqli->query($sql)) {
            return $this->unsetRedisPostCounts();
        } else {
            return false;
        }
    }

    /**
     * 更新db post_user数据
     * @param array $userSatateData
     * @return bool|mysqli_result
     */
    protected function updateDbPostUser($userSatateData = [])
    {
        //更新mysql文章关联用户点赞状态
        if ($this->findDbPostUser()) {
            $sql = "update {$this->form_post_user} set update_at={$userSatateData['update_at']},
                    status={$userSatateData['status']},list_update_at={$this->time} where post_id={$this->post_id} and user_id={$this->user_id}";
        } else {
            $sql = "insert into {$this->form_post_user} (post_id,user_id,update_at,create_at,status,list_update_at) values 
                        ({$this->post_id},{$this->user_id},{$userSatateData['update_at']},{$userSatateData['create_at']},{$userSatateData['status']},{$this->time})";
        }
        return $this->mysqli->query($sql);
    }

    /**
     * 删除redis中hase对象
     * @return int
     */
    private function unsetRedisPostHash()
    {
        $this->post_user_like = 'post_user_like_' . $this->post_id . '_' . $this->user_id;
        return $this->deleteRedisPostUser($this->post_user_like);
    }

    /**
     * 删除文章下的用户
     * @return int
     */
    private function unsetRedisUserCounts()
    {
        return $this->deletePostSetUser();
    }

    /**
     *记录失败日志
     */
    private function writeLog()
    {
        //写入日志
        $logFile = '../log/' . $this->logName;
        $content = "操作失败,文章：" . $this->post_id . '--用户：' . $this->user_id . '\r\n';
        if (is_file($logFile)) {
            if (file_put_contents($logFile, $content, FILE_APPEND)) {
                print ("写入成功");
            } else {
                print ("写入失败");
            }
        } else {
            print ("文件不存在或没有写入权限");
        }
    }


}
