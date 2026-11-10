<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 静态快捷入口
// +----------------------------------------------------------------------
// | 说明：本类不继承 hulang\Image，只提供一组静态方法。
// |      每个操作方法都是「打开源图 → 处理 → 保存到新文件」一步到位：
// |      第一个参数是源文件，第二个参数是保存的新文件，后面才是原方法参数。
// |      例：ImageKit::crop('a.jpg', 'b.jpg', 200, 150);
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理类（静态快捷方式，内部调用 hulang\Image）
 *
 * 用法：
 * ~~~
 * \hulang\ImageKit::thumb('./a.jpg', './b.jpg', 200, 200, \hulang\ImageKit::THUMB_CENTER);
 * \hulang\ImageKit::water('./a.jpg', './c.jpg', './logo.png', \hulang\ImageKit::WATER_SOUTHEAST, 60);
 * ~~~
 * 每个方法内部都按「打开 → 处理 → 保存」执行，返回实际写入的目标文件路径。
 */
class ImageKit
{
    //引入常量定义，与 hulang\Image 共用同一份值（统一维护在 hulang\ImageConstants 中）
    use ImageConstants;

    /**
     * 裁剪图像
     *
     * @param string        $source 源图片路径
     * @param string        $save   处理后的保存路径
     * @param integer       $w      裁剪区域宽度
     * @param integer       $h      裁剪区域高度
     * @param integer       $x      裁剪区域 x 坐标
     * @param integer       $y      裁剪区域 y 坐标
     * @param null|integer  $width  图像保存宽度
     * @param null|integer  $height 图像保存高度
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function crop($source, $save, $w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        //打开源图（校验与解码都由 hulang\Image 完成）
        $image = Image::open($source);

        //按原库的参数执行裁剪
        $image->crop($w, $h, $x, $y, $width, $height);

        //按目标文件的扩展名决定输出格式并写入
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径，方便调用方直接使用
        return $save;
    }

    /**
     * 生成缩略图
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $width  缩略图最大宽度
     * @param integer $height 缩略图最大高度
     * @param int     $type   缩略图裁剪类型
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function thumb($source, $save, $width, $height, $type = self::THUMB_SCALING)
    {
        //打开源图
        $image = Image::open($source);

        //按指定类型生成缩略图
        $image->thumb($width, $height, $type);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 添加图片水印
     *
     * @param string    $source 源图片路径
     * @param string    $save   处理后的保存路径
     * @param string    $water  水印图片路径
     * @param int|array $locate 水印位置
     * @param int       $alpha  透明度（0-100，100 为完全不透明）
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function water($source, $save, $water, $locate = self::WATER_SOUTHEAST, $alpha = 100)
    {
        //打开源图
        $image = Image::open($source);

        //叠加水印
        $image->water($water, $locate, $alpha);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 图像添加文字
     *
     * @param string        $source 源图片路径
     * @param string        $save   处理后的保存路径
     * @param string        $text   添加的文字
     * @param string        $font   字体路径
     * @param integer       $size   字号
     * @param string        $color  文字颜色（#RRGGBBAA，AA 为 GD 的 alpha 0-127）
     * @param int|array     $locate 文字写入位置
     * @param integer|array $offset 文字相对当前位置的偏移量
     * @param integer       $angle  文字倾斜角度
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function text($source, $save, $text, $font, $size, $color = '#00000000', $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        //打开源图
        $image = Image::open($source);

        //写入文字
        $image->text($text, $font, $size, $color, $locate, $offset, $angle);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 旋转图像
     *
     * @param string  $source  源图片路径
     * @param string  $save    处理后的保存路径
     * @param integer $degrees 顺时针旋转的度数
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function rotate($source, $save, $degrees = 90)
    {
        //打开源图
        $image = Image::open($source);

        //旋转（动画 GIF 会逐帧处理）
        $image->rotate($degrees);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 翻转图像
     *
     * @param string  $source    源图片路径
     * @param string  $save      处理后的保存路径
     * @param integer $direction 翻转轴，X 轴（上下）或 Y 轴（左右）
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function flip($source, $save, $direction = self::FLIP_X)
    {
        //打开源图
        $image = Image::open($source);

        //翻转
        $image->flip($direction);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 按 EXIF 方向自动摆正图像并保存
     *
     * @param string $source 源图片路径
     * @param string $save   处理后的保存路径
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function orient($source, $save)
    {
        //打开源图
        $image = Image::open($source);

        //按 EXIF 的 Orientation 标记把像素转正
        $image->orient();

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 自动裁掉四周纯色边并保存
     *
     * @param string  $source    源图片路径
     * @param string  $save      处理后的保存路径
     * @param integer $tolerance 容差，0 表示严格按四角像素颜色匹配
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function trim($source, $save, $tolerance = 0)
    {
        //打开源图
        $image = Image::open($source);

        //从四角向内收缩，去掉纯色边
        $image->trim($tolerance);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 锐化图像并保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $level  锐化强度 0-100
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function sharpen($source, $save, $level = 10)
    {
        //打开源图
        $image = Image::open($source);

        //按强度锐化
        $image->sharpen($level);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 模糊图像并保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $level  模糊强度 0-100
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function blur($source, $save, $level = 5)
    {
        //打开源图
        $image = Image::open($source);

        //按强度模糊
        $image->blur($level);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 转为灰度图并保存
     *
     * @param string $source 源图片路径
     * @param string $save   处理后的保存路径
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function grayscale($source, $save)
    {
        //打开源图
        $image = Image::open($source);

        //转成灰度
        $image->grayscale();

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 调整亮度并保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $level  亮度级别，-100 最暗、0 原样、100 最亮
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function brightness($source, $save, $level)
    {
        //打开源图
        $image = Image::open($source);

        //调整亮度
        $image->brightness($level);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 调整对比度并保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $level  对比度级别，-100 最柔和、0 原样、100 最锐利
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function contrast($source, $save, $level)
    {
        //打开源图
        $image = Image::open($source);

        //调整对比度
        $image->contrast($level);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 像素化（马赛克）后保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $size   像素块边长，数值越大越模糊
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function pixelate($source, $save, $size = 10)
    {
        //打开源图
        $image = Image::open($source);

        //打马赛克
        $image->pixelate($size);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 把图像放到指定尺寸的画布上并保存
     *
     * @param string      $source     源图片路径
     * @param string      $save       处理后的保存路径
     * @param integer     $width      画布宽度
     * @param integer     $height     画布高度
     * @param null|string    $background 背景色，如 #ffffff；传 null 表示透明
     * @param integer|string $position   原图位置：可用 self::POS_CENTER 等 9 个常量，
     *                                   也可传 center、top-left、bottom-right 等字符串
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function resizeCanvas($source, $save, $width, $height, $background = '#ffffff', $position = self::POS_CENTER)
    {
        //打开源图
        $image = Image::open($source);

        //按目标尺寸重建画布
        $image->resizeCanvas($width, $height, $background, $position);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 等比缩放裁切填满指定尺寸并保存
     *
     * @param string  $source   源图片路径
     * @param string  $save     处理后的保存路径
     * @param integer        $width    目标宽度
     * @param integer        $height   目标高度
     * @param integer|string $position 保留区域位置：可用 self::POS_CENTER 等 9 个常量，
     *                                 也可传 center、top、bottom 等字符串
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function cover($source, $save, $width, $height, $position = self::POS_CENTER)
    {
        //打开源图
        $image = Image::open($source);

        //等比填满后裁切
        $image->cover($width, $height, $position);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 等比缩放完整容纳并保存（空缺补背景色）
     *
     * @param string      $source     源图片路径
     * @param string      $save       处理后的保存路径
     * @param integer        $width      目标宽度
     * @param integer        $height     目标高度
     * @param null|string    $background 空缺背景色，传 null 表示透明
     * @param integer|string $position   原图在区域内位置：可用 self::POS_CENTER 等 9 个
     *                                   常量，也可传 center、top-left 等字符串
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function contain($source, $save, $width, $height, $background = '#ffffff', $position = self::POS_CENTER)
    {
        //打开源图
        $image = Image::open($source);

        //等比缩放到完整放下
        $image->contain($width, $height, $background, $position);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 把透明区域填成指定颜色并保存
     *
     * @param string      $source 源图片路径
     * @param string      $save   处理后的保存路径
     * @param null|string $color  填充色，如 #ffffff；传 null 表示不处理
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function fillTransparentAreas($source, $save, $color = '#ffffff')
    {
        //打开源图
        $image = Image::open($source);

        //透明区域刷底色
        $image->fillTransparentAreas($color);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 把另一张图贴到源图上并保存
     *
     * @param string       $source       源图片路径
     * @param string       $save         处理后的保存路径
     * @param string|Image $image        要贴上去的图片路径，或 Image 实例
     * @param integer      $x            相对对齐点的横向偏移
     * @param integer      $y            相对对齐点的纵向偏移
     * @param integer|string $position     对齐位置：可用 self::POS_NORTHWEST 等 9 个常量，
     *                                     也可传 top-left、center、bottom-right 等字符串
     * @param float          $transparency 透明度，0 全透明、1 完全不透明
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function insert($source, $save, $image, $x = 0, $y = 0, $position = self::POS_NORTHWEST, $transparency = 1)
    {
        //打开源图
        $canvas = Image::open($source);

        //把目标图贴到指定位置
        $canvas->insert($image, $x, $y, $position, $transparency);

        //按目标扩展名保存
        $canvas->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 直接返回编码后的二进制内容（不写文件）
     *
     * @param string      $source  源图片路径
     * @param null|string $type    目标类型，如 jpg、png、webp；不传则沿用源类型
     * @param integer     $quality 质量 0-100，只对 JPEG / WebP 生效
     *
     * @return string 图片二进制字符串
     * @throws image\Exception
     */
    public static function encode($source, $type = null, $quality = 80)
    {
        //打开源图后直接编码返回
        return Image::open($source)->encode($type, $quality);
    }

