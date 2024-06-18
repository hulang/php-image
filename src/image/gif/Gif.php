<?php

declare(strict_types=1);

namespace hulang\image\gif;

class Gif
{
    /**
     * GIF帧列表
     *
     * @var mixed|array
     */
    private $frames = [];
    /**
     * 每帧等待时间列表
     *
     * @var mixed|array
     */
    private $delays = [];

    /**
     * Gif动画类的构造函数
     * 
     * 用于初始化GIF动画,支持从URL或文件直接加载GIF数据
     * 如果提供了源数据和模式,则会尝试解码GIF以获取帧和延迟信息
     * 
     * @param string $src GIF的源数据,可以是文件路径或URL
     * @param string $mod 源数据的类型,'url'表示URL,其他情况视为文件路径
     * @throws \Exception 如果解码GIF过程中出现错误,会抛出异常
     */
    public function __construct($src = null, $mod = 'url')
    {
        /* 当提供源数据时,尝试解码GIF */
        if (!is_null($src)) {
            /* 如果是文件路径且模式为URL,转换为文件内容 */
            if ('url' == $mod && is_file($src)) {
                $src = file_get_contents($src);
            }
            /* 尝试使用Decoder类解码GIF */
            /* 解码GIF图片 */
            try {
                $de = new Decoder($src);
                /* 获取解码后的帧和延迟信息 */
                $this->frames = $de->getFrames();
                $this->delays = $de->getDelays();
            } catch (\Exception $e) {
                /* 解码失败时,抛出异常 */
                throw new \Exception('解码GIF图片出错');
            }
        }
    }
    /**
     * 处理图像帧的数据
     * 
     * 此方法用于设置或获取当前图像帧的数据.如果未提供参数,则方法将返回当前帧的数据
     * 如果提供了参数,则方法将更新当前帧的数据
     * 
     * @param string|null $stream 二进制图像数据流.如果为null,则表示获取当前帧数据；否则,表示更新当前帧数据
     * @return mixed 如果未提供参数,则返回当前帧的数据；如果提供了参数,则返回更新后的当前帧引用
     */
    public function image($stream = null)
    {
        // 当未提供参数时,获取当前帧的数据
        if (is_null($stream)) {
            // 尝试获取当前帧,如果失败则重置到第一个帧
            $current = current($this->frames);
            return false === $current ? reset($this->frames) : $current;
        }
        // 当提供参数时,更新当前帧的数据
        $this->frames[key($this->frames)] = $stream;
    }
    /**
     * 移动到下一帧并返回当前帧的数据
     * 
     * 本函数用于在动画帧序列中向前移动到下一帧,并返回当前帧的数据
     * 它是实现动画播放循环的关键部分,通过不断地获取下一帧的数据
     * 实现动画的平滑过渡效果
     * 
     * @return mixed|string 返回当前帧的数据.如果已经到达最后一帧,则返回false
     */
    public function nextImage()
    {
        // 使用内置的next函数来移动到数组的下一元素,即下一帧,并返回当前元素的值
        return next($this->frames);
    }
    /**
     * 将动画帧编码并保存为GIF图像
     * 
     * 此方法通过创建一个Encoder对象来处理动画帧的编码工作,它使用了指定的参数来控制编码过程
     * 然后将编码后的GIF图像数据写入到指定的文件路径中
     * 
     * @param string $pathname 要保存的GIF图像的文件路径.这个路径应该包括文件名和扩展名
     */
    public function save($pathname)
    {
        // 创建一个Encoder对象,用于将帧编码为GIF动画
        $gif = new Encoder($this->frames, $this->delays, 0, 2, 0, 0, 0, 'bin');
        // 将编码后的GIF动画数据写入到文件中
        file_put_contents($pathname, $gif->getAnimation());
    }
}
