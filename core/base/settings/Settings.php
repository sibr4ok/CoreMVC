<?php

namespace core\base\settings;

use core\base\controllers\Singleton;

class Settings
{
    use Singleton;
    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controllers/',
            'hrUrl' => false,
            'routes' => []
        ],
        'settings' => [
            'path' => 'core/base/settings/'
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => false
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            'routes' => []
        ],
        'default' => [
            'controller' => 'IndexController',
            'inputMethod' => 'inputData',
            'outputMethod' => 'outputData'

        ]
    ];

    private $defaultTable = "teachers";
    private $projectTables = [
        'teachers' => ['name' => 'Учителя', 'img' => 'pages.png'],
        'students' => ['name' => 'Ученики']
    ];
    private $expansion = 'core/admin/expansion/';

    static public function get($property)
    {
        return self::getInstance()->$property;
    }

    public function clueProperties($class)
    {
        $baseProperties = [];

        foreach ($this as $name => $item) {
            $property = $class::get($name);

            if (is_array($property) && is_array($item)) {
                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
            } elseif (!$property) {
                $baseProperties[$name] = $this->$name;
            }
        }
        return $baseProperties;
    }

    public function arrayMergeRecursive()
    {
        $arrays = func_get_args();

        $baseArray = array_shift($arrays);

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && is_array($baseArray[$key])) {
                    $baseArray[$key] = $this->arrayMergeRecursive($baseArray[$key], $value);
                } else {
                    if (is_int($key)) {
                        if (!in_array($value, $baseArray))
                            array_push($baseArray, $value);
                    } else {
                        $baseArray[$key] = $value;
                    }
                }
            }
        }

        return $baseArray;
    }
}