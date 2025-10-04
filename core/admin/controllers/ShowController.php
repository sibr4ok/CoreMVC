<?php

namespace core\admin\controllers;

class ShowController extends BaseAdmin
{
    protected function InputData()
    {
        # Наследуем InputData от BaseAdmin
        $this->execBase();

        # Выбираем из какой таблицы нам тащить данные
        $this->createTableData();

        $this->createData();

        return $this->expansion(get_defined_vars());
    }

    protected function outputData()
    {

    }
}