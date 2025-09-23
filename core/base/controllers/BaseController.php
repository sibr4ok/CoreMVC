<?php

namespace core\base\controllers;

use core\base\exceptions\RouteException;

abstract class BaseController
{
    protected $page;
    protected $errors;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    public function route()
    {
        $controller = str_replace('/', '\\', $this->controller);
        try {
            # Проверяем существованеи метода request в классе $controller
            $object = new \ReflectionMethod($controller, 'request');
            $args = [
                'paramaters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];
            $object->invoke(new $controller, $args);
        } catch (\ReflectionException $e) {
            throw new RouteException($e->getMessage());
        }
    }

    public function request($args)
    {
        $this->parameters = $args['parameters'];

        $inputMethod = $args['inputMethod'];
        $outputMethod = $args['outputMethod'];

        $this->$inputMethod();

        $this->page = $this->$outputMethod();

        if ($this->errors) {
            //$this->writeLog();
        }

        $this->getPage();
    }

    protected function render($path = '', $parameters = [])
    {
        extract($parameters);

        if(!$path) {
            $path  =  TEMPLATE . '/' . explode('controller',
                strtolower((new \ReflectionClass($this))->getShortName()))[0];
        }
        #Начинаем запись в буфер обмена 
        ob_start();

        if(!include_once $path . '.php') throw new RouteException("Отсутствует шаблон - $path");

        return ob_get_clean();


    }

    protected function getPage()
    {
        exit($this->page);
    }
}
