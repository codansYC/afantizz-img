<?php

/**
 * Created by PhpStorm.
 * User: lekuai
 * Date: 2018/1/22
 * Time: 下午2:44
 */
namespace app\models;

use yii\db\ActiveRecord;

class Image extends ActiveRecord
{

    static public function tableName()
    {
        return 'image';
    }

}