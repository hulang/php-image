<?php
/**
 * ImageKit 静态快捷入口自检脚本
 *
 * 逐个验证 hulang\ImageKit 的静态方法：第一个参数源文件、第二个参数目标文件、
 * 后面是原 hulang\Image 的方法参数，全部按「打开 → 处理 → 保存」执行。
 * 运行：php tests/intervention-check.php
 */

require __DIR__ . '/autoload.php';

use hulang\ImageKit;

$dir = __DIR__ . '/images/';
$out = __DIR__ . '/tmp/';
@mkdir($out, 0777, true);

$src  = $dir . 'test.jpg';
$png  = $dir . 'test.png';
$gif  = $dir . 'test.gif';
$ttf  = $dir . 'test.ttf';
$bmp  = $dir . 'test.bmp';

$pass = 0;
$fail = 0;

/**
 * 断言辅助函数
 *
 * @param string $label     用例名称
 * @param bool   $condition 断言结果
 * @param string $detail    附加说明
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

/* 1. 只读信息：每个方法只传源文件 */
$size = ImageKit::size($src);
check('size() 读取尺寸', 800 === $size[0] && 600 === $size[1], "{$size[0]}x{$size[1]}");
check('type()', 'jpeg' === ImageKit::type($src), ImageKit::type($src));
check('mime()', 'image/jpeg' === ImageKit::mime($src), ImageKit::mime($src));
check('width()', 800 === ImageKit::width($src), (string) ImageKit::width($src));
check('height()', 600 === ImageKit::height($src), (string) ImageKit::height($src));

/* 2. 常量值必须与 hulang\Image 一一对应 */
$consts = [
    'THUMB_SCALING'   => 1,
    'THUMB_FILLED'    => 2,
    'THUMB_CENTER'    => 3,
    'THUMB_NORTHWEST' => 4,
    'THUMB_SOUTHEAST' => 5,
    'THUMB_FIXED'     => 6,
    'WATER_NORTHWEST' => 1,
    'WATER_NORTH'     => 2,
    'WATER_NORTHEAST' => 3,
    'WATER_WEST'      => 4,
    'WATER_CENTER'    => 5,
    'WATER_EAST'      => 6,
    'WATER_SOUTHWEST' => 7,
    'WATER_SOUTH'     => 8,
    'WATER_SOUTHEAST' => 9,
    'FLIP_X'          => 1,
    'FLIP_Y'          => 2,
];
$constOk = true;
foreach ($consts as $name => $expected) {
    if (ImageKit::{$name} !== $expected) {
        $constOk = false;
        echo "  常量不一致: {$name}" . PHP_EOL;
    }
}
check('17 个常量值正确', $constOk);

/* 3. 6 种缩略图类型：一步到位执行并校验输出文件 */
$thumbCases = [
    'THUMB_SCALING'   => [ImageKit::THUMB_SCALING, 200, 200, [200, 150]],
    'THUMB_FILLED'    => [ImageKit::THUMB_FILLED, 200, 200, [200, 200]],
    'THUMB_CENTER'    => [ImageKit::THUMB_CENTER, 200, 200, [200, 200]],
    'THUMB_NORTHWEST' => [ImageKit::THUMB_NORTHWEST, 200, 200, [200, 200]],
    'THUMB_SOUTHEAST' => [ImageKit::THUMB_SOUTHEAST, 200, 200, [200, 200]],
    'THUMB_FIXED'     => [ImageKit::THUMB_FIXED, 200, 200, [200, 200]],
];
foreach ($thumbCases as $label => list($type, $w, $h, $expect)) {
    $dst = $out . 'kit-' . $label . '.jpg';
    $ret = ImageKit::thumb($src, $dst, $w, $h, $type);
    $got = ImageKit::size($dst);
    check(
        "thumb() {$label}",
        $got[0] === $expect[0] && $got[1] === $expect[1] && $ret === $dst,
        "{$got[0]}x{$got[1]}"
    );
}

