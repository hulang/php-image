# The PHP 8.3.0+ Image Package

## 环境要求

- PHP >= 8.3（底层已切换为 Intervention Image 4.4.1，该库硬性要求 `php ^8.3`，已在 PHP 8.3.33 实测通过）
- 扩展：`gd`（必需）、`mbstring`（必需，Intervention 4.x 依赖）
- GD 需要编译了 FreeType（`text()` 写文字需要）

## 实现说明

`hulang\Image` 的公共 API 完全不变（方法签名、常量、链式调用都保持原样），底层由原来的手写 GD 实现换成了 **Intervention Image 4.x**（GD 驱动）。

`hulang\ImageKit` 是纯静态的快捷入口类：不继承 `hulang\Image`，每个操作方法都是「打开源图 → 处理 → 保存」一步到位。第一个参数是源文件、第二个参数是保存的新文件，后面才是原方法参数，返回值是实际写入的目标文件路径。

`hulang\ImageIntervention` 作为历史类名保留，静态方法全部继承自 `hulang\ImageKit`。它不再提供 `open()` 链式写法，需要对象方式连续操作时请直接用 `hulang\Image`。

对外契约逐项对齐：

- 6 个 `THUMB_*`（`SCALING / FILLED / CENTER / NORTHWEST / SOUTHEAST / FIXED`）、9 个 `WATER_*`、`FLIP_X` / `FLIP_Y` 常量全部保留，集中定义在 `src/ImageConstants.php` 的 trait 中，`Image` 与 `ImageKit` 共用同一份值；
- `save($pathname, $type = null, $quality = 80, $interlace = true)`、`crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)`、`thumb($width, $height, $type = self::THUMB_SCALING)`、`water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)`、`text($text, $font, $size, $color = '#00000000', $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0)` 签名不变；
- `output() / width() / height() / type() / mime() / size() / rotate() / flip() / __destruct()` 同样可用；
- 新增图像处理方法：`orient() / trim() / sharpen() / blur() / grayscale() / brightness() / contrast() / pixelate()`；
- 新增尺寸与变换方法：`resizeCanvas() / cover() / contain()`、`insert()`（按坐标自由贴图）、`encode()`（直接返回二进制、不写文件）；
- 新增动图方法：`count() / isAnimated() / loops() / setLoops() / removeAnimation() / sliceAnimation()`；
- 新增元信息方法：`resolution() / setResolution()`（DPI）、`colors()`（提取主色）；
- 以上方法在 `Image` 上是链式调用，在 `ImageKit` 上都有对应的静态一步到位版本；
- `open()` 接受 `\SplFileInfo` 或字符串路径，异常类型仍是 `hulang\image\Exception`。

## 安装

> composer require hulang/php-image

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

也可以换成 `ImageKit` 的静态写法，一步到位——第一个参数是源文件，第二个参数是保存的新文件：

~~~
\hulang\ImageKit::thumb('./image.jpg', './thumb.jpg', 200, 200, \hulang\ImageKit::THUMB_CENTER);
\hulang\ImageKit::water('./image.jpg', './water.jpg', './logo.png', \hulang\ImageKit::WATER_SOUTHEAST, 60);
\hulang\ImageKit::crop('./image.jpg', './crop.jpg', 400, 300, 100, 100, 200, 150);
\hulang\ImageKit::text('./image.jpg', './text.jpg', '文字内容', './font.ttf', 24);
\hulang\ImageKit::rotate('./image.jpg', './rotate.jpg', 90);

//只读信息只需要传源文件
$size  = \hulang\ImageKit::size('./image.jpg');
$width = \hulang\ImageKit::width('./image.jpg');
~~~

返回值是目标文件路径，可以直接拿去拼 URL 或入库。

常用的图像修正方法同样是一步到位：

~~~
//手机竖拍照片按 EXIF 方向转正
\hulang\ImageKit::orient('./a.jpg', './b.jpg');

//扫描件、截图自动去掉四周纯色白边
\hulang\ImageKit::trim('./a.jpg', './b.jpg');

