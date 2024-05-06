#### The PHP 7.2+ Image Package

#### 环境

- php >=7.2.0

##### 安装

```php
composer require hulang/php-image
```

##### 使用

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
