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
            'where' => ['name' => 'masha, dasha, ivan', 'fio' => 'Masha', 'surname' => 'Sergeevna'],
            'operand' => ['IN', 'LIKE%', '<>'],
            'condition' => ["OR", 'AND'],
            'order' => ['fio', 'name',],
            'order_direction' => ['ASC', 'DESC'],
            'limit' => '1'
        ]);

        exit("This admin panel");
    }

}