/* 4. 小图不放大（等比例缩放时） */
$dst = $out . 'kit-small.jpg';
ImageKit::thumb($png, $dst, 900, 900, ImageKit::THUMB_SCALING);
$got = ImageKit::size($dst);
check('thumb() 小图不放大', 800 === $got[0] && 600 === $got[1], "{$got[0]}x{$got[1]}");

/* 5. 裁剪：区域 + 二次缩放 */
$dst = $out . 'kit-crop.jpg';
ImageKit::crop($src, $dst, 400, 300, 100, 100, 200, 150);
$got = ImageKit::size($dst);
check('crop() 裁剪+缩放', 200 === $got[0] && 150 === $got[1], "{$got[0]}x{$got[1]}");

/* 6. 旋转 */
$dst = $out . 'kit-rotate.jpg';
ImageKit::rotate($src, $dst, 90);
$got = ImageKit::size($dst);
check('rotate(90) 画布自动扩展', 600 === $got[0] && 800 === $got[1], "{$got[0]}x{$got[1]}");

/* 7. 两种翻转 */
$dstX = $out . 'kit-flip-x.jpg';
$dstY = $out . 'kit-flip-y.jpg';
$a = ImageKit::flip($src, $dstX, ImageKit::FLIP_X);
$b = ImageKit::flip($src, $dstY, ImageKit::FLIP_Y);
check('flip(FLIP_X)', $a === $dstX && is_file($dstX));
check('flip(FLIP_Y)', $b === $dstY && is_file($dstY));

/* 8. 五个水印位置 */
$waterCases = [
    'WATER_NORTHWEST' => ImageKit::WATER_NORTHWEST,
    'WATER_CENTER'    => ImageKit::WATER_CENTER,
    'WATER_SOUTHEAST' => ImageKit::WATER_SOUTHEAST,
    'WATER_SOUTHWEST' => ImageKit::WATER_SOUTHWEST,
    'WATER_NORTHEAST' => ImageKit::WATER_NORTHEAST,
];
foreach ($waterCases as $label => $pos) {
    $dst = $out . 'kit-' . $label . '.jpg';
    ImageKit::water($src, $dst, $png, $pos, 100);
    $got = ImageKit::size($dst);
    check("water() {$label}", is_file($dst) && 800 === $got[0] && 600 === $got[1]);
}

/* 9. 自定义水印坐标 + 半透明 */
$dst = $out . 'kit-water-xy.jpg';
ImageKit::water($src, $dst, $png, [10, 10], 50);
check('water() 自定义坐标 + 透明度', is_file($dst) && ImageKit::size($dst) === [800, 600]);

/* 10. 文字水印 */
$dst = $out . 'kit-text.jpg';
ImageKit::text($src, $dst, 'ThinkPHP 8', $ttf, 24, '#ffffff', ImageKit::WATER_CENTER);
check('text() 中文字体路径 + 居中', is_file($dst) && 800 === ImageKit::width($dst));

/* 11. 文字自动取色 */
$dst = $out . 'kit-text-auto.jpg';
ImageKit::text($src, $dst, 'auto color', $ttf, 24, 'auto');
check('text() auto 取色', is_file($dst) && filesize($dst) > 0);

/* 12. save() 做格式转换：jpg 存成 png */
$dst = $out . 'kit-save.png';
$ret = ImageKit::save($src, $dst, 'png');
check('save() 指定 png 类型', $ret === $dst && 'png' === ImageKit::type($dst), ImageKit::type($dst));

/* 13. save() 目标扩展名自动推断 + 质量参数 */
$dst = $out . 'kit-quality.jpg';
ImageKit::save($src, $dst, null, 60, false);
check('save() 按目标扩展名推断 + 质量', is_file($dst) && filesize($dst) > 0, filesize($dst) . ' 字节');

