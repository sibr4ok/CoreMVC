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

    protected function createData($arr = [], $add_arr = true)
    {
        $fields = [];
        $order = [];
        $order_direction = [];

        if ($add_arr) {

            if (!$this->columns["id_row"])
                return $this->data = [];

            $fields[] = $this->columns["id_row"] . " as id";
            if ($this->columns['name'])
                $fields['name'] = 'name';
            if ($this->columns['img'])
                $fields['img'] = 'img';

            if (count($fields) < 3) {
                foreach ($this->columns as $key => $value) {
                    if (!$fields['name'] && strpos($key, 'name') !== false) {
                        $fields['name'] = $key . ' as name';
                    }
                    if (!$fields['img'] && strpos($key, 'img') === 0) {
                        $fields['img'] = $key . ' as img';
                    }
                }
            }

            if ($arr['fields']) {
                if (is_array($arr['fields']))
                    $fields = Settings::getInstance()->arrayMergeRecursive($fields, $arr['fields']);
                else
                    $fields[] = $arr['fields'];

            }
            # Сортировки:
            if ($this->columns['parent_id']) {
                if (!in_array('parent_id', $fields))
                    $fields[] = 'parent_id';
                $order[] = 'parent_id';
            }
            if ($this->columns['menu_position']) {
                $order[] = 'menu_position';
            } elseif ($this->columns['date']) {
                $order_direction = $order ? ['ASC', 'DESC'] : ['DESC'];

                $order[] = 'date';
            }
            // Склеиваем сортировки из поступившего массива
            if ($arr['order']) {
                if (is_array($arr['order']))
                    $order = Settings::getInstance()->arrayMergeRecursive($order, $arr['order']);
                else
                    $order[] = $arr['order'];
            }
            if ($arr['order_direction']) {
                if (is_array($arr['order_direction']))
                    $order_direction = Settings::getInstance()->arrayMergeRecursive($order_direction, $arr['order_direction']);
                else
                    $order_direction[] = $arr['order_direction'];
            }

        } else {

            if (!$arr)
                return $this->data = [];

            $fields = $arr['fields'];
            $order = $arr['order'];
            $order_direction = $arr['order_direction'];

        }

        $this->data = $this->model->read($this->table, [
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $order_direction
        ]);
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