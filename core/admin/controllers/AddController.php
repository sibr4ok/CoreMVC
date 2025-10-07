<?php

namespace core\admin\controllers;

use core\admin\controllers\BaseAdmin;

class AddController extends BaseAdmin
{
    protected function inputData()
    {
        # Наследуем InputData от BaseAdmin
        if (!$this->userID) {
            $this->execBase();
        }

        $this->createTableData();

        // Разбираем колонки по блокам
        $this->createOutputData();
    }


}