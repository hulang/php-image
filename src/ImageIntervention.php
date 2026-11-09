<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 兼容别名
// +----------------------------------------------------------------------
// | 底层实现已迁到 hulang\Image（Intervention Image 4.x），
// | 这里保留 ImageIntervention 作为别名，兼容此前使用该类名的代码。
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理类（hulang\Image 的兼容别名）
 *
 * 用法：
 * ~~~
 * $image = \hulang\ImageIntervention::open('./a.jpg');
 * ~~~
 * 与 \hulang\Image::open('./a.jpg') 完全等价。
 */
class ImageIntervention extends Image
{
}
