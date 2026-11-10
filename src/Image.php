<?php

// +----------------------------------------------------------------------
// | 说明：底层已切换为 Intervention Image 4.x（GD 驱动），
// |      公共 API（常量、方法签名、链式调用）与原版完全一致。
// +----------------------------------------------------------------------

namespace hulang;

use Intervention\Image\Alignment;
use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Direction;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use hulang\image\Exception as ImageException;

/**
 * 图片处理类（Intervention Image 4.x 实现）
 *
 * 用法与原版一致：
 * ~~~
 * $image = \hulang\Image::open('./a.jpg');
 * $image->thumb(200, 200)->water('./logo.png')->save('./b.jpg');
 * ~~~
 */
class Image
{
    //引入常量定义，常量值统一维护在 hulang\ImageConstants 中
    use ImageConstants;

    /**
     * Intervention 图片对象
     *
     * @var \Intervention\Image\Image
     */
    protected $im;

    /**
     * 图像信息，包括 width, height, type, mime, size
     *
     * @var array
     */
    protected $info;

    /**
     * 图片管理器（全局复用，避免重复创建驱动）
     *
     * @var ImageManager|null
     */
    protected static $manager;

    /**
     * 构造方法：读取图片并建立 Intervention 图片对象
     *
     * @param \SplFileInfo $file 图片文件
     * @throws ImageException
     */
    protected function __construct(\SplFileInfo $file)
    {
        //获取图像信息
        $info = @getimagesize($file->getPathname());

        //检测图像合法性
        if (false === $info) {
            throw new ImageException('Illegal image file');
        }
        //部分合法 GIF（例如隔行编码的 GIF）不会返回 bits，此时再用文件头确认一次
        if (IMAGETYPE_GIF === $info[2] && empty($info['bits'])) {
            $head = @file_get_contents($file->getPathname(), false, null, 0, 6);
            if ('GIF89a' !== $head && 'GIF87a' !== $head) {
                throw new ImageException('Illegal image file');
            }
        }

        //设置图像信息
        $this->info = [
            'width'  => $info[0],
            'height' => $info[1],
            'type'   => image_type_to_extension($info[2], false),
            'mime'   => $info['mime'],
        ];

        //交给 Intervention 解码（GIF 动画会解码成多帧）
        try {
            $this->im = self::manager()->decodeSplFileInfo($file);
        } catch (\Throwable $e) {
            throw new ImageException('Failed to create image resources!');
        }
    }

    /**
     * 打开一个图片文件
     *
     * @param \SplFileInfo|string $file 图片文件或路径
     * @return Image
     * @throws ImageException
     */
    public static function open($file)
    {
        if (is_string($file)) {
            $file = new \SplFileInfo($file);
        }
        if (!$file->isFile()) {
            throw new ImageException('image file not exist');
        }

        //使用晚绑定：子类调用 open() 时实例化的是子类自身
        return new static($file);
    }

    /**
     * 获取图片管理器（懒加载单例）
     *
     * @return ImageManager
     */
    protected static function manager()
    {
        if (null === self::$manager) {
            self::$manager = new ImageManager(new Driver());
        }

        return self::$manager;
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
        //自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        //JPEG 统一写成 jpg，Intervention 的扩展名编码器两种写法都认
        if ('jpeg' == $type) {
            $type = 'jpg';
        }

        //按不同格式准备编码参数
        $options = [];
        if (in_array($type, ['jpg', 'webp'], true)) {
            $options['quality'] = (int) $quality;
        }
        if ('jpg' == $type) {
            $options['progressive'] = (bool) $interlace;
        }
        if (in_array($type, ['png', 'gif'], true)) {
            $options['interlaced'] = (bool) $interlace;
        }

        //编码并写入文件（GIF 动画会保留所有帧）
        $encoded = $this->im->encodeUsingFileExtension($type, ...$options);
        $encoded->save($pathname);

        return $this;
    }

    /**
     * http 输出图片
     *
     * @return void
     */
    public function output()
    {
        $type = $this->info['type'];
        $type = 'jpeg' == $type ? 'jpg' : $type;
        header("content-type: image/{$type}");
        echo $this->im->encodeUsingFileExtension($type)->toString();
        exit;
    }

    /**
     * 返回图像宽度
     *
     * @return int 图像宽度
     */
    public function width()
    {
        return $this->im->width();
    }

    /**
     * 返回图像高度
     *
     * @return int 图像高度
     */
    public function height()
    {
        return $this->im->height();
    }

    /**
     * 返回图像类型
     *
     * @return string 图像类型
     */
    public function type()
    {
        return $this->info['type'];
    }