/* 14. 动画 GIF：静态方法同样逐帧处理 */
$gifSize = ImageKit::size($gif);
check('open() 动画 GIF 尺寸', 380 === $gifSize[0] && 216 === $gifSize[1], "{$gifSize[0]}x{$gifSize[1]}");
$dst = $out . 'kit-anim.gif';
ImageKit::thumb($gif, $dst, 120, 68, ImageKit::THUMB_SCALING);
$got = ImageKit::size($dst);
check('动画 GIF 处理后尺寸正确', 119 === $got[0] && 68 === $got[1], "{$got[0]}x{$got[1]}");
check('动画 GIF 结果仍是 gif', 'gif' === ImageKit::type($dst), ImageKit::type($dst));

/* 15. 异常：非法图片 / 文件不存在 */
$thrown = false;
try {
    ImageKit::crop($bmp, $out . 'kit-bad.jpg', 100, 100);
} catch (\hulang\image\Exception $e) {
    $thrown = true;
}
check('非法图片抛出 hulang\\image\\Exception', $thrown);

$thrown = false;
try {
    ImageKit::thumb($dir . 'not-exists.jpg', $out . 'kit-none.jpg', 100, 100);
} catch (\hulang\image\Exception $e) {
    $thrown = true;
}
check('文件不存在抛出异常', $thrown);

/* 16. 与 hulang\Image 实例方式的结果对照 */
$gdSizes = [];
foreach ($thumbCases as $label => list($type, $w, $h, $expect)) {
    $g = \hulang\Image::open($src)->thumb($w, $h, $type);
    $gdSizes[$label] = $g->size();
    $kit = ImageKit::size($out . 'kit-' . $label . '.jpg');
    check("尺寸与实例方式一致 {$label}", $gdSizes[$label] === $kit, implode('x', $kit));
}

/* 17. 结构语义：纯静态、不继承 Image、返回值是路径 */
$rc = new \ReflectionClass(ImageKit::class);
check('ImageKit 不继承 hulang\\Image', !$rc->isSubclassOf(\hulang\Image::class));
$rm = $rc->getMethod('crop');
check('crop() 是静态方法', $rm->isStatic());
$ret = ImageKit::thumb($src, $out . 'kit-return.jpg', 200, 200);
check('返回值是保存路径', is_string($ret) && $ret === $out . 'kit-return.jpg');

/* 18. 历史类名 ImageIntervention 继承静态方法 */
$legacy = \hulang\ImageIntervention::thumb($src, $out . 'kit-legacy.jpg', 200, 200);
check('ImageIntervention 静态调用可用', is_file($legacy) && 200 === ImageKit::width($legacy));
check(
    'ImageIntervention 常量同样可用',
    \hulang\ImageIntervention::WATER_CENTER === ImageKit::WATER_CENTER
);

/* 19. 新增效果类方法：摆正 / 去边 / 锐化 / 模糊 / 灰度 */
$dst = $out . 'kit-orient.jpg';
ImageKit::orient($src, $dst);
$got = ImageKit::size($dst);
check('orient() 无 EXIF 时保持原尺寸', 800 === $got[0] && 600 === $got[1], "{$got[0]}x{$got[1]}");

//先造一张白底红块的图，用来验证 trim 能不能把白边裁掉
$trimSrc = $out . 'trim-src.png';
$canvas = imagecreatetruecolor(200, 200);
$whiteColor = imagecolorallocate($canvas, 255, 255, 255);
imagefill($canvas, 0, 0, $whiteColor);
$redColor = imagecolorallocate($canvas, 255, 0, 0);
imagefilledrectangle($canvas, 40, 40, 159, 159, $redColor);
imagepng($canvas, $trimSrc);
imagedestroy($canvas);

$dst = $out . 'kit-trim.png';
ImageKit::trim($trimSrc, $dst, 0);
$got = ImageKit::size($dst);
check('trim() 裁掉四周纯色边', 120 === $got[0] && 120 === $got[1], "{$got[0]}x{$got[1]}");

$dst = $out . 'kit-sharpen.jpg';
ImageKit::sharpen($src, $dst, 10);
check('sharpen() 生成文件且尺寸不变', is_file($dst) && [800, 600] === ImageKit::size($dst));