    /**
     * 读取图像的帧数
     *
     * @param string $source 源图片路径
     *
     * @return integer 帧数，静态图返回 1
     * @throws image\Exception
     */
    public static function count($source)
    {
        //打开后取帧数
        return Image::open($source)->count();
    }

    /**
     * 判断是不是动图
     *
     * @param string $source 源图片路径
     *
     * @return boolean 帧数大于 1 返回 true
     * @throws image\Exception
     */
    public static function isAnimated($source)
    {
        //打开后判断帧数
        return Image::open($source)->isAnimated();
    }

    /**
     * 读取动图的循环次数
     *
     * 注意 GIF 的 loop 字段记录的是"重复次数"，比实际播放次数少 1：
     * 读到 2 表示浏览器会播 3 遍，读到 0 表示无限循环。
     *
     * @param string $source 源图片路径
     *
     * @return integer 循环次数，0 表示无限循环
     * @throws image\Exception
     */
    public static function loops($source)
    {
        //打开后读取循环次数
        return Image::open($source)->loops();
    }

    /**
     * 设置动图循环次数并保存
     *
     * @param string  $source 源图片路径
     * @param string  $save   处理后的保存路径
     * @param integer $loops  循环次数，0 表示无限循环；设为 3 时实际播放 3 遍，
     *                        再用 loops() 读回来会是 2（GIF 记录的是重复次数）
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function setLoops($source, $save, $loops)
    {
        //打开源图
        $image = Image::open($source);

        //写回循环次数
        $image->setLoops($loops);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 去掉动画只留一帧并保存
     *
     * @param string  $source   源图片路径
     * @param string  $save     处理后的保存路径
     * @param integer $position 保留第几帧，0 表示第一帧
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function removeAnimation($source, $save, $position = 0)
    {
        //打开源图
        $image = Image::open($source);

        //只保留指定帧
        $image->removeAnimation($position);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 截取动图的一段帧并保存
     *
     * @param string      $source 源图片路径
     * @param string      $save   处理后的保存路径
     * @param integer     $offset 从第几帧开始，0 表示第一帧
     * @param null|integer $length 取多少帧，null 表示取到结尾
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function sliceAnimation($source, $save, $offset = 0, $length = null)
    {
        //打开源图
        $image = Image::open($source);

        //按区间截取帧
        $image->sliceAnimation($offset, $length);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 读取图像分辨率（DPI）
     *
     * 没有 DPI 元数据的图片（网上下载的图基本都是）通常返回 1，
     * 需要按 300dpi 交付时用 setResolution() 显式设置。
     *
     * @param string $source 源图片路径
     *
     * @return array 0 - 水平 DPI，1 - 垂直 DPI
     * @throws image\Exception
     */
    public static function resolution($source)
    {
        //打开后读取 DPI
        return Image::open($source)->resolution();
    }

