### The ThinkPHP8 Image Package

#### 安装

> composer require hulang/php-image

#### 使用

~~~php
$image = \hulang\Image::open('./image.jpg');
或者
$image = \hulang\Image::open(request()->file('image'));


$image->crop(...)
    ->thumb(...)
    ->water(...)
    ->text(....)
    ->save(..);

~~~
