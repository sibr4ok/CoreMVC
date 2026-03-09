<?php

namespace core\base\controllers;

trait Singleton
{
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }
        self::$_instance = new self;

        if (method_exists(self::$_instance, "connect"))
            self::$_instance->connect();

        return self::$_instance;
    }
    private function __construct()
    {
    }
    private function __clone()
    {
    }
}