<?php

namespace core\base\controllers;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseController
{
    use BaseMethods;

    protected $header;
    protected $content;
    protected $footer;
    protected $page;
    protected $errors;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    protected $template;
    protected $styles;
    protected $scripts;

    /**Динамичиски вызывает метод request в классе обрабатывая возможные ошибки*/
    public function route()
    {
        $controller = str_replace('/', '\\', $this->controller);
        try {
            # Проверяем существованеи метода request в классе $controller
            $object = new \ReflectionMethod($controller, 'request');
            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];
            # Создаем динамически объект и вызываем метод request
            $object->invoke(new $controller, $args);
        } catch (\ReflectionException $e) {
            throw new RouteException($e->getMessage(), 0);
        }
    }

    /**Подключает различные методы нужного контроллера */

    public function request($args)
    {
        $this->parameters = $args['parameters'];

        $inputMethod = $args['inputMethod'];
        $outputMethod = $args['outputMethod'];

        $data = $this->$inputMethod();

        # Проверяем существует ли выходящий метод если нет то отправляем только входящий метод
        if (method_exists($this, $outputMethod)) {
            $page = $this->$outputMethod($data);
            if ($page)
                $this->page = $page;
        } elseif ($data) {
            $this->page = $data;
        }



        if ($this->errors) {
            $this->writeLog($this->errors);
        }

        $this->getPage();
    }
    /**Подключает шаблон*/
    protected function render($path = '', $parameters = [])
    {
        # Принимаем массив
        @extract($parameters);

        if (!$path) {
            # Получаем пространсво класса
            $class = new \ReflectionClass($this);
            $space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
            $routes = Settings::get('routes');

            $template = $space === $routes['user']['path'] ? TEMPLATE : ADMIN_TEMPLATE;

            $path = $template . explode(
                'controller',
                strtolower((new \ReflectionClass($this))->getShortName())
            )[0];
        }
        #Начинаем запись в буфер обмена
        ob_start();

        if (!include_once $path . '.php')
            throw new RouteException("Отсутствует шаблон - $path", 0);

        return ob_get_clean();


    }
    /**Вывод страницы */
    protected function getPage()
    {
        if (is_array($this->page)) {
            foreach ($this->page as $block)
                echo $block;
        } else {
            echo $this->page;
        }
        exit();
    }
    /**Метод иницилизирует стили и скрипты  */
    protected function init($admin = false)
    {
        if (!$admin) {
            if (USER_CSS_JS["styles"]) {
                foreach (USER_CSS_JS["styles"] as $item)
                    $this->styles[] = PATH . TEMPLATE . trim($item, "/");
            }
            if (USER_CSS_JS["scripts"]) {
                foreach (USER_CSS_JS["scripts"] as $item)
                    $this->scripts[] = PATH . TEMPLATE . trim($item, "/");
            }
        } else {
            if (ADMIN_CSS_JS["styles"]) {
                foreach (ADMIN_CSS_JS["styles"] as $item)
                    $this->styles[] = PATH . ADMIN_TEMPLATE . trim($item, "/");
            }
            if (ADMIN_CSS_JS["scripts"]) {
                foreach (ADMIN_CSS_JS["scripts"] as $item)
                    $this->scripts[] = PATH . ADMIN_TEMPLATE . trim($item, "/");
            }
        }
    }
}