$dst = $out . 'kit-blur.jpg';
ImageKit::blur($src, $dst, 5);
check('blur() 生成文件且尺寸不变', is_file($dst) && [800, 600] === ImageKit::size($dst));

//灰度：先确认源图该点是彩色，再看输出是否 R=G=B
$srcPixel = imagecreatefromjpeg($src);
$srcRgb = imagecolorat($srcPixel, 400, 300);
$srcR = ($srcRgb >> 16) & 0xFF;
$srcG = ($srcRgb >> 8) & 0xFF;
$srcB = $srcRgb & 0xFF;
imagedestroy($srcPixel);
check('灰度测试前提：源图该点为彩色', $srcR !== $srcG || $srcG !== $srcB, "rgb({$srcR},{$srcG},{$srcB})");

$dst = $out . 'kit-gray.png';
ImageKit::grayscale($src, $dst);
$grayPixel = imagecreatefrompng($dst);
$grayRgb = imagecolorat($grayPixel, 400, 300);
$grayR = ($grayRgb >> 16) & 0xFF;
$grayG = ($grayRgb >> 8) & 0xFF;
$grayB = $grayRgb & 0xFF;
imagedestroy($grayPixel);
check('grayscale() 输出像素 R=G=B', $grayR === $grayG && $grayG === $grayB, "rgb({$grayR},{$grayG},{$grayB})");

/* 20. 调色与尺寸：亮度 / 对比度 / 像素化 / 画布 / 覆盖 / 容纳 */
$dst = $out . 'kit-bright.jpg';
ImageKit::brightness($src, $dst, 50);
check('brightness() 尺寸不变', [800, 600] === ImageKit::size($dst));

$dst = $out . 'kit-contrast.jpg';
ImageKit::contrast($src, $dst, 30);
check('contrast() 尺寸不变', [800, 600] === ImageKit::size($dst));

$dst = $out . 'kit-pixel.jpg';
ImageKit::pixelate($src, $dst, 15);
check('pixelate() 尺寸不变', [800, 600] === ImageKit::size($dst));

$dst = $out . 'kit-canvas.jpg';
ImageKit::resizeCanvas($src, $dst, 300, 300);
check('resizeCanvas() 输出 300x300 画布', [300, 300] === ImageKit::size($dst));

$dst = $out . 'kit-cover.jpg';
ImageKit::cover($src, $dst, 200, 200);
check('cover() 输出 200x200', [200, 200] === ImageKit::size($dst));

$dst = $out . 'kit-contain.jpg';
ImageKit::contain($src, $dst, 200, 200);
check('contain() 输出 200x200', [200, 200] === ImageKit::size($dst));

/* 21. 透明填充 / 贴图 / 编码 / 动图 / DPI / 主色 */
//现造一张透明 PNG：四周全透明，中间一个红方块
$alphaSrc = $out . 'check-alpha.png';
$alphaCanvas = imagecreatetruecolor(200, 200);
imagesavealpha($alphaCanvas, true);
imagealphablending($alphaCanvas, false);
$alphaTrans = imagecolorallocatealpha($alphaCanvas, 0, 0, 0, 127);
imagefill($alphaCanvas, 0, 0, $alphaTrans);
imagealphablending($alphaCanvas, true);
$alphaRed = imagecolorallocate($alphaCanvas, 255, 0, 0);
imagefilledrectangle($alphaCanvas, 80, 80, 120, 120, $alphaRed);
imagepng($alphaCanvas, $alphaSrc);
imagedestroy($alphaCanvas);

$dst = $out . 'kit-fill.jpg';
ImageKit::fillTransparentAreas($alphaSrc, $dst, '#00ff00');
$fillPixel = imagecreatefromjpeg($dst);
$fillRgb = imagecolorat($fillPixel, 5, 5);
$fillR = ($fillRgb >> 16) & 0xFF;
$fillG = ($fillRgb >> 8) & 0xFF;
$fillB = $fillRgb & 0xFF;
imagedestroy($fillPixel);
check('fillTransparentAreas() 透明区填成绿色', $fillR < 40 && $fillG > 215 && $fillB < 40, "rgb({$fillR},{$fillG},{$fillB})");

