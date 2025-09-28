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

        $res = $db->read($table, [
            'fields' => ['id', 'name'],
            'where' => ['name' => "O'Raily"],
        ]);

        exit("This admin panel");
    }

}