//缩图后补清晰度、轻微模糊、转黑白（Image 上可直接链式调用）
\hulang\ImageKit::sharpen('./a.jpg', './b.jpg', 10);
\hulang\ImageKit::blur('./a.jpg', './b.jpg', 5);
\hulang\ImageKit::grayscale('./a.jpg', './b.jpg');
~~~

调色、画布与动图相关的方法同样是同样的调用方式：

~~~
//调色：亮度、对比度范围都是 -100 ~ 100，pixelate 用来打码
\hulang\ImageKit::brightness('./a.jpg', './b.jpg', 30);
\hulang\ImageKit::contrast('./a.jpg', './b.jpg', 20);
\hulang\ImageKit::pixelate('./a.jpg', './b.jpg', 15);

//统一画布（不缩放图像，只补边或裁边）、头像裁切、完整容纳
\hulang\ImageKit::resizeCanvas('./a.jpg', './b.jpg', 300, 300);
\hulang\ImageKit::cover('./a.jpg', './b.jpg', 200, 200);
\hulang\ImageKit::contain('./a.jpg', './b.jpg', 200, 200, '#ffffff');

//把另一张图贴到指定位置（比 water 的九宫格更灵活）
\hulang\ImageKit::insert('./a.jpg', './b.jpg', './badge.png', 10, 10, 'top-left', 0.8);

//透明 PNG 转 JPG 前，先把透明区域刷成底色
\hulang\ImageKit::fillTransparentAreas('./alpha.png', './b.jpg', '#ffffff');

//动图：取第一帧做封面、截取前 10 帧、改循环次数
\hulang\ImageKit::removeAnimation('./a.gif', './cover.gif', 0);
\hulang\ImageKit::sliceAnimation('./a.gif', './part.gif', 0, 10);
\hulang\ImageKit::setLoops('./a.gif', './loop.gif', 3);

echo \hulang\ImageKit::count('./a.gif');      // 42（帧数）
var_dump(\hulang\ImageKit::isAnimated('./a.gif'));  // true

//DPI 与主色
\hulang\ImageKit::setResolution('./a.jpg', './300dpi.jpg', 300);
$colors = \hulang\ImageKit::colors('./a.jpg', 5);   // ['#ffad19', '#ffc55a', ...]

//直接拿二进制，不落盘（适合直接输出到浏览器或上传对象存储）
$binary = \hulang\ImageKit::encode('./a.jpg', 'webp', 80);
~~~

链式写法同样可用：

~~~
\hulang\Image::open('./a.jpg')
    ->orient()
    ->trim()
    ->sharpen(10)
    ->save('./b.jpg');

\hulang\Image::open('./a.jpg')
    ->cover(200, 200)
    ->brightness(20)
    ->save('./b.jpg');
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
| `src/Image.php` | 底层换为 Intervention Image 4.x，公共 API 不变；新增字体路径解析（中文目录兜底）、隔行 GIF 合法性兜底、`auto` 取色的越界保护；另新增 `orient / trim / sharpen / blur / grayscale / brightness / contrast / pixelate / resizeCanvas / cover / contain / insert / encode / count / isAnimated / loops / setLoops / removeAnimation / sliceAnimation / resolution / setResolution / colors` 共 22 个图像处理与查询方法 |
| `src/ImageConstants.php` | 新增常量 trait：17 个 `THUMB_*` / `WATER_*` / `FLIP_*` 常量集中定义，`Image` 与 `ImageKit` 通过 `use` 共用，只维护一份值 |
| `src/ImageKit.php` | 新增静态快捷入口类：不继承 `Image`，每个方法按「打开源图 → 处理 → 保存」执行，参数为「源文件、目标文件、原方法参数」；上述新增方法均提供对应的静态一步到位版本 |
| `src/ImageIntervention.php` | 历史类名，静态方法全部继承自 `hulang\ImageKit`，不再提供 `open()` 链式写法 |
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
php tests/intervention-check.php  # 66 项 ImageKit 静态 API 自检
~~~
