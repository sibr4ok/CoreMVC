<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

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

    protected string $alias;
    protected $fileArray;

    protected $messages;

    protected $translate;
    protected $blocks = [];

    protected $templateArr;
    protected $formTemplates;
    protected $noDelete;

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
        if (!$this->templateArr)
            $this->templateArr = Settings::get('templateArr');
        if (!$this->formTemplates)
            $this->formTemplates = Settings::get('formTemplates');

        if (!$this->messages)
            $this->messages = include $_SERVER["DOCUMENT_ROOT"] . PATH . Settings::get('messages') . 'informationMessages.php';

        $this->sendNoCacheHeaders();

    }
    protected function outputData()
    {
        if (!$this->content) {

            $arg = func_get_arg(0);
            $vars = $arg ?: [];

            // Объявляем шаблон
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
                    $settings = Settings::getInstance();
                $this->table = $settings::get("defaultTable");
            }

        $this->columns = $this->model->showColumns($this->table);
        if (!$this->columns)
            new RouteException("Не найдены поля в таблице - $this->table", 2);
    }

    /**
     * Метод, который в зависимости от таблиц, если найдет подобного рода файлы,
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

            // Если нет элемента в настройках, то заносим в блок по умолчанию
            if (!$insert)
                $this->blocks[$default][] = $column;
            if (!$this->translate[$column])
                $this->translate[$column][] = $column;

        }
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

    protected function checkPost($settings = false)
    {
        if ($this->isPost()) {
            # Валидация полей 
            $this->clearPostFields($settings);

            $this->table = $this->clearStr($_POST["table"]);
            unset($_POST["table"]);
            # Изменяем данные таблицы
            if ($this->table) {
                $this->createTableData($settings);
                $this->editData();
            }
        }
    }

    protected function clearPostFields($settings, &$arr = [])
    {
        if (!$arr)
            $arr = &$_POST;

        if (!$settings)
            $settings = Settings::getInstance();

        $id = $_POST[$this->columns['id_row']] ?: false;

        $validate = $settings->get("validation");
        if (!$this->translate)
            $this->translate = $settings::get("translate");

        foreach ($arr as $key => $value) {

            if (is_array($value)) {
                $this->clearPostFields($settings, $value);

            } else {

                if (is_numeric($value)) {
                    $arr[$key] = $this->clearNum($value);
                }
                if ($validate) {

                    if ($validate[$key]) {

                        $answer = $this->translate[$key] ? $this->translate[$key][0] : $key;

                        if ($validate[$key]['crypt']) {
                            if ($id) {
                                if (empty($value)) {
                                    unset($arr[$key]);
                                    continue;
                                }

                                $arr[$key] = md5($value);
                            }
                        }

                        if ($validate[$key]['empty'])
                            $this->emptyFields($value, $answer, $arr);

                        if ($validate[$key]['trim'])
                            $arr[$key] = trim($value);

                        if ($validate[$key]['int'])
                            $arr[$key] = $this->clearNum($value);

                        if ($validate[$key]['count'])
                            $this->countChar($value, $validate[$key]['count'], $answer, $arr);
                    }
                }
            }
        }
        return true;
    }

    protected function emptyFields($value, $answer, $arr = [])
    {
        if (empty($value)) {
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . " $answer" . '</div>';
            # Сохраняем данные в сессию и перенаправляем на эту страницу снова
            $this->addSessionData($arr);
        }
    }

    protected function countChar($str, $counter, $answer, $arr)
    {
        if (mb_strlen($str) > $counter) {

            $str_res = mb_str_replace('$1', $answer, $this->messages['count']);
            $str_res = mb_str_replace('$2', $counter, $str_res);

            $_SESSION['res']['answer'] = '<div class="error">' . $str_res . '</div>';
            # Сохраняем данные в сессию и перенаправляем на эту страницу снова
            $this->addSessionData($arr);
        }
    }
    protected function addSessionData($arr = [])
    {
        if (!$arr)
            $arr = $_POST;

        foreach ($arr as $key => $item) {
            $_SESSION['res'][$key] = $item;
        }
        $this->redirect();
    }

    protected function editData($return_id = false)
    {
        $id = false;
        $method = 'create';
        $where = [];

        // Если есть id в POST, то это изменение данных
        if ($_POST[$this->columns['id_row']]) {

            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]) :
                $this->clearStr($_POST[$this->columns['id_row']]);
            if ($id) {

                $where = [$this->columns['id_row'] => $id];
                $method = 'update';
            }
        }
        # Если в таблице есть время, то записываем в него NOW() - текущее время
        foreach ($this->columns as $key => $item) {
            if (is_array($item) && ($item['Type'] === 'date' || $item['Type'] === 'datetime')) {
                if (!$_POST[$key])
                    $_POST[$key] = 'NOW() ';
            }
        }

        $this->createFile();
        // создаем url для страницы
        $this->createAlias($id);
        $this->updateMenuPosition();

        //исключаем поля из системы добавления
        $except = $this->checkExceptFields();

        //отправляем запрос в бд
        $res_id = $this->model->$method($this->table, [
            'files' => $this->fileArray,
            'where' => $where,
            'return_id' => true,
            'except' => $except
        ]);

        if (!$id && $method === 'create') {
            # записываем новый id
            $_POST[$this->columns['id_row']] = $res_id;

            $answerSuccess = $this->messages['addSuccess'];
            $answerFail = $this->messages['addFail'];
        } else {
            $answerSuccess = $this->messages['editSuccess'];
            $answerFail = $this->messages['editFail'];
        }

        # для расширения
        $this->expansion(get_defined_vars());

        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        # формируем ответы
        if ($res_id) {
            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

            if (!$return_id)
                $this->redirect();

            return $_POST[$this->columns['id_row']];
        } else {
            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';

            if (!$return_id)
                $this->redirect();
        }
        return null;
    }

    /* Метод исключающий поля из системы добавления*/
    protected function checkExceptFields($arr = []): array
    {
        $except = [];

        if(!$arr) {
            $arr = $_POST;
        }else{
            foreach ($arr as $key => $item) {
                if(!$this->columns[$key]) $except[] = $key;
            }
        }
        return $except;
    }

    protected function createFile()
    {
        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile();
    }

    protected function createAlias($id = false): void
    {
        if(!empty($this->columns['alias'])) {

            $alias_str = [];

            if(!$_POST['alias']) {

                if ($_POST['name']) {

                    $alias_str = $this->clearStr($_POST['name']);
                } else {

                    foreach ($_POST as $key => $value) {
                        if (str_contains($key, 'name') && $value) {
                            $alias_str = $this->clearStr($value);
                            break;
                        }
                    }
                }
            }else{

                $alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);
            }

            // Система транслитерации
            $textModify = new \libraries\TextModify();
            $alias = $textModify->translit($alias_str);

            // Проверяем есть ли ссылка в таблице
            $where['alias'] = $alias;
            $operand[] =  '=';

            if($id){

                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }

            $res_alias = $this->model->read($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];

            if(!$res_alias) {

                $_POST['alias'] = $alias;
            }else{

                $this->alias = $alias;
                $_POST['alias'] = '';
            }

            // Если мы редактируем, то отправляем браузеру 301 код для смены ссылки
            if($_POST['alias'] && $id) {

                if(method_exists($this, 'checkOldAlias')) $this->checkOldAlias($id);
            }
        }

    }

    protected function updateMenuPosition()
    {
    }

    protected function checkAlias($id): bool
    {
        if($id){

            if(!empty($this->alias)){
                $this->alias .= '-' . $id;

                $this->model->update($this->table, [
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id],
                ]);

                return true;
            }
        }
        return false;
    }
}