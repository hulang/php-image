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
    /* 缩略图相关常量定义（值直接引用 hulang\Image，保证两处永远一致） */
    const THUMB_SCALING   = Image::THUMB_SCALING; //常量，标识缩略图等比例缩放类型
    const THUMB_FILLED    = Image::THUMB_FILLED; //常量，标识缩略图缩放后填充类型
    const THUMB_CENTER    = Image::THUMB_CENTER; //常量，标识缩略图居中裁剪类型
    const THUMB_NORTHWEST = Image::THUMB_NORTHWEST; //常量，标识缩略图左上角裁剪类型
    const THUMB_SOUTHEAST = Image::THUMB_SOUTHEAST; //常量，标识缩略图右下角裁剪类型
    const THUMB_FIXED     = Image::THUMB_FIXED; //常量，标识缩略图固定尺寸缩放类型

    /* 水印相关常量定义（值直接引用 hulang\Image） */
    const WATER_NORTHWEST = Image::WATER_NORTHWEST; //常量，标识左上角水印
    const WATER_NORTH     = Image::WATER_NORTH; //常量，标识上居中水印
    const WATER_NORTHEAST = Image::WATER_NORTHEAST; //常量，标识右上角水印
    const WATER_WEST      = Image::WATER_WEST; //常量，标识左居中水印
    const WATER_CENTER    = Image::WATER_CENTER; //常量，标识居中水印
    const WATER_EAST      = Image::WATER_EAST; //常量，标识右居中水印
    const WATER_SOUTHWEST = Image::WATER_SOUTHWEST; //常量，标识左下角水印
    const WATER_SOUTH     = Image::WATER_SOUTH; //常量，标识下居中水印
    const WATER_SOUTHEAST = Image::WATER_SOUTHEAST; //常量，标识右下角水印

    /* 翻转相关常量定义（值直接引用 hulang\Image） */
    const FLIP_X = Image::FLIP_X; //X轴翻转（上下翻转）
    const FLIP_Y = Image::FLIP_Y; //Y轴翻转（左右翻转）

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
