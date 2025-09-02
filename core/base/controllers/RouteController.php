<?php

namespace core\base\controllers;

class RouteController
{
    static private $_instance;

    private function __construct() {}

    static public function getInstance()
    {
        # Проверяем храниться ли объект в свойстве
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }
        return self::$_instance = new self;
    }
}
