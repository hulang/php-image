<?php

declare(strict_types=1);

namespace hulang\image\gif;

class Encoder
{
    public $GIF = 'GIF89a';
    public $VER = 'GIFEncoder V2.05';
    public $BUF = [];
    public $LOP = 0;
    public $DIS = 2;
    public $COL = -1;
    public $IMG = -1;
    public $ERR = [
        'ERR00' => 'Does not supported function for only one image!',
        'ERR01' => 'Source is not a GIF image!',
        'ERR02' => 'Unintelligible flag ',
        'ERR03' => 'Does not make animation from animated GIF source',
    ];
    /**
     * 构造函数,用于初始化GIF动画生成器
     * 
     * @param array $GIF_src GIF图像源数组,可以是文件路径或二进制数据
     * @param array $GIF_dly 每帧的延迟时间,以毫秒为单位
     * @param int $GIF_lop 循环次数,-1表示无限循环
     * @param int $GIF_dis 显示方式,0为顺序显示,1为同时显示
     * @param int $GIF_red 红色通道的色值
     * @param int $GIF_grn 绿色通道的色值
     * @param int $GIF_blu 蓝色通道的色值
     * @param string $GIF_mod 图像源类型,可以是'URL'或'BIN'
     */
    public function __construct($GIF_src, $GIF_dly, $GIF_lop, $GIF_dis, $GIF_red, $GIF_grn, $GIF_blu, $GIF_mod)
    {
        // 检查源数组是否有效
        if (!is_array($GIF_src)) {
            printf('%s: %s', $this->VER, $this->ERR['ERR00']);
            exit(0);
        }
        // 设置循环次数,默认为0表示无限循环
        $this->LOP = ($GIF_lop > -1) ? $GIF_lop : 0;
        // 设置显示方式,默认为2,表示顺序显示
        $this->DIS = ($GIF_dis > -1) ? (($GIF_dis < 3) ? $GIF_dis : 3) : 2;
        // 设置颜色值,如果RGB值都有效,则合并为一个整数
        $this->COL = ($GIF_red > -1 && $GIF_grn > -1 && $GIF_blu > -1) ? ($GIF_red | ($GIF_grn << 8) | ($GIF_blu << 16)) : -1;
        // 遍历源数组,加载GIF图像数据
        for ($i = 0; $i < count($GIF_src); $i++) {
            // 根据源类型,加载图像数据
            if (strtolower($GIF_mod) == 'url') {
                $this->BUF[] = fread(fopen($GIF_src[$i], 'rb'), filesize($GIF_src[$i]));
            } else if (strtolower($GIF_mod) == 'bin') {
                $this->BUF[] = $GIF_src[$i];
            } else {
                printf('%s: %s ( %s )!', $this->VER, $this->ERR['ERR02'], $GIF_mod);
                exit(0);
            }
            // 检查GIF图像的版本信息
            if (substr($this->BUF[$i], 0, 6) != 'GIF87a' && substr($this->BUF[$i], 0, 6) != 'GIF89a') {
                printf('%s: %d %s', $this->VER, $i, $this->ERR['ERR01']);
                exit(0);
            }
            // 检查GIF图像是否包含NETSCAPE标志,不支持此特性
            for ($j = (13 + 3 * (2 << (ord($this->BUF[$i][10]) & 0x07))), $k = true; $k; $j++) {
                switch ($this->BUF[$i][$j]) {
                    case '!':
                        if ((substr($this->BUF[$i], ($j + 3), 8)) == 'NETSCAPE') {
                            printf('%s: %s ( %s source )!', $this->VER, $this->ERR['ERR03'], ($i + 1));
                            exit(0);
                        }
                        break;
                    case ';':
                        $k = false;
                        break;
                }
            }
        }
        // 添加GIF头部信息
        $this->addHeader();
        // 添加每一帧的延迟时间
        for ($i = 0; $i < count($this->BUF); $i++) {
            isset($GIF_dly[$i]) && $this->addFrames($i, $GIF_dly[$i]);
        }
        // 添加GIF尾部信息
        $this->addFooter();
    }
    /**
     * 添加GIF头部信息
     * 
     * 此方法用于构造GIF图像的头部信息,特别是对于处理动画GIF而言,需要添加NETSCAPE2.0应用扩展块来定义循环次数
     * 如果检测到BUF中存在动画标志（第10个字节的最高位被设置）,则会构建并添加这个应用扩展块
     * 
     * @see https://www.w3.org/Graphics/GIF/spec-gif89a.txt GIF89a规范文档,详细说明了GIF文件格式和扩展块的用法
     */
    public function addHeader()
    {
        // 检查第10个字节的最高位,确定是否存在动画
        if (ord($this->BUF[0][10]) & 0x80) {
            // 计算颜色表大小,GIF规范中,颜色表大小由3的倍数决定,每个颜色项占用3字节
            $cmap = 3 * (2 << (ord($this->BUF[0][10]) & 0x07));
            // 从BUF中提取并拼接GIF头部信息,包括逻辑屏幕描述符和颜色表
            $this->GIF .= substr($this->BUF[0], 6, 7);
            $this->GIF .= substr($this->BUF[0], 13, $cmap);
            // 添加NETSCAPE2.0应用扩展块,定义动画的循环次数
            // 这里的循环次数通过$this->word($this->LOP)计算得到,并作为一个字节对存储
            $this->GIF .= '!\377\13NETSCAPE2.0\3\1' . $this->word($this->LOP) . '\0';
        }
    }
    /**
     * 添加帧到GIF动画中
     * 
     * 此函数负责解析输入的帧数据,并将其添加到正在构建的GIF动画中
     * 它处理了帧之间的差异,以及帧颜色表的优化,以减少最终GIF文件的大小
     * 
     * @param int $i 帧索引,用于访问帧数据数组
     * @param int $d 延迟时间,以百分之一秒为单位,表示当前帧应显示的时间
     */
    public function addFrames($i, $d)
    {
        // 初始化变量用于处理帧数据
        $Locals_img = '';
        // 计算本地颜色表大小
        $Locals_str = 13 + 3 * (2 << (ord($this->BUF[$i][10]) & 0x07));
        // 计算本地帧数据的结束位置
        $Locals_end = strlen($this->BUF[$i]) - $Locals_str - 1;
        // 提取本地帧数据
        $Locals_tmp = substr($this->BUF[$i], $Locals_str, $Locals_end);
        // 计算全局颜色表大小
        $Global_len = 2 << (ord($this->BUF[0][10]) & 0x07);
        // 计算本地颜色表大小
        $Locals_len = 2 << (ord($this->BUF[$i][10]) & 0x07);
        // 提取全局颜色表
        $Global_rgb = substr($this->BUF[0], 13, 3 * (2 << (ord($this->BUF[0][10]) & 0x07)));
        // 提取本地颜色表
        $Locals_rgb = substr($this->BUF[$i], 13, 3 * (2 << (ord($this->BUF[$i][10]) & 0x07)));
        // 构建本地扩展块,用于指定帧的显示方式和延迟时间
        $Locals_ext = '!\xF9\x04' . chr(($this->DIS << 2) + 0) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . '\x0\x0';
        // 如果设置了指定颜色,并且帧有本地颜色表,则尝试找到并替换颜色索引
        if ($this->COL > -1 && ord($this->BUF[$i][10]) & 0x80) {
            for ($j = 0; $j < (2 << (ord($this->BUF[$i][10]) & 0x07)); $j++) {
                if (ord($Locals_rgb[3 * $j + 0]) == (($this->COL >> 16) & 0xFF) && ord($Locals_rgb[3 * $j + 1]) == (($this->COL >> 8) & 0xFF) && ord($Locals_rgb[3 * $j + 2]) == (($this->COL >> 0) & 0xFF)) {
                    // 更新本地扩展块的颜色索引
                    $Locals_ext = '!\xF9\x04' . chr(($this->DIS << 2) + 1) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . chr($j) . '\x0';
                    break;
                }
            }
        }
        // 根据帧数据的起始标识符,提取图像数据和更新帧数据
        switch ($Locals_tmp[0]) {
            case '!':
                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);
                break;
            case ',':
                $Locals_img = substr($Locals_tmp, 0, 10);
                $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);
                break;
        }
        // 根据帧的特征,决定如何添加当前帧到GIF动画中
        if (ord($this->BUF[$i][10]) & 0x80 && $this->IMG > -1) {
            if ($Global_len == $Locals_len) {
                if ($this->blockCompare($Global_rgb, $Locals_rgb, $Global_len)) {
                    // 如果全局颜色表与本地颜色表相同,直接使用本地帧数据
                    $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
                } else {
                    // 否则,更新帧的数据以包含本地颜色表
                    $byte = ord($Locals_img[9]);
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord($this->BUF[0][10]) & 0x07);
                    $Locals_img[9] = chr($byte);
                    $this->GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
                }
            } else {
                // 如果全局颜色表大小与本地颜色表大小不同,更新帧的数据以包含本地颜色表
                $byte = ord($Locals_img[9]);
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord($this->BUF[$i][10]) & 0x07);
                $Locals_img[9] = chr($byte);
                $this->GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
            }
        } else {
            // 如果帧没有本地颜色表或不使用颜色表,直接添加帧数据
            $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
        }
        // 设置标志,表示已经添加过帧
        $this->IMG = 1;
    }
    /**
     * 添加GIF文件尾部标识
     * 
     * 该方法用于在构建GIF图像时,添加GIF文件格式的尾部标识
     * 这是GIF图像标准格式的一部分,确保图像数据的完整性和正确解析
     * 
     * @return void
     */
    public function addFooter()
    {
        $this->GIF .= ';';
    }
    /**
     * 比较两个块的数据是否相同
     * 
     * 该函数用于比较指定长度的两个数据块是否完全相同.每个数据块由一系列的三元组组成
     * 比较是基于每个三元组的每个元素逐个进行的.如果所有三元组在对应位置上的元素都相同
     * 则认为两个块相同,函数返回1；否则,认为两个块不同,函数返回0
     * 
     * @param array $GlobalBlock 全局数据块,包含一系列三元组
     * @param array $LocalBlock 局部数据块,包含一系列三元组
     * @param int $Len 指定的比较长度,即比较两个块中前n个三元组
     * @return int 如果两个块完全相同返回1,否则返回0
     */
    public function blockCompare($GlobalBlock, $LocalBlock, $Len)
    {
        // 遍历指定长度的三元组,从每个块的起始位置开始比较
        for ($i = 0; $i < $Len; $i++) {
            // 比较每个三元组的三个元素,如果有任一元素不同,则认为两个块不同
            if ($GlobalBlock[3 * $i + 0] != $LocalBlock[3 * $i + 0] || $GlobalBlock[3 * $i + 1] != $LocalBlock[3 * $i + 1] || $GlobalBlock[3 * $i + 2] != $LocalBlock[3 * $i + 2]) {
                // 返回0表示两个块不同
                return 0;
            }
        }
        // 所有三元组对应元素都相同,返回1表示两个块相同
        return 1;
    }
    /**
     * 将一个整数转换为一个由两个字节组成的字符串
     * 
     * 此函数的目的是将一个整数编码为一个由两个字节组成的字符串
     * 它通过位运算和chr函数将整数的低8位和高8位分别转换为字节
     * 然后将这两个字节连接成一个字符串返回
     * 这种编码方式常用于处理二进制数据或与硬件通信时的数据转换
     * 
     * @param int $int 需要转换的整数,必须是小于65536的非负整数
     * @return string 返回一个由两个字节组成的字符串,表示原始整数的编码形式
     */
    public function word($int)
    {
        // 先处理低8位,使用按位与运算和chr函数将整数的低8位转换为字节
        // 再处理高8位,同样使用按位与运算和chr函数将整数的高8位转换为字节
        // 最后使用字符串连接操作符将两个字节连接成一个字符串返回
        return (chr($int & 0xFF) . chr(($int >> 8) & 0xFF));
    }
    /**
     * 获取动画状态
     * 
     * 本方法用于返回当前对象的动画状态.如果对象是动画形式,则返回true;如果不是,则返回false
     * 这个方法的存在是为了方便外部代码检查当前对象是否处于动画状态,从而决定是否需要执行某些动画相关的操作
     * 
     * @return bool 返回当前对象的动画状态,true表示是动画,false表示不是动画
     */
    public function getAnimation()
    {
        // 返回对象的动画状态
        return ($this->GIF);
    }
}
