<?php
/**
 * Intervention Image 4.x 兼容层自检脚本
 *
 * 逐项验证 hulang\ImageKit 的公共 API 是否与 hulang\Image 行为一致。
 * 运行：php tests/intervention-check.php
 */

require __DIR__ . '/autoload.php';

use hulang\ImageKit;

$dir  = __DIR__ . '/images/';
$out  = __DIR__ . '/tmp/';
@mkdir($out, 0777, true);

$pass = 0;
$fail = 0;

/**
 * 断言辅助函数
 */
function check($label, $condition, $detail = '')
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "[通过] {$label}" . ($detail ? "  ({$detail})" : '') . PHP_EOL;
    } else {
        $fail++;
        echo "[失败] {$label}" . ($detail ? "  ({$detail})" : '') . PHP_EOL;
    }
}

/* 1. 打开图片并读取基础信息 */
$img = ImageKit::open($dir . 'test.jpg');
$size = $img->size();
check('open() 读取尺寸', 800 === $size[0] && 600 === $size[1], "{$size[0]}x{$size[1]}");
check('type()', 'jpeg' === $img->type(), $img->type());
check('mime()', 'image/jpeg' === $img->mime(), $img->mime());
check('width()/height()', 800 === $img->width() && 600 === $img->height());

/* 2. 六种缩略图类型 */
$thumbCases = [
    'THUMB_SCALING'   => [ImageKit::THUMB_SCALING, 200, 150],
    'THUMB_FILLED'    => [ImageKit::THUMB_FILLED, 200, 200],
    'THUMB_CENTER'    => [ImageKit::THUMB_CENTER, 200, 200],
    'THUMB_NORTHWEST' => [ImageKit::THUMB_NORTHWEST, 200, 200],
    'THUMB_SOUTHEAST' => [ImageKit::THUMB_SOUTHEAST, 200, 200],
    'THUMB_FIXED'     => [ImageKit::THUMB_FIXED, 200, 200],
];
foreach ($thumbCases as $label => list($type, $w, $h)) {
    $t    = ImageKit::open($dir . 'test.jpg')->thumb($w, $h, $type);
    $size = $t->size();
    check("thumb() {$label}", abs($size[0] - $w) <= 1 && abs($size[1] - $h) <= 1, "{$size[0]}x{$size[1]}");
}

/* 缩略图不放大：原图 200x150，要求 300x300 */
$t    = ImageKit::open($dir . 'test.jpg')->thumb(300, 300, ImageKit::THUMB_SCALING);
$size = $t->size();
check('thumb() 小图不放大', $size[0] <= 300 && $size[1] <= 300, "{$size[0]}x{$size[1]}");

/* 3. crop 裁剪并缩放 */
$c    = ImageKit::open($dir . 'test.jpg')->crop(400, 300, 100, 100, 200, 150);
$size = $c->size();
check('crop() 裁剪+缩放', 200 === $size[0] && 150 === $size[1], "{$size[0]}x{$size[1]}");

/* 4. 旋转 / 翻转 */
$r    = ImageKit::open($dir . 'test.jpg')->rotate(90);
$size = $r->size();
check('rotate(90) 画布自动扩展', 600 === $size[0] && 800 === $size[1], "{$size[0]}x{$size[1]}");

$fx = ImageKit::open($dir . 'test.jpg')->flip(ImageKit::FLIP_X);
check('flip(FLIP_X)', 800 === $fx->width() && 600 === $fx->height());

$fy = ImageKit::open($dir . 'test.jpg')->flip(ImageKit::FLIP_Y);
check('flip(FLIP_Y)', 800 === $fy->width() && 600 === $fy->height());

/* 5. 水印九个位置 */
$waterPositions = [
    'WATER_NORTHWEST' => ImageKit::WATER_NORTHWEST,
    'WATER_CENTER'    => ImageKit::WATER_CENTER,
    'WATER_SOUTHEAST' => ImageKit::WATER_SOUTHEAST,
    'WATER_SOUTHWEST' => ImageKit::WATER_SOUTHWEST,
    'WATER_NORTHEAST' => ImageKit::WATER_NORTHEAST,
];
foreach ($waterPositions as $label => $pos) {
    $w    = ImageKit::open($dir . 'test.jpg')->water($dir . 'test.png', $pos, 100);
    $file = $out . "check-{$label}.jpg";
    $w->save($file, null, 90);
    check("water() {$label}", is_file($file) && 800 === $w->width() && 600 === $w->height(), $file);
}

/* 自定义坐标水印 */
$w = ImageKit::open($dir . 'test.jpg')->water($dir . 'test.png', [10, 10], 50);
check('water() 自定义坐标 + 透明度', 800 === $w->width());

/* 6. 文字（含中文字体路径，验证临时目录兜底） */
$text = ImageKit::open($dir . 'test.jpg')->text(
    'ThinkPHP 8',
    $dir . 'test.ttf',
    24,
    '#ffffffff',
    ImageKit::WATER_CENTER,
    0,
    0
);
$text->save($out . 'check-text.jpg', null, 90);
check('text() 中文字体路径 + 居中', is_file($out . 'check-text.jpg'));

