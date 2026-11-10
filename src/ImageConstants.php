<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 常量集中定义
// +----------------------------------------------------------------------
// | 说明：缩略图、水印、翻转三组常量统一放在这个 trait 里，
// |      hulang\Image 与 hulang\ImageKit 都 use 本 trait，
// |      两边只需维护一份值，对外仍然可以用 Image::XXX 或 ImageKit::XXX 访问。
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理常量（trait）
 *
 * 常量值只在这里维护一次，Image 和 ImageKit 通过 use 引入即可共用。
 */
trait ImageConstants
{
    /* 缩略图相关常量定义 */
    const THUMB_SCALING   = 1; //常量，标识缩略图等比例缩放类型
    const THUMB_FILLED    = 2; //常量，标识缩略图缩放后填充类型
    const THUMB_CENTER    = 3; //常量，标识缩略图居中裁剪类型
    const THUMB_NORTHWEST = 4; //常量，标识缩略图左上角裁剪类型
    const THUMB_SOUTHEAST = 5; //常量，标识缩略图右下角裁剪类型
    const THUMB_FIXED     = 6; //常量，标识缩略图固定尺寸缩放类型

    /* 水印相关常量定义 */
    const WATER_NORTHWEST = 1; //常量，标识左上角水印
    const WATER_NORTH     = 2; //常量，标识上居中水印
    const WATER_NORTHEAST = 3; //常量，标识右上角水印
    const WATER_WEST      = 4; //常量，标识左居中水印
    const WATER_CENTER    = 5; //常量，标识居中水印
    const WATER_EAST      = 6; //常量，标识右居中水印
    const WATER_SOUTHWEST = 7; //常量，标识左下角水印
    const WATER_SOUTH     = 8; //常量，标识下居中水印
    const WATER_SOUTHEAST = 9; //常量，标识右下角水印

    /* 翻转相关常量定义 */
    const FLIP_X = 1; //X轴翻转（上下翻转）
    const FLIP_Y = 2; //Y轴翻转（左右翻转）

    /* 位置相关常量定义（cover / contain / resizeCanvas / insert 的对齐位置） */
    const POS_NORTHWEST = 1; //左上
    const POS_NORTH     = 2; //上中
    const POS_NORTHEAST = 3; //右上
    const POS_WEST      = 4; //左中
    const POS_CENTER    = 5; //居中
    const POS_EAST      = 6; //右中
    const POS_SOUTHWEST = 7; //左下
    const POS_SOUTH     = 8; //下中
    const POS_SOUTHEAST = 9; //右下

    /**
     * 把位置常量或字符串翻译成 Intervention 的对齐字符串
     *
     * 传数字时按 POS_* 常量解析（1-9 九宫格，与 WATER_* 编号一致）；
     * 传字符串时原样透传，方便继续用 'center'、'top-left' 这类原生写法。
     *
     * @param integer|string $position 位置常量，或原生对齐字符串
     * @param string         $default  无法识别时使用的默认位置
     * @return string 对齐字符串，如 top-left、center、bottom-right
     */
    protected static function alignmentOf($position, $default = 'center')
    {
        //数字按九宫格常量翻译，字符串直接交给底层
        if (is_int($position) || (is_string($position) && ctype_digit($position))) {
            //1-9 与上表一一对应
            $map = [
                self::POS_NORTHWEST => 'top-left',
                self::POS_NORTH     => 'top',
                self::POS_NORTHEAST => 'top-right',
                self::POS_WEST      => 'left',
                self::POS_CENTER    => 'center',
                self::POS_EAST      => 'right',
                self::POS_SOUTHWEST => 'bottom-left',
                self::POS_SOUTH     => 'bottom',
                self::POS_SOUTHEAST => 'bottom-right',
            ];
            $key = (int) $position;

            //超出范围时退回默认位置，避免把空字符串传给底层
            return $map[$key] ?? $default;
        }

        //字符串原样透传
        return $position;
    }
}