    /**
     * 设置图像分辨率（DPI）并保存
     *
     * @param string     $source 源图片路径
     * @param string     $save   处理后的保存路径
     * @param float      $x      水平 DPI
     * @param null|float $y      垂直 DPI，不传时与水平方向相同
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function setResolution($source, $save, $x, $y = null)
    {
        //打开源图
        $image = Image::open($source);

        //写入新的 DPI
        $image->setResolution($x, $y);

        //按目标扩展名保存
        $image->save($save, self::extensionOf($save));

        //返回实际写入的路径
        return $save;
    }

    /**
     * 提取图像主色调
     *
     * @param string  $source 源图片路径
     * @param integer $count  取几个颜色
     *
     * @return array 十六进制颜色数组，如 ['#f5c26b', '#2b2b2b']
     * @throws image\Exception
     */
    public static function colors($source, $count = 5)
    {
        //打开后提取主色
        return Image::open($source)->colors($count);
    }

    /**
     * 仅保存（可用于格式转换或重新压缩，不做任何画面处理）
     *
     * @param string      $source    源图片路径
     * @param string      $save      保存路径
     * @param null|string $type      图像类型，不传则按目标文件扩展名
     * @param int         $quality   图像质量（JPEG / WebP 有效）
     * @param bool        $interlace 是否对 JPEG 图像设置隔行扫描
     *
     * @return string 实际写入的目标文件路径
     * @throws image\Exception
     */
    public static function save($source, $save, $type = null, $quality = 80, $interlace = true)
    {
        //打开源图
        $image = Image::open($source);

        //没指定类型时按目标扩展名推断
        if (null === $type) {
            $type = self::extensionOf($save);
        }

        //写入文件
        $image->save($save, $type, $quality, $interlace);

        //返回实际写入的路径
        return $save;
    }