    /**
     * 返回图像 MIME 类型
     *
     * @return string 图像 MIME 类型
     */
    public function mime()
    {
        return $this->info['mime'];
    }

    /**
     * 返回图像尺寸数组 0 - 图像宽度，1 - 图像高度
     *
     * @return array 图像尺寸
     */
    public function size()
    {
        return [$this->im->width(), $this->im->height()];
    }

    /**
     * 旋转图像
     *
     * @param int $degrees 顺时针旋转的度数
     * @return $this
     */
    public function rotate($degrees = 90)
    {
        //Intervention 对动画 GIF 会自动逐帧处理
        $this->im->rotate((float) $degrees);

        return $this;
    }

    /**
     * 翻转图像
     *
     * @param integer $direction 翻转轴，X 轴（上下）或 Y 轴（左右）
     * @return $this
     * @throws ImageException
     */
    public function flip($direction = self::FLIP_X)
    {
        switch ($direction) {
            case self::FLIP_X:
                //X 轴翻转即上下翻转
                $this->im->flip(Direction::VERTICAL);
                break;
            case self::FLIP_Y:
                //Y 轴翻转即左右翻转
                $this->im->flip(Direction::HORIZONTAL);
                break;
            default:
                throw new ImageException('不支持的翻转类型');
        }

        return $this;
    }

    /**
     * 裁剪图像
     *
     * @param  integer $w      裁剪区域宽度
     * @param  integer $h      裁剪区域高度
     * @param  integer $x      裁剪区域 x 坐标
     * @param  integer $y      裁剪区域 y 坐标
     * @param  integer $width  图像保存宽度
     * @param  integer $height 图像保存高度
     *
     * @return $this
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        //设置保存尺寸
        empty($width) && $width   = $w;
        empty($height) && $height = $h;
        $width  = (int) $width;
        $height = (int) $height;

        //先从原图裁出 w×h 的区域（越界部分用白色填充，与原实现一致）
        $this->im = $this->im->crop((int) $w, (int) $h, (int) $x, (int) $y, '#ffffff');

        //裁剪区域与保存尺寸不一致时再缩放
        if ($width != (int) $w || $height != (int) $h) {
            $this->im = $this->im->resize($width, $height);
        }

        return $this;
    }

    /**
     * 生成缩略图
     *
     * @param  integer $width  缩略图最大宽度
     * @param  integer $height 缩略图最大高度
     * @param int      $type   缩略图裁剪类型
     *
     * @return $this
     * @throws ImageException
     */
    public function thumb($width, $height, $type = self::THUMB_SCALING)
    {
        $width  = (int) $width;
        $height = (int) $height;
        //原图宽度和高度
        $w = $this->im->width();
        $h = $this->im->height();

        switch ($type) {
            /* 等比例缩放 */
            case self::THUMB_SCALING:
                //原图尺寸小于缩略图尺寸则不进行缩略
                if ($w < $width && $h < $height) {
                    return $this;
                }
                //按原版算法自行计算尺寸：取最小比例后向下取整，
                //不使用 Intervention 的 scale()，否则其四舍五入会造成 1px 偏差
                $scale = min($width / $w, $height / $h);
                $newW  = (int) ($w * $scale);
                $newH  = (int) ($h * $scale);
                $this->im = $this->im->resize(max(1, $newW), max(1, $newH));
                break;
            /* 居中裁剪 */
            case self::THUMB_CENTER:
                $this->im = $this->im->cover($width, $height, Alignment::CENTER);
                break;
            /* 左上角裁剪 */
            case self::THUMB_NORTHWEST:
                $this->im = $this->im->cover($width, $height, Alignment::TOP_LEFT);
                break;
            /* 右下角裁剪 */
            case self::THUMB_SOUTHEAST:
                $this->im = $this->im->cover($width, $height, Alignment::BOTTOM_RIGHT);
                break;
            /* 填充 */
            case self::THUMB_FILLED:
                //按比例缩放后居中放在白底画布上，小图不放大
                $this->im = $this->im->containDown($width, $height, '#ffffff', Alignment::CENTER);
                break;
            /* 固定 */
            case self::THUMB_FIXED:
                $this->im = $this->im->resize($width, $height);
                break;
            default:
                throw new ImageException('不支持的缩略图裁剪类型');
        }

        return $this;
    }

