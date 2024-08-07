<?php

declare(strict_types=1);

namespace hulang;

use hulang\image\Exception as ImageException;
use hulang\image\gif\Gif;

class Image
{
    /* 翻转相关常量定义 */
    const FLIP_X = 1; //X轴翻转
    const FLIP_Y = 2; //Y轴翻转
    /**
     * 图像资源对象
     *
     * @return mixed|GdImage|false
     */
    protected $im;

    /** @var  mixed|Gif */
    protected $gif;

    /**
     * 图像信息:包括 width, height, type, mime, size
     *
     * @var mixed|array
     */
    protected $info;
    /**
     * 图像处理类的构造函数
     *
     * 本构造函数用于初始化图像处理类,它通过分析给定的图像文件,获取图像的基本信息,并根据图像类型创建相应的图像资源
     * 如果图像文件不合法或无法创建图像资源,则会抛出异常
     *
     * @param \SplFileInfo $file 图像文件信息对象
     * @throws ImageException 如果图像文件不合法或无法创建图像资源,则抛出异常
     */
    protected function __construct(\SplFileInfo $file)
    {
        // 获取图像文件的尺寸和类型信息
        $info = @getimagesize($file->getPathname());
        // 检查图像信息是否获取成功,以及对于GIF图像,检查是否是无损GIF
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImageException('Illegal image file');
        }
        // 初始化图像信息数组
        $this->info = [
            // 图像宽度
            'width' => $info[0],
            // 图像高度
            'height' => $info[1],
            // 图像类型
            'type' => image_type_to_extension($info[2], false),
            // 图像MIME类型
            'mime' => $info['mime'],
        ];
        // 根据图像类型创建图像资源
        if ('gif' == $this->info['type']) {
            // 如果是GIF图像,使用Gif类处理图像数据
            $this->gif = new Gif($file->getPathname());
            $this->im = @imagecreatefromstring($this->gif->image());
        } else {
            // 对于非GIF图像,使用相应的函数创建图像资源
            $fun = "imagecreatefrom{$this->info['type']}";
            $this->im = @$fun($file->getPathname());
        }
        // 检查图像资源是否成功创建,如果失败,则抛出异常
        if (empty($this->im)) {
            throw new ImageException('Failed to create image resources!');
        }
    }

    /**
     * 打开一个图像文件
     * 
     * 该方法接受一个文件路径(字符串)或一个SplFileInfo对象作为参数,用于创建一个表示图像的新对象
     * 如果参数是字符串,它将被转换为SplFileInfo对象,以利用其文件操作的方法
     * 如果指定的文件不存在,将抛出一个ImageException异常
     * 
     * @param \SplFileInfo|string $file 图像文件的SplFileInfo对象或路径字符串
     * @return Image 返回一个新创建的Image对象
     * @throws ImageException 如果文件不存在,则抛出异常
     */
    public static function open($file)
    {
        // 如果传入的是字符串,则将其转换为SplFileInfo对象
        if (is_string($file)) {
            $file = new \SplFileInfo($file);
        }
        // 检查文件是否存在,如果不存在则抛出异常
        if (!$file->isFile()) {
            throw new ImageException('image file not exist');
        }
        // 返回一个新的Image对象,用于处理图像
        return new self($file);
    }

    /**
     * 保存当前图像到指定路径
     * 
     * 该方法支持保存为JPEG、GIF、PNG格式的图像.根据传入的类型参数自动选择保存方式
     * 如果未指定类型,则根据图像的原始类型进行保存.可以设置保存的质量和是否使用隔行扫描
     * 
     * @param string $pathname 图像保存的路径和文件名
     * @param null|string $type 保存的图像类型,可以是jpeg、jpg、gif、png.如果不指定,则根据原始图像类型决定
     * @param int $quality 保存的质量,取值范围0-100,默认为80
     * @param bool $interlace 是否启用隔行扫描,用于JPEG图像,默认为true
     * @return $this 返回当前实例,支持链式调用
     */
    public function save($pathname, $type = null, $quality = 80, $interlace = true)
    {
        // 根据是否指定了类型来决定使用原始类型还是指定的类型,并将类型转换为小写
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        // 根据类型选择不同的保存方式
        if ('jpeg' == $type || 'jpg' == $type) {
            // 对JPEG图像启用或禁用隔行扫描
            imageinterlace($this->im, $interlace);
            // 保存JPEG图像,质量参数为$quality
            imagejpeg($this->im, $pathname, $quality);
        } elseif ('gif' == $type && !empty($this->gif)) {
            // 如果是GIF格式且已加载GIF数据,则使用GIF对象的保存方法
            $this->gif->save($pathname);
        } elseif ('png' == $type) {
            // 保存PNG图像时,启用完整的alpha通道保存
            imagesavealpha($this->im, true);
            // 调整PNG保存的质量,质量参数范围为0-9,根据$quality调整
            imagepng($this->im, $pathname, min((int) ($quality / 10), 9));
        } else {
            // 对于其他支持的图像类型,通过动态函数调用进行保存
            $fun = 'image' . $type;
            $fun($this->im, $pathname);
        }
        // 方法结束,返回当前实例以支持链式调用
        return $this;
    }

    /**
     * 获取图像的宽度
     * 
     * 该方法通过访问内部信息数组来获取图像的宽度
     * 它不接受任何参数,并返回图像的宽度值
     * 如果内部信息数组中没有宽度信息,则可能返回默认值或触发错误
     * 
     * @return mixed|int 返回图像的宽度.如果无法获取宽度,则可能返回默认值或触发错误
     */
    public function width()
    {
        return $this->info['width'];
    }

    /**
     * 获取图像的高度
     * 
     * 该方法通过访问内部信息数组来获取图像的高度.此方法适用于各种图像格式
     * 提供了一个统一的方式来获取图像的高度,而不需要关心具体的图像格式
     * 
     * @return mixed|int 返回图像的高度.如果无法获取高度,可能返回一个错误值或异常
     */
    public function height()
    {
        return $this->info['height'];
    }

    /**
     * 获取图像类型
     * 
     * 该方法用于返回图像的类型,基于之前获取的图像信息
     * 支持的图像类型可能包括JPEG、PNG、GIF等
     * 
     * @return string 图像类型,以字符串形式表示,例如"jpeg"、"png"等
     */
    public function type()
    {
        return $this->info['type'];
    }

    /**
     * 获取图像的MIME类型
     * 
     * 该方法用于返回图像文件的MIME类型.MIME类型是一种标准,用于标识文件类型和编码方式
     * 对于图像文件,MIME类型可以是如image/jpeg、image/png等,它们用于指示图像的格式
     * 
     * @return mixed|string 返回图像的MIME类型.如果无法确定MIME类型,可能返回mixed类型的值
     */
    public function mime()
    {
        return $this->info['mime'];
    }

    /**
     * 获取图像尺寸
     * 
     * 该方法返回一个包含图像宽度和高度的数组
     * 它提供了对图像尺寸的快速访问,而无需直接处理内部信息数组
     * 
     * @return array 返回一个包含图像宽度和高度的数组,索引0对应宽度,索引1对应高度
     */
    public function size()
    {
        // 返回图像的宽度和高度
        return [$this->info['width'], $this->info['height']];
    }

    /**
     * 旋转图像
     * 
     * 该方法用于旋转当前加载的图像.如果处理的是GIF动画,则会对每一帧进行旋转
     * 旋转是通过PHP的imagerotate函数实现的,并且会更新图像的宽度和高度信息
     * 
     * @param int $degrees 旋转的角度.默认为90度
     * @return $this 返回自身,允许链式调用
     */
    public function rotate($degrees = 90)
    {
        // 不断旋转图像直到处理完所有GIF动画的帧
        do {
            // 旋转图像,分配透明色以保持透明效果
            $img = imagerotate($this->im, -$degrees, imagecolorallocatealpha($this->im, 0, 0, 0, 127));
            // 销毁原图像资源,以释放内存
            imagedestroy($this->im);
            // 更新图像资源为旋转后的图像
            $this->im = $img;
            // 如果是GIF动画,检查是否还有下一帧
        } while (!empty($this->gif) && $this->gifNext());
        // 更新图像的宽度和高度信息
        $this->info['width']  = imagesx($this->im);
        $this->info['height'] = imagesy($this->im);
        // 返回自身,支持链式调用
        return $this;
    }

    /**
     * 翻转图像
     * 
     * 该方法用于翻转当前图像,支持水平(X轴)和垂直(Y轴)两种翻转方式
     * 通过指定不同的翻转方向,实现图像的镜像效果
     * 
     * @param int $direction 翻转方向,使用类常量定义,默认为水平翻转
     *                      可选值为FLIP_X(水平翻转)和FLIP_Y(垂直翻转)
     * @return $this 返回翻转后的图像对象,支持链式调用
     * @throws ImageException 如果指定的翻转方向不是支持的类型,则抛出异常
     */
    public function flip($direction = self::FLIP_X)
    {
        // 获取原图的宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];
        do {
            // 创建一个新的真彩色图像对象,用于存放翻转后的图像
            $img = imagecreatetruecolor($w, $h);
            // 根据指定的翻转方向,执行相应的图像复制操作来实现翻转效果
            switch ($direction) {
                case self::FLIP_X:
                    // 水平翻转：将原图的每一行从下到上复制到新图像中
                    for ($y = 0; $y < $h; $y++) {
                        imagecopy($img, $this->im, 0, $h - $y - 1, 0, $y, $w, 1);
                    }
                    break;
                case self::FLIP_Y:
                    // 垂直翻转：将原图的每一列从右到左复制到新图像中
                    for ($x = 0; $x < $w; $x++) {
                        imagecopy($img, $this->im, $w - $x - 1, 0, $x, 0, 1, $h);
                    }
                    break;
                default:
                    // 如果指定的翻转方向不是支持的类型,则抛出异常
                    throw new ImageException('不支持的翻转类型');
            }
            // 销毁原图像对象,用新图像对象替换
            imagedestroy($this->im);
            $this->im = $img;
            // 如果当前处理的是GIF动画,则继续处理下一帧
        } while (!empty($this->gif) && $this->gifNext());
        // 返回翻转后的图像对象,支持链式调用
        return $this;
    }

    /**
     * 裁剪图像
     * 
     * 本函数用于从当前图像中裁剪出指定区域,并可选地重新设置图像的尺寸
     * 裁剪是根据指定的宽度、高度、以及左上角的坐标来进行的
     * 如果没有指定新图像的宽度和高度,则它们将默认为裁剪区域的宽度和高度
     * 对于GIF动画,函数将处理每一帧的裁剪
     * 
     * @param int $w 裁剪区域的宽度
     * @param int $h 裁剪区域的高度
     * @param int $x 裁剪区域的左上角x坐标,默认为0(左边缘)
     * @param int $y 裁剪区域的左上角y坐标,默认为0(上边缘)
     * @param int $width 新图像的宽度,默认为0,即裁剪区域的宽度
     * @param int $height 新图像的高度,默认为0,即裁剪区域的高度
     * 
     * @return $this 返回处理后的图像对象,支持链式调用
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = 0, $height = 0)
    {
        // 如果没有指定新图像的宽度和高度,则使用裁剪区域的尺寸
        if (empty($width)) {
            $width = $w;
        }
        if (empty($height)) {
            $height = $h;
        }
        do {
            // 创建一个真彩色图像,用于存储裁剪后的结果
            $img = imagecreatetruecolor($width, $height);
            // 设置图像的默认背景颜色为白色
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            // 将原图像的指定区域裁剪并调整大小到新图像上
            imagecopyresampled($img, $this->im, 0, 0, $x, $y, $width, $height, $w, $h);
            // 销毁原图像资源,以释放内存
            imagedestroy($this->im);
            // 将裁剪并调整大小后的图像设置为新的原图像
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext()); // 如果是GIF动画,则处理下一帧
        // 更新图像信息,包括宽度和高度
        $this->info['width']  = (int) $width;
        $this->info['height'] = (int) $height;
        // 返回图像对象,支持链式调用
        return $this;
    }

    /**
     * 创建缩略图
     * 
     * 根据指定的宽度、高度和类型,生成目标图像的缩略图.支持不同的裁剪和缩放模式
     * 
     * @param int $width 目标宽度
     * @param int $height 目标高度
     * @param int $type 缩略图生成模式
     * @return object 返回处理后的图像对象
     * @throws ImageException 如果指定的裁剪类型不受支持
     */
    public function thumb($width, $height, $type = 1)
    {
        // 获取原始图像的宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];
        switch ($type) {
            case 1:
                // 标识缩略图等比例缩放类型
                // 如果原始图像尺寸小于目标尺寸,则直接返回原始图像
                if ($w < $width && $h < $height) {
                    return $this;
                }
                // 计算缩放比例
                $scale = min($width / $w, $height / $h);
                // 初始化裁剪的起始点和目标尺寸
                $x = $y = 0;
                $width  = intval($w * $scale);
                $height = intval($h * $scale);
                break;
            case 2:
                // 标识缩略图缩放后填充类型
                // 计算填充裁剪的缩放比例和目标尺寸,确保图像充满目标区域
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }
                $neww = intval($w * $scale);
                $newh = intval($h * $scale);
                // 计算裁剪区域的起始点和目标尺寸
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                $posx = intval(($width - $neww) / 2);
                $posy = intval(($height - $newh) / 2);
                // 重复处理GIF的每一帧
                do {
                    $img = imagecreatetruecolor($width, $height);
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);
                    imagecopyresampled($img, $this->im, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->im);
                    $this->im = $img;
                } while (!empty($this->gif) && $this->gifNext());
                // 更新图像信息
                $this->info['width']  = (int) $width;
                $this->info['height'] = (int) $height;
                return $this;
            case 3:
                // 标识缩略图居中裁剪类型
                // 计算居中裁剪的缩放比例
                $scale = max($width / $w, $height / $h);
                // 计算裁剪区域的宽度和高度以及起始点
                $w = intval($width / $scale);
                $h = intval($height / $scale);
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            case 4:
                // 标识缩略图左上角裁剪类型
                // 计算左上角裁剪的缩放比例和裁剪区域尺寸
                $scale = max($width / $w, $height / $h);
                $x = $y = 0;
                $w = intval($width / $scale);
                $h = intval($height / $scale);
                break;
            case 5:
                // 标识缩略图右下角裁剪类型
                // 计算右下角裁剪的缩放比例和裁剪区域尺寸
                $scale = max($width / $w, $height / $h);
                $w = intval($width / $scale);
                $h = intval($height / $scale);
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;
            case 6:
                // 标识缩略图固定尺寸缩放类型
                // 固定尺寸缩放,不做裁剪
                $x = 0;
                $y = 0;
                break;
            default:
                // 如果指定的裁剪类型不受支持,则抛出异常
                throw new ImageException('不支持的缩略图裁剪类型');
        }
        // 执行裁剪操作
        return $this->crop($w, $h, $x, $y, $width, $height);
    }

    /**
     * 在图片上添加水印
     * 
     * 本函数用于在当前处理的图片上添加水印图片.水印的位置可以通过预定义的常量或自定义数组进行指定
     * 支持的水印位置包括东南、西南、西北、东北、中心以及上下左右的具体坐标.水印的透明度可以通过$alpha参数进行调整
     * 
     * @param string $source 水印图片的路径
     * @param int $locate 水印的位置
     * @param int $alpha 水印的透明度,取值范围0-100
     * @return ImageHandler 返回处理后的图片对象
     * @throws ImageException 如果水印图片不存在或格式不支持,将抛出异常
     */
    public function water($source, $locate = 9, $alpha = 100)
    {
        // 检查水印图片是否存在
        if (!is_file($source)) {
            throw new ImageException('水印图像不存在');
        }
        // 获取水印图片的尺寸信息
        $info = getimagesize($source);
        // 检查水印图片是否合法
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImageException('非法水印文件');
        }
        // 根据水印图片的类型创建图像资源
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        // 启用水印图片的 alpha 混合
        imagealphablending($water, true);
        // 根据指定的位置计算水印的坐标
        switch ($locate) {
            case 1:
                // 左上角
                $x = 0;
                $y = 0;
                break;
            case 2:
                // 上居中
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;
            case 3:
                // 右上角
                $x = ($this->info['width'] - $info[0]);
                $y = 0;
                break;
            case 4:
                // 左居中
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            case 5:
                // 居中
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            case 6:
                // 右居中
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            case 7:
                // 左下角
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;
            case 8:
                // 下居中
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;
            case 9:
                // 右下角
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;
            default:
                // 支持使用数组自定义水印位置
                if (is_array($locate)) {
                    [$x, $y] = $locate;
                } else {
                    throw new ImageException('不支持的水印位置类型');
                }
        }
        // 循环处理GIF动画中的每一帧
        do {
            // 创建一个临时图像资源用于水印合并
            $src = imagecreatetruecolor($info[0], $info[1]);
            // 为临时图像填充白色背景
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            // 复制原图和水印到临时图像
            imagecopy($src, $this->im, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            // 将水印合并到原图,设置透明度
            imagecopymerge($this->im, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
            // 销毁临时图像资源
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());
        // 销毁水印图像资源
        imagedestroy($water);
        // 返回处理后的图片对象
        return $this;
    }

    /**
     * 在图片上添加文本水印
     * 
     * @param string $text 水印文本内容
     * @param string $font 水印文本所使用的字体文件路径
     * @param int $size 水印文本的字体大小
     * @param string|array $color 水印文本的颜色,可以是十六进制颜色字符串或数组
     * @param int $locate 水印的位置,使用类常量定义,也可以是自定义的数组
     * @param int|array $offset 水印的偏移量,可以是单个值或包含x、y的数组
     * @param int $angle 水印文本的旋转角度
     * @return self 返回自身,支持链式调用
     * @throws ImageException 如果字体文件不存在或颜色值不合法抛出异常
     */
    public function text($text, $font, $size, $color = '#00000000', $locate = 9, $offset = 0, $angle = 0)
    {
        // 检查字体文件是否存在,不存在则抛出异常
        if (!is_file($font)) {
            throw new ImageException("不存在的字体文件：{$font}");
        }
        // 获取文本框的四个角点坐标
        $info = imagettfbbox($size, $angle, $font, $text);
        // 计算文本的宽度和高度
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        // 根据水印位置常量,计算文本在图片上的确切位置
        switch ($locate) {
            case 1:
                // 左上角
                break;
            case 2:
                // 上居中
                $x += ($this->info['width'] - $w) / 2;
                break;
            case 3:
                // 右上角
                $x += ($this->info['width'] - $w) - 10;
                break;
            case 4:
                // 左居中
                $y += ($this->info['height'] - $h) / 2;
                break;
            case 5:
                // 居中
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;
            case 6:
                // 右居中
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;
            case 7:
                // 左下角
                $y += $this->info['height'] - $h;
                break;
            case 8:
                // 下居中
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;
            case 9:
                // 右下角
                $x += ($this->info['width'] - $w) - 10;
                $y += $this->info['height'] - $h;
                break;
            default:
                // 支持自定义位置数组
                if (is_array($locate)) {
                    [$posx, $posy] = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new ImageException('不支持的文字位置类型');
                }
        }
        // 处理偏移量,支持单个值和数组形式
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            [$ox, $oy] = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }
        // 处理颜色值,支持十六进制字符串和数组形式
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ImageException('错误的颜色值');
        }
        // 循环绘制文本,针对GIF动图的每一帧
        do {
            $col = imagecolorallocatealpha($this->im, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->im, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
        // 返回自身,支持链式调用
        return $this;
    }

    /**
     * 获取GIF动画的下一张图片
     * 
     * 该方法用于处理GIF动画,提取并生成下一张图片.如果存在下一张图片
     * 则销毁当前图片资源,创建并返回下一张图片资源.如果不存在下一张图片
     * 则重新加载第一张图片,准备重新播放动画
     * 
     * @return bool|resource 返回下一张图片资源,如果不存在下一张图片则返回false
     */
    protected function gifNext()
    {
        // 开启输出缓冲,以捕获生成的GIF图像数据
        ob_start();
        ob_implicit_flush(false);
        // 将当前图片资源转换为GIF格式并捕获输出
        imagegif($this->im);
        $img = ob_get_clean();
        // 将捕获的GIF图像数据传递给GIF处理类
        $this->gif->image($img);
        // 获取GIF的下一张图片数据
        $next = $this->gif->nextImage();
        if ($next) {
            // 如果存在下一张图片,销毁当前图片资源,创建新的图片资源
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($next);
            return $next;
        } else {
            // 如果没有下一张图片,销毁当前图片资源,重新加载第一张图片
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    /**
     * 类的析构函数
     * 
     * 该函数在对象销毁时自动调用.其目的是销毁类中保存的图像资源
     * 以释放系统资源.只有在图像资源存在且未被销毁时,才调用imagedestroy函数
     * 
     * @see imagedestroy() 用于销毁图像资源的PHP内置函数
     */
    public function __destruct()
    {
        // 检查图像资源是否存在,如果存在,则销毁它
        empty($this->im) || imagedestroy($this->im);
    }
}