    /**
     * http 直接输出图片
     *
     * 注意：内部会在输出后 exit，只能放在脚本最后一步。
     *
     * @param string $source 源图片路径
     * @return void
     * @throws image\Exception
     */
    public static function output($source)
    {
        //打开源图后直接输出，不再保存
        Image::open($source)->output();
    }

    /**
     * 返回图像宽度
     *
     * @param string $source 源图片路径
     * @return int 图像宽度
     * @throws image\Exception
     */
    public static function width($source)
    {
        //打开后读取宽度
        return Image::open($source)->width();
    }

    /**
     * 返回图像高度
     *
     * @param string $source 源图片路径
     * @return int 图像高度
     * @throws image\Exception
     */
    public static function height($source)
    {
        //打开后读取高度
        return Image::open($source)->height();
    }

    /**
     * 返回图像类型
     *
     * @param string $source 源图片路径
     * @return string 图像类型（jpg、png、gif 等）
     * @throws image\Exception
     */
    public static function type($source)
    {
        //打开后读取类型
        return Image::open($source)->type();
    }

    /**
     * 返回图像 MIME 类型
     *
     * @param string $source 源图片路径
     * @return string 图像 MIME 类型
     * @throws image\Exception
     */
    public static function mime($source)
    {
        //打开后读取 MIME
        return Image::open($source)->mime();
    }

    /**
     * 返回图像尺寸数组 0 - 宽度，1 - 高度
     *
     * @param string $source 源图片路径
     * @return array 图像尺寸
     * @throws image\Exception
     */
    public static function size($source)
    {
        //打开后读取尺寸数组
        return Image::open($source)->size();
    }

    /**
     * 从保存路径中取出小写扩展名
     *
     * 目标文件没写扩展名时返回 null，交给 hulang\Image 沿用源文件类型。
     *
     * @param string $path 目标文件路径
     * @return null|string 扩展名
     */
    protected static function extensionOf($path)
    {
        //取扩展名并统一转小写
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        //没有扩展名时返回 null
        if ('' === $ext) {
            return null;
        }

        //返回规范化的扩展名
        return $ext;
    }
}
