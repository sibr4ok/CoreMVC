<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;

class IndexController extends BaseController
{

    protected function inputData()
    {
        $db = Model::getInstance();

        $table = 'teacher';

        $files['gallery_img'] = ["red.jpg", "blue.jpg", "black.jpg"];
        $files["img"] = "main_img.jpg";

        $res = $db->create($table, [
            'fields' => ['name' => 'Katya', 'content' => 'hello'],
            'files' => $files,
            'except' => ['content']
        ]);

        exit("This admin panel");
    }

}