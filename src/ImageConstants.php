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
}
