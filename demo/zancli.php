<?php
/**
 * Created by PhpStorm.
 * User: wangwf
 * Date: 17-6-16
 * Time: 下午11:07
 */
require '../autoload.php';
$zan = new Zan();
while (TRUE) {
    $zan->execute(10);
}