<?php

/**
 * Redis+Mysql点赞功能.
 * User: wangwenfan
 * Date: 2017/6/16 0016
 * Time: 11:46
 */
class Like
{

    protected $mysqli;
    protected $redis;
    protected $user_id;//用户id
    protected $post_id;//文章id
    protected $post_counts;//文章计数key
    protected $post_user_like_set;//post_id下的user_id集合
    protected $post_user_like;//hash类型的点赞数据对象
    protected $list_post;//队列名
    private $status;//点赞状态
    protected $post_set;//存放所有文章ID
    protected $form_post_user;//用户点赞信息表
    protected $form_post_set;//文章点赞数量表
    protected $logName;//日志文件名

    /**
     * 获取配置项实例对象
     * Like constructor.
     * @param array $config
     */
    public function __construct()
    {
        $config = require ZAN_APP . 'config/config.php';
        $this->logName = $config['log'];
        $mysqlConfig = $config['mysql'];
        $redisConfig = $config['redis'];
        $dbTable = $config['dbTable'];
        $this->mysqli = new Db($mysqlConfig);
        $this->post_set = $redisConfig['post_set'] ? $redisConfig['post_set'] : 'post_set';
        $redis_port = $redisConfig['port'] ? $redisConfig['port'] : 6379;
        $this->list_post = 'list_post';
        $this->redis = new Redis();
        $this->redis->connect($redisConfig['host'], $redis_port);
        $this->form_post_user = $dbTable['form_post_user'] ? $dbTable['form_post_user'] : 'post_user';
        $this->form_post_set = $dbTable['form_post_set'] ? $dbTable['form_post_set'] : 'post_set';
    }

    /**
     * 执行点赞操作
     * @param string $user_id 用户id
     * @param string $post_id 文章id
     * @return bool|array
     */
    public function giveFavour($user_id = '', $post_id = '')
    {
        if (empty($user_id) || empty($post_id)) return false;
        $result = [];
        $this->user_id = $user_id;
        $this->post_id = $post_id;
        $this->addPostSet();
        if ($postUserData = $this->findPostUserHash()) {
            $this->status = $postUserData['status'] ? 0 : 1;
        } else {
            $result = $this->findDbPostUser();
            if ($result) {
                $this->status = $result['status'] ? 0 : 1;
            } else {
                $this->status = 1;
            }
        }
        $this->addPostUserHash($postUserData);
        $this->addUser();
        $this->postGiveCount();
        //得到文章点赞数量
        $dbCount = $this->findPostCountsAll();
        $redisCount = $this->findRedisCounts();
        $data['zanCounts'] = $dbCount['zan_count'] + $redisCount;
        $data['status'] = $this->status;
        //进入队列
        $this->queueInto();
        return $data;
    }

    /**
     * 根据文章ID获取点赞量
     * @param string $post_id
     * @return int
     */
    public static function getPostCountsAll($post_id = '')
    {
        if (empty($post_id)) return 0;
        $self = new self();
        $self->post_id = $post_id;
        $sql = "SELECT * FROM {$self->form_post_set} WHERE post_id={$self->post_id}";
        $result = $self->mysqli->findOne($sql);
        return $result['zan_count'] + $self->findRedisCounts();
    }

    /**
     * 获取该文章点赞状态
     * @param string $user_id
     * @param string $post_id
     * @return int
     */
    public static function getUserIsFull($user_id = '', $post_id = '')
    {
        if (empty($user_id) || empty($post_id)) return 0;
        $self = new self();
        $self->post_id = $post_id;
        $self->user_id = $user_id;
        if ($r = $self->findRedisPostUser()) return $r['status'];
        if ($d = $self->findDbPostUser()) return $d['status'];
        else return 0;
    }

    /**
     * 获取Redis中用户点赞信息
     * @param string $post_id
     * @param string $user_id
     * @return array
     */
    protected function findRedisPostUser($post_id = '', $user_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->user_id = $user_id ? $user_id : $this->user_id;
        $this->post_user_like = 'post_user_like_' . $this->post_id . '_' . $this->user_id;
        return $this->redis->hGetAll($this->post_user_like);
    }

    /**
     * 获取Db中用户点赞信息
     * @return array
     */
    protected function findDbPostUser($post_id = '', $user_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->user_id = $user_id ? $user_id : $this->user_id;
        $sql = "SELECT * FROM {$this->form_post_user} WHERE post_id={$this->post_id} AND user_id={$this->user_id}";
        return $this->mysqli->findOne($sql);
    }


