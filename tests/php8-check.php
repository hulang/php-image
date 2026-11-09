<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//优先使用 Composer 自动加载（底层依赖 Intervention Image）
$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
} else {
    //没有依赖时只能加载自身源码，Intervention 相关调用会失败
    require __DIR__ . '/../src/image/Exception.php';
    require __DIR__ . '/../src/Image.php';
}

use hulang\Image;

$src  = __DIR__ . '/images/test.jpg';
$png  = __DIR__ . '/images/test.png';
$gif  = __DIR__ . '/images/test.gif';
$ttf  = __DIR__ . '/images/test.ttf';
$out  = __DIR__ . '/tmp/';
if (!is_dir($out)) { mkdir($out, 0777, true); }

$tests = [];

$tests['open_jpg'] = function () use ($src) {
    $img = Image::open($src);
    return [$img->width(), $img->height(), $img->type(), $img->mime()];
};

$tests['thumb_scaling'] = function () use ($src, $out) {
    Image::open($src)->thumb(200, 200, Image::THUMB_SCALING)->save($out . 'thumb_scaling.jpg');
    return [filesize($out . 'thumb_scaling.jpg')];
};

$tests['thumb_center'] = function () use ($src, $out) {
    Image::open($src)->thumb(200, 200, Image::THUMB_CENTER)->save($out . 'thumb_center.jpg');
    return [filesize($out . 'thumb_center.jpg')];
};

$tests['thumb_filled'] = function () use ($src, $out) {
    Image::open($src)->thumb(300, 300, Image::THUMB_FILLED)->save($out . 'thumb_filled.jpg');
    return [filesize($out . 'thumb_filled.jpg')];
};

$tests['crop'] = function () use ($src, $out) {
    Image::open($src)->crop(100, 100, 10, 10)->save($out . 'crop.jpg');
    return [filesize($out . 'crop.jpg')];
};

$tests['water_png'] = function () use ($src, $png, $out) {
    Image::open($src)->water($png, Image::WATER_SOUTHEAST, 60)->save($out . 'water.jpg');
    return [filesize($out . 'water.jpg')];
};

$tests['text'] = function () use ($src, $ttf, $out) {
    Image::open($src)->text('ThinkPHP 8', $ttf, 24, '#ffffff', Image::WATER_CENTER)->save($out . 'text.jpg');
    return [filesize($out . 'text.jpg')];
};

$tests['text_auto_color'] = function () use ($src, $ttf, $out) {
    Image::open($src)->text('AUTO', $ttf, 30, 'auto', Image::WATER_NORTHWEST)->save($out . 'text_auto.jpg');
    return [filesize($out . 'text_auto.jpg')];
};

$tests['rotate'] = function () use ($src, $out) {
    Image::open($src)->rotate(90)->save($out . 'rotate.jpg');
    return [filesize($out . 'rotate.jpg')];
};

$tests['flip_x'] = function () use ($src, $out) {
    Image::open($src)->flip(Image::FLIP_X)->save($out . 'flip.jpg');
    return [filesize($out . 'flip.jpg')];
};

$tests['open_gif'] = function () use ($gif) {
    $img = Image::open($gif);
    return [$img->width(), $img->height(), $img->type()];
};

$tests['gif_thumb_save'] = function () use ($gif, $out) {
    Image::open($gif)->thumb(120, 120, Image::THUMB_SCALING)->save($out . 'anim.gif');
    return [filesize($out . 'anim.gif')];
};

$tests['png_output'] = function () use ($png, $out) {
    Image::open($png)->thumb(200, 200)->save($out . 'out.png');
    return [filesize($out . 'out.png')];
};

$fail = 0;
foreach ($tests as $name => $fn) {
    try {
        $r = $fn();
        echo "[OK]   $name => " . implode(', ', $r) . PHP_EOL;
    } catch (\Throwable $e) {
        $fail++;
        echo "[FAIL] $name => " . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    }
}
echo PHP_EOL . "失败数: $fail / " . count($tests) . PHP_EOL;