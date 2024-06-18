<?php

declare(strict_types=1);

namespace hulang\image\gif;

class Decoder
{
    public $GIF_buffer = [];
    public $GIF_arrays = [];
    public $GIF_delays = [];
    public $GIF_stream = '';
    public $GIF_string = '';
    public $GIF_bfseek = 0;
    public $GIF_screen = [];
    public $GIF_global = [];
    public $GIF_sorted;
    public $GIF_colorS;
    public $GIF_colorC;
    public $GIF_colorF;
    /**
     * GIF图像解析器的构造函数
     * 
     * 该构造函数用于初始化GIF图像解析器,它从给定的GIF数据流中读取必要的信息
     * 包括全局颜色表的配置和图像描述信息
     * 
     * @param resource $GIF_pointer 指向GIF数据流的指针
     */
    public function __construct($GIF_pointer)
    {
        // 初始化GIF数据流变量
        $this->GIF_stream = $GIF_pointer;
        // 读取GIF头部信息
        $this->getByte(6);
        $this->getByte(7);
        // 存储屏幕描述信息
        $this->GIF_screen = $this->GIF_buffer;
        // 解析屏幕描述中的颜色表相关配置
        $this->GIF_colorF = $this->GIF_buffer[4] & 0x80 ? 1 : 0;
        $this->GIF_sorted = $this->GIF_buffer[4] & 0x08 ? 1 : 0;
        $this->GIF_colorC = $this->GIF_buffer[4] & 0x07;
        // 计算全局颜色表的大小
        $this->GIF_colorS = 2 << $this->GIF_colorC;
        // 如果存在全局颜色表,则读取全局颜色表
        if (1 == $this->GIF_colorF) {
            $this->getByte(3 * $this->GIF_colorS);
            $this->GIF_global = $this->GIF_buffer;
        }
        // 遍历GIF数据,直到遇到结束符
        for ($cycle = 1; $cycle;) {
            // 读取下一个字节
            if ($this->getByte(1)) {
                switch ($this->GIF_buffer[0]) {
                        // 处理扩展信息
                    case 0x21:
                        $this->readExtensions();
                        break;
                        // 处理图像描述
                    case 0x2C:
                        $this->readDescriptor();
                        break;
                        // 遇到结束符,结束循环
                    case 0x3B:
                        $cycle = 0;
                        break;
                }
            } else {
                // 如果读取失败,结束循环
                $cycle = 0;
            }
        }
    }
    /**
     * 读取GIF图像扩展信息
     * 
     * 此方法用于解析GIF图像文件中的扩展信息,特别是与动画相关的延迟时间信息
     * 它通过读取GIF文件中的扩展块,查找并存储延迟时间数据,为后续的动画播放提供必要的参数
     * 
     * 在GIF文件格式中,扩展块用于提供除基本图像数据之外的附加信息,如透明色设置和帧延迟时间
     * 此方法专注于处理延迟时间信息,以便于动画的正确播放
     */
    public function readExtensions()
    {
        // 读取第一个字节,通常为图形控制扩展的标识
        $this->getByte(1);
        // 使用无限循环来处理可能存在的多个扩展块,直到遇到结束标记
        for (;;) {
            // 读取下一个字节,它指定了当前扩展块的长度
            $this->getByte(1);
            // 检查当前字节是否为结束标记(0x00),如果是,则退出循环
            if (($u = $this->GIF_buffer[0]) == 0x00) {
                break;
            }
            // 根据扩展块的长度读取相应的字节数据
            $this->getByte($u);
            // 如果当前扩展块类型为图形控制扩展(长度为4),则提取并存储延迟时间
            if (4 == $u) {
                // 延迟时间以毫秒为单位存储在两个字节中,这里进行合并并存储
                $this->GIF_delays[] = ($this->GIF_buffer[1] | $this->GIF_buffer[2] << 8);
            }
        }
    }
    /**
     * 读取描述符信息
     * 
     * 该方法用于解析GIF图像的描述符信息,包括屏幕大小、颜色表信息等
     * 它通过读取字节并更新内部状态来构建GIF字符串,这个字符串包含了GIF图像的关键描述信息
     * 
     * @see https://www.w3.org/Graphics/GIF/spec-gif89a.txt GIF规范文档
     */
    public function readDescriptor()
    {
        // 读取第9个字节
        $this->getByte(9);
        // 从缓冲区获取屏幕描述符信息
        $GIF_screen = $this->GIF_buffer;
        // 判断是否存在全局颜色表
        $GIF_colorF = $this->GIF_buffer[8] & 0x80 ? 1 : 0;
        if ($GIF_colorF) {
            // 如果存在全局颜色表,获取颜色表的大小和排序标志
            $GIF_code = $this->GIF_buffer[8] & 0x07;
            $GIF_sort = $this->GIF_buffer[8] & 0x20 ? 1 : 0;
        } else {
            // 如果不存在全局颜色表,使用已定义的颜色表信息
            $GIF_code = $this->GIF_colorC;
            $GIF_sort = $this->GIF_sorted;
        }
        // 计算颜色表的入口数量
        $GIF_size = 2 << $GIF_code;
        // 更新屏幕描述符的字段,设置全局颜色表标志和颜色表大小
        $this->GIF_screen[4] &= 0x70;
        $this->GIF_screen[4] |= 0x80;
        $this->GIF_screen[4] |= $GIF_code;
        if ($GIF_sort) {
            // 如果颜色表需要排序,设置排序标志
            $this->GIF_screen[4] |= 0x08;
        }
        // 初始化GIF字符串为'GIF87a'
        $this->GIF_string = 'GIF87a';
        // 将更新后的屏幕描述符写入GIF字符串
        $this->putByte($this->GIF_screen);
        if (1 == $GIF_colorF) {
            // 如果存在全局颜色表,读取颜色表并写入GIF字符串
            $this->getByte(3 * $GIF_size);
            $this->putByte($this->GIF_buffer);
        } else {
            // 如果不存在全局颜色表,写入已定义的全局颜色表信息
            $this->putByte($this->GIF_global);
        }
        // 添加图像分离符到GIF字符串
        $this->GIF_string .= chr(0x2C);
        // 更新屏幕描述符中的背景色索引位
        $GIF_screen[8] &= 0x40;
        // 将更新后的屏幕描述符写入GIF字符串
        $this->putByte($GIF_screen);
        // 读取图形控制扩展块的长度和类型
        $this->getByte(1);
        // 将读取的图形控制扩展块写入GIF字符串
        $this->putByte($this->GIF_buffer);
        // 循环读取和写入图像数据块,直到遇到图像结束标志
        for (;;) {
            $this->getByte(1);
            $this->putByte($this->GIF_buffer);
            if (($u = $this->GIF_buffer[0]) == 0x00) {
                break;
            }
            $this->getByte($u);
            $this->putByte($this->GIF_buffer);
        }
        // 添加GIF文件结束标志到字符串
        $this->GIF_string .= chr(0x3B);
        // 将构建的GIF字符串添加到数组中,用于后续处理或输出
        $this->GIF_arrays[] = $this->GIF_string;
    }
    /**
     * 从GIF流中读取指定长度的字节
     * 
     * 此方法用于从当前的GIF流中读取指定数量的字节,并将它们存储到缓冲区中
     * 如果无法读取到足够的字节,则方法返回0,否则返回1,表示读取成功
     * 
     * @param int $len 需要读取的字节长度
     * @return int 读取操作的状态,0表示失败,1表示成功
     */
    public function getByte($len)
    {
        // 初始化字节缓冲区
        $this->GIF_buffer = [];
        // 循环读取指定长度的字节
        for ($i = 0; $i < $len; $i++) {
            // 检查是否已经到达GIF流的末尾
            if ($this->GIF_bfseek > strlen((string) $this->GIF_stream)) {
                // 如果已经超出流的长度,则返回0,表示读取失败
                return 0;
            }
            // 从流中读取一个字节,将其转换为整数并添加到缓冲区中
            $this->GIF_buffer[] = ord($this->GIF_stream[$this->GIF_bfseek++]);
        }
        // 如果成功读取了指定长度的字节,则返回1,表示读取成功
        return 1;
    }
    /**
     * 将字节序列添加到GIF字符串中
     * 
     * 此方法用于逐个字节地将给定的字节序列拼接到GIF字符串中.它通过循环遍历每个字节
     * 并使用chr函数将整数值转换为对应的ASCII字符,然后将其追加到GIF_string属性中
     * 这种方法对于构建GIF图像的字节流非常有用,因为GIF图像的格式是基于ASCII字符的
     * 
     * @param array $bytes 字节序列,包含要添加到GIF字符串中的字节值
     */
    public function putByte($bytes)
    {
        // 遍历字节序列,将每个字节转换为字符并追加到GIF字符串中
        for ($i = 0; $i < count($bytes); $i++) {
            $this->GIF_string .= chr($bytes[$i]);
        }
    }
    /**
     * 获取GIF动画的所有帧
     * 
     * 该方法返回一个数组,数组中包含了GIF动画的所有帧.每个帧都是一个图像块
     * 用于构成整个GIF动画.通过获取这些帧,可以对GIF动画进行进一步的处理
     * 比如逐帧显示来实现动画效果,或者对单个帧进行编辑
     * 
     * @return array 返回一个包含GIF动画所有帧的数组
     */
    public function getFrames()
    {
        // 返回存储GIF动画帧的数组
        return ($this->GIF_arrays);
    }
    /**
     * 获取GIF图层的延迟时间数组
     * 
     * 本方法用于返回一个数组,其中包含了GIF图像各图层的延迟时间.这些延迟时间用于在动画GIF中控制每帧之间的时间间隔
     * 从而实现动画的播放效果.返回的数组中,每个元素代表了GIF中一个图层的延迟时间,单位通常是毫秒
     * 
     * @return array 返回一个包含GIF图层延迟时间的整数数组.每个元素代表了一个图层的延迟时间
     */
    public function getDelays()
    {
        // 返回GIF图层延迟时间的数组
        return ($this->GIF_delays);
    }
}
