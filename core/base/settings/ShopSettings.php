<?php

namespace core\base\settings;

use core\base\controllers\Singleton;
use core\base\settings\Settings;

class ShopSettings
{
    use BaseSettings;
    private array $routes = [
        'plugins' => [
            'dir' => false,
            'routes' => []
        ]
    ];
}
