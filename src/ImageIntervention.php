<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 历史类名兼容
// +----------------------------------------------------------------------
// | 说明：类名 ImageIntervention 为了不打断旧代码继续保留，
// |      方法实现全部继承自 hulang\ImageKit（静态一步到位版）。
// |      注意：从 2.0.2 起本类不再提供 open() 链式调用，
// |      如需对象用法请使用 hulang\Image。
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理类（历史类名，兼容保留）
 *
 * 用法与 hulang\ImageKit 完全一致（静态方法，源文件 + 目标文件 + 原参数）：
 * ~~~
 * $path = \hulang\ImageIntervention::thumb('./a.jpg', './b.jpg', 200, 200);
 * ~~~
 * 需要 open() 之后连续操作时，请直接用 \hulang\Image：
 * ~~~
 * \hulang\Image::open('./a.jpg')->thumb(200, 200)->save('./b.jpg');
 * ~~~
 */
class ImageIntervention extends ImageKit
{
}
