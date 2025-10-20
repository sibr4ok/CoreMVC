<?php

namespace core\base\settings;

use core\base\controllers\Singleton;

trait BaseSettings
{
    use Singleton {
        getInstance as SingletonInstance;
    }

    # Храниться объект Settings
    private $baseSettings;

    public static function get($property)
    {
        return self::getInstance()->$property;
    }

    public static function getInstance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        self::SingletonInstance()->baseSettings = Settings::getInstance();
        $baseProperties = self::$_instance->baseSettings->clueProperties(get_class(self::$_instance));
        self::$_instance->setProperty($baseProperties);

        return self::$_instance;
    }

    protected function setProperty($properties): void
    {
        if ($properties) {
            foreach ($properties as $name => $property) {
                $this->$name = $property;
            }
        }
    }
}