$dst = $out . 'kit-insert.jpg';
ImageKit::insert($src, $dst, $alphaSrc, 10, 10, 'top-left', 1);
check('insert() 贴图后尺寸不变', [800, 600] === ImageKit::size($dst));

$bin = ImageKit::encode($src, 'webp', 80);
check('encode() 返回 WebP 二进制', 0 === strpos($bin, 'RIFF') && strlen($bin) > 1000, strlen($bin) . ' 字节');

check('count() 读出 GIF 帧数', 42 === ImageKit::count($gif), (string) ImageKit::count($gif));
check('isAnimated() 识别动图', true === ImageKit::isAnimated($gif));
check('isAnimated() 静态图为 false', false === ImageKit::isAnimated($src));

$dst = $out . 'kit-loop.gif';
ImageKit::setLoops($gif, $dst, 3);
check('setLoops() 写入 3 遍后读回重复次数 2', 2 === ImageKit::loops($dst), (string) ImageKit::loops($dst));

$dst = $out . 'kit-static.gif';
ImageKit::removeAnimation($gif, $dst, 0);
check('removeAnimation() 只留一帧', 1 === ImageKit::count($dst), (string) ImageKit::count($dst));

$dst = $out . 'kit-slice.gif';
ImageKit::sliceAnimation($gif, $dst, 0, 10);
check('sliceAnimation() 截取 10 帧', 10 === ImageKit::count($dst), (string) ImageKit::count($dst));

$dst = $out . 'kit-dpi.jpg';
ImageKit::setResolution($src, $dst, 300);
$dpi = ImageKit::resolution($dst);
check('setResolution() 写入 300dpi', 300.0 === (float) $dpi[0] && 300.0 === (float) $dpi[1], "{$dpi[0]}x{$dpi[1]}");

$colors = ImageKit::colors($src, 5);
check('colors() 提取主色', 5 === count($colors) && '#' === substr($colors[0], 0, 1), implode(',', $colors));

/* 22. 位置常量：与原生字符串等价 */
$dstA = $out . 'kit-pos-const.jpg';
ImageKit::cover($src, $dstA, 200, 200, ImageKit::POS_SOUTHEAST);
$dstB = $out . 'kit-pos-str.jpg';
ImageKit::cover($src, $dstB, 200, 200, 'bottom-right');
check('cover() 位置常量与字符串结果一致', md5_file($dstA) === md5_file($dstB));

$dst = $out . 'kit-pos-canvas.jpg';
ImageKit::resizeCanvas($src, $dst, 300, 300, '#ffffff', ImageKit::POS_NORTHWEST);
check('resizeCanvas() 接受位置常量', [300, 300] === ImageKit::size($dst));

$dst = $out . 'kit-pos-insert.jpg';
ImageKit::insert($src, $dst, $alphaSrc, 10, 10, ImageKit::POS_SOUTHEAST, 1);
check('insert() 接受位置常量', [800, 600] === ImageKit::size($dst));

check(
    '9 个位置常量齐全且与九宫格对应',
    9 === count([
        ImageKit::POS_NORTHWEST, ImageKit::POS_NORTH, ImageKit::POS_NORTHEAST,
        ImageKit::POS_WEST, ImageKit::POS_CENTER, ImageKit::POS_EAST,
        ImageKit::POS_SOUTHWEST, ImageKit::POS_SOUTH, ImageKit::POS_SOUTHEAST,
    ]) && ImageKit::POS_NORTHWEST === ImageKit::WATER_NORTHWEST
);

echo PHP_EOL . "通过 {$pass} 项，失败 {$fail} 项" . PHP_EOL;



exit($fail > 0 ? 1 : 0);
