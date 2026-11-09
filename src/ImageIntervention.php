<?php
// +----------------------------------------------------------------------
// | ThinkPHP Image Package - 兼容入口
// +----------------------------------------------------------------------+
// | 说明：类名 ImageIntervention 只为了不打断旧代码继续保留，
// |      实际实现全部来自 hulang\ImageKit（内部再委托 hulang\Image）。
// |      新项目请直接使用 hulang\ImageKit 或 hulang\Image。
// +----------------------------------------------------------------------

namespace hulang;

/**
 * 图片处理类（历史类名，兼容保留）
 *
 * 用法与 hulang\ImageKit 完全一致：
 * ~~~
 * $image = \hulang\ImageIntervention::open('./a.jpg');
 * $image->thumb(200, 200)->save('./b.jpg');
 * ~~~
 * 本类没有任何自有逻辑，只是 ImageKit 的一层薄壳，
 * 帮助已经使用旧类名的项目平滑过渡。
 */
class ImageIntervention extends ImageKit
{
}
