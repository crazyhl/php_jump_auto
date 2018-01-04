<?php
/**
 * Created by PhpStorm.
 * User: coolapk-hl
 * Date: 2018/1/2
 * Time: 下午4:57
 */
function jump()
{
    // 抽取截图
    exec("~/Downloads/platform-tools/adb shell screencap -p > screen.png");
    try {
        // 载入图片
        $image = new Imagick('./screen.png');
        // 获取图片大小
        $size = $image->getImageGeometry();
        // 计算图片旋转角度
        $rotate = 30;
        $it = $image->getPixelIterator();
        // 找一个安全的点，作为基准值
        $it->setIteratorRow(620);
        /** @var $pixels \ImagickPixel[] */
        $pixels = $it->getCurrentIteratorRow();
        $avgColor = $pixels[380]->getColor();
        $it->resetIterator();
        foreach ($it as $row => $pixels) { /* Loop through pixel rows */
            foreach ($pixels as $column => $pixel) { /* Loop through the pixels in the row (columns) */
                /** @var $pixel \ImagickPixel */
                $color = $pixel->getColor();
                // 找到棋子的点位决定旋转方向
                if ($color['r'] == 54 && $color['g'] == 60 && $color['b'] == 102) {
                    if ($column > ($size['width'] / 2)) {
                        $rotate = 150;
                        break(2);
                    } else {
                        $rotate = 30;
                        break(2);
                    }
                }
            }
        }

        // 索饭个图片并旋转
        $image->resizeImage($size['width'] / 2, $size['height'] / 2, Imagick::FILTER_LANCZOS, 1);
        $image->rotateImage(new ImagickPixel('#00000000'), $rotate);
        // 写出需要核查的图片
        $image->writeImage('./resize.png');

        // 载入缩放后的图片
        $resizeImage = new Imagick('./resize.png');
        // 获取图片大小
        $size = $resizeImage->getImageGeometry();

        // 准备遍历图片
        $it = $resizeImage->getPixelIterator();

        $firstPointX = 0;
        $firstPointY = 0;

        $checkColor = [
            'r' =>0x2a,
            'g' =>0x2c,
            'b' =>0x4c,
        ];
        if ($rotate == 150) {
            $checkColor = [
                'r' =>0x3a,
                'g' =>0x36,
                'b' =>0x5a,
            ];
        }

        $it->resetIterator();
        /** @var $pixels \ImagickPixel[] */
        foreach ($it as $row => $pixels) { /* Loop through pixel rows */
            foreach ($pixels as $column => $pixel) {
                $color = $pixel->getColor();
                // 找到棋子的点位决定旋转方向
//                if ($color['r'] == 0x3a && $color['g'] == 0x36 && $color['b'] == 0x5a) {
                if (abs($color['r'] - $checkColor['r']) <= 8 && abs($color['g'] - $checkColor['g']) <= 8 && abs($color['b'] - $checkColor['b']) <= 8) {
                    $pixel->setColor('rgba(0,0,0,0)');
                    $nextColor = $pixels[$column + 35]->getColor();
                    if (abs($nextColor['r'] - $checkColor['r']) <= 8 && abs($nextColor['g'] - $checkColor['g']) <= 8 && abs($nextColor['b'] - $checkColor['b']) <= 8) {
                        echo $row, ' ', $column, PHP_EOL;
                        $firstPointX = intval(ceil(($column * 2 + 27) / 2));
                        $firstPointY = $row;
                        $pixels[$firstPointX]->setColor('rgba(0,0,0,0)');
//                        if ($rotate == 150) {
//                            break(2);
//                        }
                    }
                }
            }
        }
        // 遍历图片
        $mid = ceil($size['height'] / 2.0);
        if ($rotate == 30) {
            $mid = $mid + 14;
        } else {
            $mid = $mid - 3;
        }

        $it->syncIterator();
        // 将游标移动到图片纵坐标中间那条线
        $endPoint = 0;
        $it->setIteratorRow($mid);
        $pixels = $it->getCurrentIteratorRow();
        $standardColor = [];
        $startColorPoint = 0;
        $endColorPoint = 0;
        for ($i = 800; $i > 0; $i--) {
            /** @var $pixels \ImagickPixel[] */
            $color = $pixels[$i]->getColor();
            if ($endPoint == 0) {
                if ((abs($color['r'] - $avgColor['r']) > 16 || abs($color['g'] - $avgColor['g']) > 16 || abs($color['b'] - $avgColor['b']) > 16)
                    || (abs($color['r'] - 255) < 10 && abs($color['g'] - 255) < 10 && abs($color['b'] - 255) < 10)) {
                    $i = $i - 3;
                    $standardColor = $pixels[$i]->getColor();
                    $startColorPoint = $i;
                    break;
                }
            }
            $pixels[$i]->setColor('rgba(0,0,0,0)');
        }
        $it->syncIterator();

        for ($i = $startColorPoint - 130; $i < $startColorPoint; $i++) {
            /** @var $pixels \ImagickPixel[] */
            $color = $pixels[$i]->getColor();
            if (
            (abs($color['r'] - $standardColor['r']) < 16 || abs($color['g'] - $standardColor['g']) < 16 || abs($color['b'] - $standardColor['b']) < 16)
            ) {
                $endColorPoint = $i + 3;
                break;

            }
            $pixels[$i]->setColor('rgba(0,0,0,0)');
        }

        $it->syncIterator();
        $resizeImage->writeImage('./resize.png');
        $endPointX = ($endColorPoint + $startColorPoint) / 2;
        $endPointY = $mid;
        var_dump($endPointX);
        var_dump($endPointY);
        var_dump($firstPointX);
        var_dump($firstPointY);
        if ($firstPointX == 0) {
            echo '没有扫描到起始，请手动跳动后，在继续运行本程序';
            exit(1);
        }
        $distance = sqrt(pow(abs($endPointX - $firstPointX), 2) + pow(abs($firstPointX - $firstPointY), 2)) *1.945;
        var_dump($distance);
        exec("~/Downloads/platform-tools/adb shell input swipe 100 100 100 100 " . intval($distance));
    } catch (\Exception $e) {

    }
}

//jump();
while (1) {
    jump();
    sleep(2);
}