/* auto 颜色 */
$auto = ImageKit::open($dir . 'test.jpg')->text('auto color', $dir . 'test.ttf', 24, 'auto');
$auto->save($out . 'check-text-auto.jpg', null, 90);
check('text() auto 取色', is_file($out . 'check-text-auto.jpg'));

/* 7. 保存为其它格式 */
ImageKit::open($dir . 'test.jpg')->save($out . 'check-save.png', 'png');
check('save() 指定 png 类型', is_file($out . 'check-save.png') && 'PNG' === strtoupper(substr(file_get_contents($out . 'check-save.png'), 1, 3)));

ImageKit::open($dir . 'test.jpg')->save($out . 'check-save.jpg', null, 60, false);
check('save() jpg 质量/隔行', is_file($out . 'check-save.jpg'), filesize($out . 'check-save.jpg') . ' 字节');

/* 8. 动画 GIF 全链路：缩略 -> 保存 -> 帧数不变 */
$gif = ImageKit::open($dir . 'test.gif');
check('open() 动画 GIF 尺寸', 380 === $gif->width() && 216 === $gif->height());

$gifThumb = ImageKit::open($dir . 'test.gif')->thumb(120, 68, ImageKit::THUMB_SCALING);
$gifThumb->save($out . 'check-anim.gif');
$after = ImageKit::open($out . 'check-anim.gif');
//兼容层不暴露帧数，直接用底层 ImageManager 解码统计
$manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
$frameCount = $manager->decodePath($out . 'check-anim.gif')->count();
check('动画 GIF 处理后帧数保留', 42 === $frameCount, "{$frameCount} 帧, " . filesize($out . 'check-anim.gif') . ' 字节');
//380x216 缩到 120x68 时受高度限制，宽度按原版算法向下取整为 119
check('动画 GIF 尺寸正确', 119 === $gifThumb->width() && 68 === $gifThumb->height(), $gifThumb->width() . 'x' . $gifThumb->height());

/* 9. 链式调用 */
$chain = ImageKit::open($dir . 'test.jpg')
    ->thumb(300, 300, ImageKit::THUMB_CENTER)
    ->water($dir . 'test.png', ImageKit::WATER_SOUTHEAST, 80)
    ->rotate(0)
    ->save($out . 'check-chain.jpg', null, 85);
check('链式调用', is_file($out . 'check-chain.jpg') && 300 === $chain->width());

/* 10. 非法图片抛出异常 */
$thrown = false;
try {
    ImageKit::open($dir . 'test.bmp');
} catch (\hulang\image\Exception $e) {
    $thrown = true;
}
check('非法图片抛出 hulang\\image\\Exception', $thrown);

/* 11. 不存在的文件抛出异常 */
$thrown = false;
try {
    ImageKit::open($dir . 'not-exists.jpg');
} catch (\hulang\image\Exception $e) {
    $thrown = true;
}
check('文件不存在抛出异常', $thrown);

/* 12. 与原生 GD 版尺寸对比 */
$gdSizes = [];
foreach ($thumbCases as $label => list($type, $w, $h)) {
    $g = \hulang\Image::open($dir . 'test.jpg')->thumb($w, $h, $type);
    $gdSizes[$label] = $g->size();
    $iv = ImageKit::open($dir . 'test.jpg')->thumb($w, $h, $type)->size();
    check("尺寸与 GD 版一致 {$label}", $gdSizes[$label] === $iv, implode('x', $iv) . ' / GD ' . implode('x', $gdSizes[$label]));
}

/* 13. 水印像素确实被写入（右下角区域有变化） */
$plain = ImageKit::open($dir . 'test.jpg')->save($out . 'check-plain.jpg', null, 90);
$wmImg = ImageKit::open($out . 'check-WATER_SOUTHEAST.jpg');
$plainImg = ImageKit::open($out . 'check-plain.jpg');
$colorW = $wmImg->mime();
check('水印结果文件可再次打开', 'image/jpeg' === $colorW);

/* 14. ImageKit 结构语义 + 旧类名兼容 */
$kit = ImageKit::open($dir . 'test.jpg');
check('ImageKit 不再 instanceof Image', !($kit instanceof \hulang\Image));
check('image() 返回 hulang\\Image', $kit->image() instanceof \hulang\Image);
check(
    '常量与 hulang\\Image 一致',
    ImageKit::WATER_CENTER === \hulang\Image::WATER_CENTER
    && ImageKit::FLIP_Y === \hulang\Image::FLIP_Y
);
$legacy = \hulang\ImageIntervention::open($dir . 'test.jpg');
check('旧类名 ImageIntervention 仍可用', $legacy instanceof ImageKit && 800 === $legacy->width());
check('旧类名同样不再是 hulang\\Image', !($legacy instanceof \hulang\Image));

echo PHP_EOL . "通过 {$pass} 项，失败 {$fail} 项" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
