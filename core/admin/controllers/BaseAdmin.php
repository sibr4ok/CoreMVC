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
    protected $foreignData;

    protected $adminPath;

    protected $menu;
    protected $title;

    protected $translate;
    protected $blocks = [];

    protected function inputData()
    {

        $this->init(true);

        $this->title = "VG engine";

        if (!$this->model)
            $this->model = Model::getInstance();
        if (!$this->menu)
            $this->menu = Settings::get("projectTables");
        if (!$this->adminPath)
            $this->adminPath = PATH . Settings::get("routes")["admin"]["alias"] . "/";

        $this->sendNoCacheHeaders();

    }
    protected function outputData()
    {
        if (!$this->content) {

            $arg = func_get_arg(0);
            $vars = $arg ? $arg : [];

            // Обьевляем шаблон
            //if (!$this->template) $this->template = ADMIN_TEMPLATE . 'show';

            $this->content = $this->render($this->template, $vars);

        }

        $this->header = $this->render(ADMIN_TEMPLATE . "include/header");
        $this->footer = $this->render(ADMIN_TEMPLATE . "include/footer");

        return $this->render(ADMIN_TEMPLATE . "layout/default");
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

    protected function createTableData($settings = false)
    {
        if (!$this->table)
            if ($this->parameters) {
                $this->table = array_keys($this->parameters)[0];
            } else {
                if (!$settings)
                    $settings = $settings = Settings::getInstance();
                $this->table = $settings::get("defaultTable");
            }

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
    protected function expansion($args = [], $settings = false)
    {
        $filename = explode('_', $this->table);
        $className = '';
        foreach ($filename as $item) {
            $className .= ucfirst($item);
        }
        if (!$settings)
            $path = Settings::get('expansion');
        elseif (is_object($settings))
            $path = $settings::get('expansion');
        else
            $path = $settings;

        $class = $path . $className . "Expansion";

        if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')) {

            $class = str_replace('/', '\\', $class);

            $exp = $class::getInstance();

            # Переносим динамически свойства в этот объект ссылкой
            foreach ($this as $name => $value) {
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args);
        } else {

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if (is_readable($file))
                return include $file;
        }
        return null;
    }
    protected function createOutputData($settings = false)
    {
        if (!$settings)
            $settings = Settings::getInstance();

        // Получаем свойства с настроек
        $blocks = $settings->get('blockNeedle');
        $this->translate = $settings->get('translate');

        if (!$blocks || !is_array($blocks)) {

            foreach ($this->columns as $column => $v) {
                if ($column === "id_row")
                    continue;

                if (!$this->translate[$column])
                    $this->translate[$column][] = $column;

                $this->blocks[0][] = $column;
            }
            return;
        }
        // Дефолтный блок
        $default = array_keys($blocks)[0];

        foreach ($this->columns as $column => $i) {
            if ($column === "id_row")
                continue;

            $insert = false;

            foreach ($blocks as $block => $v) {
                // Создаем поле блока
                if (!array_key_exists($block, $this->blocks))
                    $this->blocks[$block] = [];
                // Если есть колонка в блоке настроек
                if (in_array($column, $v)) {
                    $this->blocks[$block][] = $column;
                    $insert = true;
                    break;
                }
            }

            // Если нет элемента в настройках то заносим в блок по умолчанию
            if (!$insert)
                $this->blocks[$default][] = $column;
            if (!$this->translate[$column])
                $this->translate[$column][] = $column;

        }
        return;
    }
    protected function createRadio($settings = false)
    {
        if (!$settings)
            $settings = Settings::getInstance();

        $radio = $settings->get("radio");

        if ($radio) {
            foreach ($this->columns as $column => $i) {
                if ($radio[$column])
                    $this->foreignData[$column] = $radio[$column];
            }
        }
    }

}