<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
define('TEST_PATH', __DIR__ . '/');

//优先使用 Composer 自动加载（安装依赖后走这条）
$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
    return;
}

//没有安装依赖时直接加载源码，方便单独运行测试
require __DIR__ . '/../src/image/Exception.php';
require __DIR__ . '/../src/Image.php';