    /**
     * 获取DbZ中文章点赞数量
     * @param string $post_id 文章id
     * @return mixed
     */
    protected function findPostCountsAll($post_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $sql = "SELECT * FROM {$this->form_post_set} WHERE post_id={$this->post_id}";
        $result = $this->mysqli->findOne($sql);
        return $result;
    }

    /**
     * 获取redis中的文章点赞数量
     * @return bool|string
     */
    protected function findRedisCounts($post_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->post_counts = 'post_' . $this->post_id . '_counts';
        $num = $this->redis->get($this->post_counts);
        return $num ? $num : 0;
    }

    /**
     * 点赞数据写入hash key
     * @param array $result
     */
    protected function addPostUserHash($postUserData = [])
    {
        $this->post_user_like = 'post_user_like_' . $this->post_id . '_' . $this->user_id;
        if (!$postUserData || empty($postUserData)) {
            $this->redis->hSet($this->post_user_like, 'user_id', $this->user_id);//用户id
            $this->redis->hSet($this->post_user_like, 'post_id', $this->post_id);//文章id
            $this->redis->hSet($this->post_user_like, 'create_at', time());//点赞时间
        }
        $this->redis->hSet($this->post_user_like, 'update_at', time());//更新时间
        $this->redis->hSet($this->post_user_like, 'status', $this->status);//点赞时间
    }

    /**
     * 存放请求用户id
     * @return int
     */
    protected function addUser($post_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->post_user_like_set = 'post_user_like_set_' . $this->post_id;
        return $this->redis->sAdd($this->post_user_like_set, $this->user_id);
    }


    /**
     * 文章点赞量计数
     * @return int
     */
    private function postGiveCount()
    {
        $this->post_counts = 'post_' . $this->post_id . '_counts';
        if ($this->status) {
            $counts = $this->redis->incr($this->post_counts);
        } else {
            //取消点赞
            $counts = $this->redis->decr($this->post_counts);
        }
        return $counts;
    }

    /**
     * 进入队列
     * @return int
     */
    private function queueInto()
    {
        return $this->redis->rPush($this->list_post, $this->post_id);
    }

    /**
     * 出队列
     * @return array
     */
    public function queueOut($timeout = 0)
    {
        return $this->redis->blPop($this->list_post, $timeout);
    }

    /**
     * 文章ID写入集合
     * @return bool
     */
    protected function addPostSet()
    {
        $postSetResult = $this->redis->sAdd($this->post_set, $this->post_id);
        if ($postSetResult) return true;
        else return false;
    }

    /**
     * 获取redis中用户点赞状态数据
     * @return array|bool
     */
    protected function findPostUserHash($post_id = '', $user_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->user_id = $user_id ? $user_id : $this->user_id;
        $this->post_user_like = 'post_user_like_' . $this->post_id . '_' . $this->user_id;
        $postUserKeyData = $this->redis->hGetAll($this->post_user_like);
        $postUserData = $postUserKeyData ? $postUserKeyData : false;
        return $postUserData;
    }

    /**
     * 根据文章id获取点赞用户
     * @param string $post_id
     * @return array
     */
    protected function findIsPostUser($post_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->post_user_like_set = 'post_user_like_set_' . $this->post_id;
        return $this->redis->sMembers($this->post_user_like_set);
    }

    /**
     * 删除redis文章数量
     * @param  int
     * @return int
     */
    protected function unsetRedisPostCounts($post_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->post_counts = 'post_' . $this->post_id . '_counts';
        return $this->redis->del($this->post_counts);
    }

    /**
     * 删除文章集合下的用户
     * @param string $post_id
     * @param string $user_id
     * @return int
     */
    protected function deletePostSetUser($post_id = '', $user_id = '')
    {
        $this->post_id = $post_id ? $post_id : $this->post_id;
        $this->user_id = $user_id ? $user_id : $this->user_id;
        $this->post_user_like_set = 'post_user_like_set_' . $this->post_id;
        return $this->redis->sRem($this->post_user_like_set, $this->user_id);
    }

    /**
     * 删除redis key
     * @param string $key
     * @return int
     */
    protected function deleteRedisPostUser($key = '')
    {
        return $this->redis->del($key);
    }
}