# The PHP 8.0.0+ Image Package

## 环境要求

- PHP >= 8.3（底层已切换为 Intervention Image 4.4.1，该库硬性要求 `php ^8.3`，已在 PHP 8.3.33 实测通过）
- 扩展：`gd`（必需）、`mbstring`（必需，Intervention 4.x 依赖）
- GD 需要编译了 FreeType（`text()` 写文字需要）

## 实现说明

`hulang\Image` 的公共 API 完全不变（方法签名、常量、链式调用都保持原样），底层由原来的手写 GD 实现换成了 **Intervention Image 4.x**（GD 驱动）。

`hulang\ImageIntervention` 保留为 `hulang\Image` 的别名，兼容此前使用该类名的代码，两者完全等价。

对外契约逐项对齐：

- 6 个 `THUMB_*`（`SCALING / FILLED / CENTER / NORTHWEST / SOUTHEAST / FIXED`）、9 个 `WATER_*`、`FLIP_X` / `FLIP_Y` 常量全部保留；
- `save($pathname, $type = null, $quality = 80, $interlace = true)`、`crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)`、`thumb($width, $height, $type = self::THUMB_SCALING)`、`water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)`、`text($text, $font, $size, $color = '#00000000', $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0)` 签名不变；
- `output() / width() / height() / type() / mime() / size() / rotate() / flip() / __destruct()` 同样可用；
- `open()` 接受 `\SplFileInfo` 或字符串路径，异常类型仍是 `hulang\image\Exception`。

## 安装

> composer require topthink/think-image

## 使用

与原版完全一致，调用方无需改动：

~~~
$image = \hulang\Image::open('./image.jpg');
或者
$image = \hulang\Image::open(request()->file('image'));


$image->crop(...)
    ->thumb(...)
    ->water(...)
    ->text(....)
    ->save(..);

~~~

一个完整示例：

~~~
$image = \hulang\Image::open('./image.jpg');
$image->thumb(200, 200, \hulang\Image::THUMB_CENTER)
      ->water('./logo.png', \hulang\Image::WATER_SOUTHEAST, 60)
      ->save('./thumb.jpg', null, 85);
~~~

## 与旧版（手写 GD 实现）的差异

| 项目 | 说明 |
| --- | --- |
| 动图 | Intervention 4.x 原生支持多帧，`thumb / crop / rotate / flip / water / text / save` 会自动逐帧处理，原 `src/image/gif/` 那套手写编解码已移除；42 帧 GIF 处理并保存后仍为 42 帧（已实测） |
| 图片质量 | `save()` 的 `$quality` 对 JPEG / WebP 生效（0-100）；PNG 为无损编码，GIF 不提供质量参数，`$quality` 对这两者无效 |
| 颜色透明 | `text()` 的 `#RRGGBBAA` 中 `AA` 仍按 GD 的 alpha（0 不透明、127 全透明）解析，内部再换算成 Intervention 的颜色对象 |
| 字体路径 | 保留"非 ASCII 路径自动复制到系统临时目录"的处理，解决 Windows 版 GD 打不开中文目录字体的问题 |
| 水印透明度 | `water()` 的 `$alpha`（0-100）映射为 Intervention 的 `transparency = $alpha / 100`；`alpha = 100` 时与旧版逐像素一致，半透明时算法略有差别（旧版走 `imagecopymerge`，新版按 alpha 通道缩放） |
| 缩略图取整 | 等比例缩放（`THUMB_SCALING`）按旧版公式 `(int)($w * $scale)` 向下取整，与旧版尺寸完全一致 |

## 改造内容

| 文件 | 改动 |
| --- | --- |
| `src/Image.php` | 底层换为 Intervention Image 4.x，公共 API 不变；新增字体路径解析（中文目录兜底）、隔行 GIF 合法性兜底、`auto` 取色的越界保护 |
| `src/ImageIntervention.php` | 改为 `hulang\Image` 的别名类，保留旧类名兼容 |
| `src/image/gif/*` | 已删除（`Gif.php`、`Decoder.php`、`Encoder.php`），多帧处理由 Intervention 原生完成 |
| `composer.json` | 依赖 `intervention/image: ^4.4`、`ext-mbstring`；`php` 约束由 `>=8.0` 改为 `>=8.3`；开发依赖 `phpunit/phpunit ^9.6` |
| `tests/autoload.php`、`tests/php8-check.php` | 移除对 gif 目录的手动 `require`，改走 Composer 自动加载 |
| `phpunit.xml` | PHPUnit 9 配置格式，测试文件按 `*Test.php` 匹配 |

## 测试

~~~
composer install
php vendor/phpunit/phpunit/phpunit
~~~

当前结果：`OK (21 tests, 91 assertions)`。

另外提供两个不依赖 PHPUnit 的单文件自检脚本，覆盖打开、缩放、裁剪、水印、文字、旋转、翻转、GIF 处理与保存：

~~~
php tests/php8-check.php          # 13 项基础自检
php tests/intervention-check.php  # 38 项 API 全覆盖自检
~~~
