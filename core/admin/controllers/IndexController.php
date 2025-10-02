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

        $res = $db->delete($table, [
            'where' => ['id' => 8],
            'join' => [
                [
                    'table' => 'students',
                    'on' => [
                        'table' => 'teachers',
                        'fields' => ['student_id', 'id']
                    ]
                ]
            ]
        ]);

        exit("This admin panel");
    }

}