    /**
     * 添加水印
     *
     * @param string    $source 水印图片路径
     * @param int|array $locate 水印位置
     * @param int       $alpha  透明度（0-100，100 为完全不透明）
     * @return $this
     * @throws ImageException
     */
    public function water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)
    {
        if (!is_file($source)) {
            throw new ImageException('水印图像不存在');
        }
        //获取水印图像信息
        $info = getimagesize($source);
        if (false === $info) {
            throw new ImageException('非法水印文件');
        }
        //部分合法 GIF 不返回 bits，用文件头兜底判断
        if (IMAGETYPE_GIF === $info[2] && empty($info['bits'])) {
            $head = @file_get_contents($source, false, null, 0, 6);
            if ('GIF89a' !== $head && 'GIF87a' !== $head) {
                throw new ImageException('非法水印文件');
            }
        }
        //解码水印图片
        try {
            $water = self::manager()->decodePath($source);
        } catch (\Throwable $e) {
            throw new ImageException('非法水印文件');
        }
        $ww = $water->width();
        $wh = $water->height();

        //当前图片宽高
        $w = $this->im->width();
        $h = $this->im->height();

        /* 设定水印位置 */
        switch ($locate) {
            /* 右下角水印 */
            case self::WATER_SOUTHEAST:
                $x = $w - $ww;
                $y = $h - $wh;
                break;
            /* 左下角水印 */
            case self::WATER_SOUTHWEST:
                $x = 0;
                $y = $h - $wh;
                break;
            /* 左上角水印 */
            case self::WATER_NORTHWEST:
                $x = $y = 0;
                break;
            /* 右上角水印 */
            case self::WATER_NORTHEAST:
                $x = $w - $ww;
                $y = 0;
                break;
            /* 居中水印 */
            case self::WATER_CENTER:
                $x = (int) (($w - $ww) / 2);
                $y = (int) (($h - $wh) / 2);
                break;
            /* 下居中水印 */
            case self::WATER_SOUTH:
                $x = (int) (($w - $ww) / 2);
                $y = $h - $wh;
                break;
            /* 右居中水印 */
            case self::WATER_EAST:
                $x = $w - $ww;
                $y = (int) (($h - $wh) / 2);
                break;
            /* 上居中水印 */
            case self::WATER_NORTH:
                $x = (int) (($w - $ww) / 2);
                $y = 0;
                break;
            /* 左居中水印 */
            case self::WATER_WEST:
                $x = 0;
                $y = (int) (($h - $wh) / 2);
                break;
            default:
                /* 自定义水印坐标 */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new ImageException('不支持的水印位置类型');
                }
        }

        //透明度换算：原参数 100 为不透明，Intervention 用 1 表示不透明
        $transparency = max(0, min(1, ((int) $alpha) / 100));

        //叠加水印（动画 GIF 会逐帧叠加）
        $this->im->insert($water, (int) $x, (int) $y, Alignment::TOP_LEFT, $transparency);

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
     * @throws ImageException
     */
    public function text($text, $font, $size, $color = '#00000000',
        $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0) {

        if (!is_file($font)) {
            throw new ImageException("不存在的字体文件：{$font}");
        }
        //GD 在部分环境（Windows 中文目录）无法直接打开字体文件，统一解析为可用路径
        $font = $this->resolveFontPath($font);

        //获取文字外框信息
        $info = @imagettfbbox((int) $size, (int) $angle, $font, $text);
        if (false === $info) {
            throw new ImageException("无法读取字体文件：{$font}");
        }
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);

        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;

        //当前图片宽高
        $imgW = $this->im->width();
        $imgH = $this->im->height();

        /* 设定文字位置 */
        switch ($locate) {
            /* 右下角文字 */
            case self::WATER_SOUTHEAST:
                $x += $imgW - $w;
                $y += $imgH - $h;
                break;
            /* 左下角文字 */
            case self::WATER_SOUTHWEST:
                $y += $imgH - $h;
                break;
            /* 左上角文字 */
            case self::WATER_NORTHWEST:
                //起始坐标即为左上角坐标，无需调整
                break;
            /* 右上角文字 */
            case self::WATER_NORTHEAST:
                $x += $imgW - $w;
                break;
            /* 居中文字 */
            case self::WATER_CENTER:
                $x += (int) (($imgW - $w) / 2);
                $y += (int) (($imgH - $h) / 2);
                break;
            /* 下居中文字 */
            case self::WATER_SOUTH:
                $x += (int) (($imgW - $w) / 2);
                $y += $imgH - $h;
                break;
            /* 右居中文字 */
            case self::WATER_EAST:
                $x += $imgW - $w;
                $y += (int) (($imgH - $h) / 2);
                break;
            /* 上居中文字 */
            case self::WATER_NORTH:
                $x += (int) (($imgW - $w) / 2);
                break;
            /* 左居中文字 */
            case self::WATER_WEST:
                $y += (int) (($imgH - $h) / 2);
                break;
            default:
                /* 自定义文字坐标 */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += (int) $posx;
                    $y += (int) $posy;
                } else {
                    throw new ImageException('不支持的文字位置类型');
                }
        }

        /* 设置偏移量 */
        if (is_array($offset)) {
            $offset        = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox     = $oy     = $offset;
        }

        /* 图片黑白检测：根据文字区域的亮度自动选择黑字或白字 */
        if ("auto" == $color) {
            $pickX = max(1, intval(mb_strwidth($text) * ($size / 1.6)));
            $pickY = max(1, (int) $size);

            $brightness = 0;
            $pixels     = 0;
            for ($i = (int) ($x + $ox); $i < $pickX + $x + $ox; $i++) {
                for ($j = (int) ($y + $oy - $pickY); $j < $y + $oy; $j++) {
                    //只统计图像范围内的像素，避免越界读取
                    if ($i < 0 || $j < 0 || $i >= $imgW || $j >= $imgH) {
                        continue;
                    }
                    $brightness += $this->getBrightnessOfPixel($i, $j);
                    $pixels++;
                }
            }

            $color = $pixels > 0 && $brightness / $pixels > 127 ? '#00000000' : '#ffffffff';
        }

        /* 设置颜色 */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ImageException('错误的颜色值');
        }

        //转成 Intervention 可识别的颜色对象
        $colorObject = $this->colorObject($color);

        //写入文字（动画 GIF 会逐帧写入）
        $this->im->text($text, (int) ($x + $ox), (int) ($y + $oy), function (FontFactory $fontFactory) use ($font, $size, $colorObject, $angle) {
            $fontFactory->file($font)
                ->size((int) $size)
                ->color($colorObject)
                ->angle((int) $angle)
                ->align('left', 'top');
        });

        return $this;
    }

    /**
     * 把 [r, g, b, alpha] 颜色数组转成 Intervention 颜色对象
     *
     * @param array $color GD 颜色数组，alpha 取值 0（不透明）~ 127（全透明）
     * @return \Intervention\Image\Colors\Rgb\Color
     */
    private function colorObject(array $color)
    {
        //GD 的 alpha 数值越大越透明，rgba 的不透明度正好相反
        $opacity = 1 - ((int) $color[3] / 127);

        return new Color((int) $color[0], (int) $color[1], (int) $color[2], (float) $opacity);
    }

    /**
     * 解析字体路径，保证 GD 能正常打开
     *
     * Windows 版 GD 无法打开含中文字符的路径，遇到这种情况时把字体复制到
     * 系统临时目录（纯英文路径）再使用，避免 imagettfbbox / imagettftext 报错。
     *
     * @param string $font 字体文件路径
     * @return string 可用的字体文件路径
     * @throws ImageException
     */
    private function resolveFontPath($font)
    {
        //路径全部是可打印 ASCII 字符时直接使用
        if (!preg_match('/[^\x20-\x7E]/', $font)) {
            return $font;
        }
        //使用文件特征生成固定的临时文件名，避免重复复制
        $ext = pathinfo($font, PATHINFO_EXTENSION);
        $ext = $ext ? $ext : 'ttf';
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'think-image-font-' .
            md5($font . '|' . @filesize($font) . '|' . @filemtime($font)) . '.' . $ext;
        if (!is_file($tmp)) {
            if (!@copy($font, $tmp)) {
                throw new ImageException("字体文件复制失败：{$font}");
            }
        }

        return $tmp;
    }

    /**
     * 获取图片指定像素点的亮度值
     *
     * @param int $x 横坐标
     * @param int $y 纵坐标
     * @return int 亮度（0-255）
     */
    private function getBrightnessOfPixel($x, $y)
    {
        try {
            $hex = $this->im->colorAt((int) $x, (int) $y)->toHex();
        } catch (\Throwable $e) {
            return 0;
        }
        //取前 6 位作为 RGB
        $hex = str_pad(substr($hex, 0, 6), 6, '0');
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));

        //红绿蓝能量不同，亮度不同，对应系数也不同（参考 https://www.w3.org/TR/AERT/#color-contrast）
        return intval($r * 0.299 + $g * 0.587 + $b * 0.114);
    }

    /**
     * 析构方法
     *
     * Intervention 的图片对象由 PHP 垃圾回收管理，这里无需手动释放资源。
     */
    public function __destruct()
    {
        //无需释放 GD 资源
    }
}
