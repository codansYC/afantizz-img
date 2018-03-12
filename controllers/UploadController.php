<?php
/**
 * Created by PhpStorm.
 * User: lekuai
 * Date: 2017/6/14
 * Time: 下午6:52
 */

namespace app\controllers;


use app\models\Image;
use app\utils\UtilHelper;
use app\utils\BizConsts;
header("Access-Control-Allow-Origin: *"); # 跨域处理
class UploadController extends BaseController {

    function actionImage()
    {
        echo 123;return;
        $folder = 'upload';
        $key = 'file';
        try {
            $file = $_FILES[$key];
            $img = $this->handleFile($file,$folder);
            $data = ['url' => (string)$img->url];
            UtilHelper::echoResult(BizConsts::SUCCESS, BizConsts::SUCCESS_MSG, $data);
        } catch (\Exception $e) {
            UtilHelper::handleException($e);
        }
    }

    function handleFile($file,$folder) {
        // 判断支持的格式
        $fileTypes = array('jpg', 'jpeg', 'gif', 'png');
        $fileParts = pathinfo($file['name']);
        $extension = strtolower($fileParts['extension']);
        if (!in_array($extension, $fileTypes)) {
            UtilHelper::echoExitResult(BizConsts::IMAGE_INVALID_ERRCODE, BizConsts::IMAGE_INVALID_ERRMSG);
        }
        // 生成目录, 拼接文件路径
        $dir = $folder . '/' .UtilHelper::getTimeStr('Ymd');
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $targetFile = $dir . '/' . strtolower(UtilHelper::getRandChar(12)) . '.jpeg';
        $middleFile = $dir . '/' . strtolower(UtilHelper::getRandChar(12)) . '.jpeg';
        $thumbFile = $dir . '/' . strtolower(UtilHelper::getRandChar(12)) . '.jpeg';
        // 临时文件
        $tempFile = $file['tmp_name'];
        $extension = next(explode("/", $file['type']));
        $imgRes = $this->compressImage($extension,$tempFile);
        imagejpeg($imgRes,$targetFile);
        $middleImageRes = $this->thumb($targetFile,800,600);
        imagejpeg($middleImageRes,$middleFile);
        $thumbImageRes = $this->thumb($targetFile);
        imagejpeg($thumbImageRes,$thumbFile);
        imagedestroy($imgRes);
        imagedestroy($middleImageRes);
        imagedestroy($thumbImageRes);

        $imageUrl = IMG_URL;
        $image = new Image();
        $image->url = $imageUrl . $targetFile;
        $image->middle_url = $imageUrl . $middleFile;
        $image->thumb_url = $imageUrl . $thumbFile;
        $image->save();
        return $image;
    }

    /*图片压缩*/
    function compressImage($extension,$file) {
        $image = $this->getResource($file, $extension);
        list($originW, $originH) = getimagesize($file);
        $imgW = $originW;
        $imgH = $originH;
        $exif = @exif_read_data($file);
        if (isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            switch ($orientation) {
                case 3:
                    $image = imagerotate($image,180,0);
                    break;
                case 6:
                    $image = imagerotate($image,-90,0);
                    $imgW = $originH;
                    $imgH = $originW;
                    break;
                case 8:
                    $image = imagerotate($image,90,0);
                    $imgW = $originH;
                    $imgH = $originW;
                    break;
                default:
                    break;
            }
        }
        $compress = imagecreatetruecolor($imgW, $imgH);
        imagecopyresampled($compress, $image, 0, 0, 0, 0, $imgW, $imgH, $imgW, $imgH);
        imagedestroy($image);
        return $compress;
    }

    function getResource($file, $extension) {
        $image = null;
        switch ($extension) {
            case 'jpg':
                $image = imagecreatefromjpg($file);
                break;
            case 'jpeg':
                $image = imagecreatefromjpeg($file);
                break;
            case 'png':
                $image = imagecreatefrompng($file);
                break;
            case 'gif':
                $image = imagecreatefromgif($file);
                break;
            default:
                break;
        }
        return $image;
    }

    function correctOrientation($image,$orientation) {

        switch ($orientation) {
            case 3:
                $image = imagerotate($image,180,0);
                break;
            case 6:
                $image = imagerotate($image,-90,0);
                break;
            case 8:
                $image = imagerotate($image,90,0);
                break;
            default:
                break;
        }
        return $image;
    }

    /*
    图片压缩、剪裁
    */
    function thumb($filename,$width=200,$height=150){
        //获取原图的图像资源
        $image = imagecreatefromjpeg($filename);
        //获取原图像$filename的宽度$width_orig和高度$height_orig
        list($width_orig,$height_orig) = getimagesize($filename);
        $ratio_orig = $width_orig / $height_orig;
        $ratio = $width / $height;
        if ($ratio_orig > $ratio) {
            $height_new = $height;
            $width_new = $height_new * $ratio_orig;
        } else {
            $width_new = $width;
            $height_new = $width_new / $ratio_orig;
        }
        //将原图缩放到这个新创建的图片资源中
        $image_scale = imagecreatetruecolor($width_new, $height_new);
        //使用imagecopyresampled()函数进行缩放设置
        imagecopyresampled($image_scale,$image,0,0,0,0,$width_new,$height_new,$width_orig,$height_orig);
        imagedestroy($image);
        // 剪裁
        if ($width_new > $width) {
            $cropX = ($width_new - $width) / 2;
            $cropY = 0;
        } else if ($height_new > $height) {
            $cropX = 0;
            $cropY = ($height_new - $height) / 2;
        } else {
            $cropX = 0;
            $cropY = 0;
        }

        $image_crop = imagecreatetruecolor($width, $height);
        //使用imagecopyresampled()函数进行剪裁
        imagecopyresampled($image_crop,$image_scale,0,0,$cropX,$cropY,$width,$height,$width,$height);

        imagedestroy($image_scale);

        return $image_crop;
    }

}

