<?php

namespace core\base\settings;

use core\base\controllers\Singleton;
use core\base\settings\Settings;

class ShopSettings
{
    use Singleton;

    # Храниться объект Settings
    private $baseSettings;

    private $routes = [
        'plugins' => [
            'dir' => false,
            'routes' => []
        ]
    ];

    static public function get($property)
    {
        return self::instance()->$property;
    }

    static private function instance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        self::getInstance()->baseSettings = Settings::getInstance();
        $baseProperties = self::$_instance->baseSettings->clueProperties(get_class());
        self::$_instance->setProperty($baseProperties);

        return self::$_instance;
    }

    protected function setProperty($properties)
    {
        if ($properties) {
            foreach ($properties as $name => $property) {
                $this->$name = $property;
            }
        }
    }
}
