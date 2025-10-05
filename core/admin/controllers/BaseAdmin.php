<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseAdmin extends BaseController
{
    protected $model;

    protected $table;
    protected $columns;
    protected $data;

    protected $menu;
    protected $title;

    protected function inputData()
    {

        $this->init(true);

        $this->title = "VG engine";

        if (!$this->model)
            $this->model = Model::getInstance();
        if (!$this->menu)
            $this->menu = Settings::get("projectTables");

        $this->sendNoCacheHeaders();

    }
    protected function outputData()
    {
    }

    protected function sendNoCacheHeaders()
    {
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0,pre-check=0");
    }

    protected function execBase()
    {
        self::inputData();
    }

    protected function createTableData()
    {
        if (!$this->table)
            $this->table = $this->parameters ? array_keys($this->parameters)[0]
                : Settings::get("defaultTable");

        $this->columns = $this->model->showColumns($this->table);
        if (!$this->columns)
            new RouteException("Не найдены поля в таблице - $this->table", 2);
    }

    /**
     * метод, который в зависимости от таблиц, если найдет подобного рода файлы,
     *  подключит классы этих файлов, класс этого файла,
     *  вызовет у него некий базовый метод который должен быть по дефолту,
     *  и дальше именно в этих классах мы будем осуществлять кодирование.
     * @param mixed $args
     * @return void
     */
    protected function expansion($args = [])
    {
        $filename = explode('_', $this->table);
        $className = '';
        foreach ($filename as $item) {
            $className .= ucfirst($item);
        }

        $class = Settings::get('expansion') . $className . "Expansion";

        if (is_readable($_SERVER['DOCUMENT_ROOT'] . '/' . $class . '.php')) {

            $class = str_replace('/', '\\', $class);

            $exp = $class::getInstance();

            $res = $exp->expansion($args);
        }


    }

}