<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;

class IndexController extends BaseController
{

    protected function inputData()
    {
        $db = Model::getInstance();

        $table = 'teachers';

        $files['galery_img'] = ["new_red.jpg"];

        $_POST["id"] = 3;
        $_POST["name"] = "";
        $_POST["content"] = "lalalala";

        $res = $db->update($table);

        exit("This admin panel");
    }

}