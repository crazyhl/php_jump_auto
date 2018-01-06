<?php
/**
 * Created by PhpStorm.
 * User: haoliang
 * Date: 2018/1/6
 * Time: 上午9:29
 */

function jump_one_step()
{
    $role_at_left = 0; // 这个值代表小人在图片的左边，然后再图片的右边查找需要跳动的位置
    $role_at_right = 1; // 这个值代表小人再图片的右边，然后再图片的左边查找需要跳动的位置
    // 需要查找小人的
    $role_color_arr = [
        'r' => 0x36,
        'g' => 0x3c,
        'b' => 0x66,
    ];

    // 截图的文件名
    $screenShotName = 'screenShot.png';
    try {
        // 抽取截图，此截图方式，并不是支持全部的手机，如果不支持，那么请采用保存到 sd 卡，然后再复制出来的方案,就是下面那两行的注释
        exec("./adb shell screencap -p > " . $screenShotName);
//        exec("./adb shell screencap -p /sdcard/" . $screenShotName);
//        exec("./adb pull /sdcard/" . $screenShotName . " ./" . $screenShotName);
        $image = new Imagick('./' . $screenShotName);
        // 获取图片大小
        $imageSize = $image->getImageGeometry();
        // 获取图片的迭代器
        $it = $image->getPixelIterator();
        // 找一个安全的点，作为基准值 // 这个基准值是用来定位用来跳跃的点的顶点和侧边的点
        $it->setIteratorRow(650);
        /** @var $pixels \ImagickPixel[] */
        $pixels = $it->getCurrentIteratorRow();
        $avgColor = $pixels[650]->getColor();

//        var_dump($avgColor);
        // 重置一下迭代器
        $it->resetIterator();
        // 设置跳过的高度
        $skipHeight = ceil($imageSize['height'] / 3);

        // 设置起始的行
        $it->setIteratorRow($skipHeight);
        $rolePositionArea = [
            'start' => null,
            'end' => null,
        ];
        // 遍历小人的位置范围
        for ($i = 0; $i < $skipHeight; $i++) {
            $pixels = $it->getCurrentIteratorRow();
//            var_dump($pixels);
            foreach ($pixels as $column => $pixel) {
                $color = $pixel->getColor();
                if (abs($color['r'] - $role_color_arr ['r']) <= 5
                    && abs($color['g'] - $role_color_arr ['g']) <= 5
                    && abs($color['b'] - $role_color_arr ['b']) <= 5) {
//                    $pixel->setColor('rgba(0,0,0,0)');
                    if (empty($rolePositionArea['start'])) {
                        $rolePositionArea['start'] = [
                            'row' => $skipHeight + $i,
                            'column' => $column,
                        ];
                    } else {
                        $rolePositionArea['end'] = [
                            'row' => $skipHeight + $i,
                            'column' => $column,
                        ];
                    }
                }
            }
//            $it->syncIterator();
            $it->next();
        }
        // 计算小人中心点
        $rolePosition = [
            'row' => ($rolePositionArea['start']['row'] + $rolePositionArea['end']['row']) / 2,
            'column' => ($rolePositionArea['start']['column'] + $rolePositionArea['end']['column']) / 2,
        ];
        var_dump($rolePosition);
        // 把计算出来的小人的中心点标记出来一下
        $it->setIteratorRow($rolePosition['row']);
        $pixels = $it->getCurrentIteratorRow();
        $pixels[$rolePosition['column']]->setColor('rgba(0,0,0,0)');
        $it->syncIterator();
        // 计算需要跳到棋盘的位置
        // 这里需要注意的是，从上往下找顶点是没问题的，但是需要根据小人的所在位置，判定，从左往右找，还是从右往左找
        // 首先寻找棋盘的顶点
        $direction = $role_at_left; // 0 代表小人再图片的左边，
        if ($rolePosition['column'] > $imageSize['width'] / 2) {
            $direction = $role_at_right;
        }
        $it->setIteratorRow($skipHeight);
        // 计算查查找棋盘每行的起始和终止位置
        if ($direction == $role_at_left) {
            $findTargetColumnStart = ceil($imageSize['width'] / 2);
            $findTargetColumnEnd = $imageSize['width'] - 1;
        } else {
            $findTargetColumnStart = 0;
            $findTargetColumnEnd = floor($imageSize['width'] / 2);
        }
        $it->setIteratorRow($skipHeight);

        $targetPositionArea = [
            'start' => null,
            'end' => null,
        ];
        // 遍历目标的位置范围
        for ($i = $skipHeight; $i < $skipHeight + 400; $i++) {
            $pixels = $it->getCurrentIteratorRow();
            for ($j = $findTargetColumnStart; $j <= $findTargetColumnEnd; $j++) {
                $color = $pixels[$j]->getColor();
                if ((abs($color['r'] - $avgColor['r']) > 16
                        || abs($color['g'] - $avgColor['g']) > 16
                        || abs($color['b'] - $avgColor['b']) > 16)
                    || (abs($color['r'] - 255) < 10
                        && abs($color['g'] - 255) < 10 &&
                        abs($color['b'] - 255) < 10)) {
//                    $pixels[$j]->setColor('rgba(0,0,0,0)');
                    if (empty($targetPositionArea['start'])) {
                        $targetPositionArea['start'] = [
                            'row' => $i,
                            'column' => $j
                        ];
                    } else if (empty($targetPositionArea['end'])) {
                        $targetPositionArea['end'] = [
                            'row' => $i,
                            'column' => $j
                        ];
                    } else if ($j > $targetPositionArea['end']['column']) {
                        $targetPositionArea['end'] = [
                            'row' => $i,
                            'column' => $j
                        ];
                    }
                }
            }
//            $it->syncIterator();
            $it->next();
        }


        $targetPosition = [
            'row' => $targetPositionArea['end']['row'],
            'column' => $targetPositionArea['start']['column'],
        ];
        var_dump($targetPosition);
        // 把计算出来的小人的中心点标记出来一下
        $it->setIteratorRow($targetPosition['row']);
        $pixels = $it->getCurrentIteratorRow();
        $pixels[$targetPosition['column']]->setColor('rgba(0,0,0,0)');
        $it->syncIterator();
        $image->writeImage('./cover_' . time() . '.png');


        // 绘图已经完成了，下一步就是计算两点的距离了
        // 利用勾股定理

        $distance = sqrt(pow(abs($targetPosition['row'] - $rolePosition['row']), 2)
            + pow(abs($targetPosition['column'] - $rolePosition['column']), 2));
        var_dump($distance);
        exec("./adb shell input swipe 100 100 100 100 " . intval($distance * 1.343));
    } catch (Exception $e) {
        // imagick 会抛出异常，所以再最外面拦截一下
        echo 'Exception: ', 'Code: ', $e->getCode(), ' Message: ', $e->getMessage(), PHP_EOL;
    }
}


while (1) {
    jump_one_step();
    sleep(3);
}