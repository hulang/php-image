# The ThinkPHP6 Image Package

## 安装

> composer require hulang/think-image

## 使用

~~~
$image = \think\Image::open('./image.jpg');
或者
$image = \think\Image::open(request()->file('image'));


$image->crop(...)
    ->thumb(...)
    ->water(...)
    ->text(....)
    ->save(..);

~~~