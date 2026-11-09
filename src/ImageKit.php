<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 独立入口类
// +----------------------------------------------------------------------
// | 说明：本类不继承 hulang\Image，而是在内部持有一个 hulang\Image
// |      实例，逐个方法转调。对外用法：
// |      ImageKit::open('a.jpg')->thumb(200, 200)->save('b.jpg');
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理类（内部委托 hulang\Image 实现的独立入口）
 *
 * 与 hulang\Image 的区别只在结构上：
 * ~~~
 * // 底层实现全部来自 hulang\Image
 * $image = \hulang\ImageKit::open('./a.jpg');
 * $image->thumb(200, 200)->save('./b.jpg');
 * ~~~
 * 调用方式、参数顺序、链式风格与 \hulang\Image 完全一致。
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
     * 内部持有的图片处理对象
     *
     * @var Image
     */
    protected $image;

    /**
     * 构造方法：包装一个已经打开的 hulang\Image 对象
     *
     * 构造函数设为 protected，统一通过 open() 创建实例，
     * 避免外部直接 new 出没有装载图片的空壳对象。
     *
     * @param Image $image 已打开图片的 hulang\Image 实例
     */
    protected function __construct(Image $image)
    {
        //保存内部实现对象，后续所有操作都转调给它
        $this->image = $image;
    }

    /**
     * 打开一个图片文件
     *
     * @param \SplFileInfo|string $file 图片文件或路径
     * @return $this
     * @throws image\Exception
     */
    public static function open($file)
    {
        //交给 hulang\Image 完成校验与解码，本类只负责包装返回
        return new static(Image::open($file));
    }

    /**
     * 取出内部持有的图片对象
     *
     * 需要直接使用 hulang\Image 或 Intervention 原始能力时可以用它，
     * 例如：$obj->image()->save('./b.jpg');
     *
     * @return Image
     */
    public function image()
    {
        //直接返回内部对象，不做拷贝
        return $this->image;
    }

    /**
     * 保存图像
     *
     * @param string      $pathname  图像保存路径名称
     * @param null|string $type      图像类型
     * @param int         $quality   图像质量（JPEG / WebP 有效）
     * @param bool        $interlace 是否对 JPEG 图像设置隔行扫描
     * @return $this
     */
    public function save($pathname, $type = null, $quality = 80, $interlace = true)
    {
        //转调内部实现完成编码与写入
        $this->image->save($pathname, $type, $quality, $interlace);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * http 输出图片
     *
     * 注意：内部实现会在输出后直接 exit，所以它只能作为链式调用的最后一步。
     *
     * @return void
     */
    public function output()
    {
        //转调内部实现，异常与输出行为完全一致
        $this->image->output();
    }

    /**
     * 返回图像宽度
     *
     * @return int 图像宽度
     */
    public function width()
    {
        //转调内部实现读取宽度
        return $this->image->width();
    }

    /**
     * 返回图像高度
     *
     * @return int 图像高度
     */
    public function height()
    {
        //转调内部实现读取高度
        return $this->image->height();
    }

    /**
     * 返回图像类型
     *
     * @return string 图像类型
     */
    public function type()
    {
        //转调内部实现读取类型（jpg、png、gif 等）
        return $this->image->type();
    }

    /**
     * 返回图像 MIME 类型
     *
     * @return string 图像 MIME 类型
     */
    public function mime()
    {
        //转调内部实现读取 MIME
        return $this->image->mime();
    }

    /**
     * 返回图像尺寸数组 0 - 图像宽度，1 - 图像高度
     *
     * @return array 图像尺寸
     */
    public function size()
    {
        //转调内部实现读取尺寸数组
        return $this->image->size();
    }

    /**
     * 旋转图像
     *
     * @param int $degrees 顺时针旋转的度数
     * @return $this
     */
    public function rotate($degrees = 90)
    {
        //转调内部实现旋转，动画 GIF 会逐帧处理
        $this->image->rotate($degrees);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * 翻转图像
     *
     * @param integer $direction 翻转轴，X 轴（上下）或 Y 轴（左右）
     * @return $this
     * @throws image\Exception
     */
    public function flip($direction = self::FLIP_X)
    {
        //转调内部实现翻转，非法的方向值由内部抛出异常
        $this->image->flip($direction);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * 裁剪图像
     *
     * @param  integer      $w      裁剪区域宽度
     * @param  integer      $h      裁剪区域高度
     * @param  integer      $x      裁剪区域 x 坐标
     * @param  integer      $y      裁剪区域 y 坐标
     * @param  null|integer $width  图像保存宽度
     * @param  null|integer $height 图像保存高度
     *
     * @return $this
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        //转调内部实现完成裁剪与二次缩放
        $this->image->crop($w, $h, $x, $y, $width, $height);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * 生成缩略图
     *
     * @param  integer $width  缩略图最大宽度
     * @param  integer $height 缩略图最大高度
     * @param  int     $type   缩略图裁剪类型
     *
     * @return $this
     * @throws image\Exception
     */
    public function thumb($width, $height, $type = self::THUMB_SCALING)
    {
        //转调内部实现生成缩略图
        $this->image->thumb($width, $height, $type);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * 添加水印
     *
     * @param string    $source 水印图片路径
     * @param int|array $locate 水印位置
     * @param int       $alpha  透明度（0-100，100 为完全不透明）
     * @return $this
     * @throws image\Exception
     */
    public function water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)
    {
        //转调内部实现叠加水印
        $this->image->water($source, $locate, $alpha);

        //返回本类实例，保持链式调用
        return $this;
    }

    /**
     * 图像添加文字
     *
     * @param string        $text   添加的文字
     * @param string        $font   字体路径
     * @param integer       $size   字号
     * @param string        $color  文字颜色（#RRGGBBAA，AA 为 GD 的 alpha 0-127）
     * @param int|array     $locate 文字写入位置
     * @param integer|array $offset 文字相对当前位置的偏移量
     * @param integer       $angle  文字倾斜角度
     *
     * @return $this
     * @throws image\Exception
     */
    public function text($text, $font, $size, $color = '#00000000', $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        //转调内部实现写入文字（含颜色解析、坐标计算与字体路径处理）
        $this->image->text($text, $font, $size, $color, $locate, $offset, $angle);

        //返回本类实例，保持链式调用
        return $this;
    }
}
