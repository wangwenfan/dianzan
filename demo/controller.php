<?php
/**
 * Created by PhpStorm.
 * User: wangwf
 * Date: 17-6-17
 * Time: 下午5:54
 */
require '../autoload.php';
$like = new Like();
$user_id = $_POST['user_id'];
$post_id = $_POST['post_id'];
$r = $like->giveFavour($user_id,$post_id);
echo json_encode($r);