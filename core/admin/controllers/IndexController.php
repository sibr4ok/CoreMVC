<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;

class IndexController extends BaseController
{

    protected function inputData()
    {
        $db = Model::getInstance();

        $query = "SELECT teacher.id, teacher.name, students.id as s_id,students.name as s_name
                    FROM teacher
                    LEFT JOIN stud_teach ON teacher.id = stud_teach.teacher_id
                    LEFT JOIN students ON stud_teach.student_id = students.id";

        $res = $db->create();

        exit("This admin panel");
    }

}