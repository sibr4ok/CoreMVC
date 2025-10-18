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
    private $expansion = 'core/admin/expansion/';
    private $messages = 'core/base/messages/';
    private $defaultTable = "teachers";

    private $formTemplates = PATH . 'core/admin/view/include/form_templates/';
    private $projectTables = [
        'teachers' => ['name' => 'Учителя', 'img' => 'pages.png'],
        'students' => ['name' => 'Ученики']
    ];

    private $templateArr = [
        'text' => ['name'],
        'textarea' => ['content'],
        'radio' => ['visible'],
        'select' => ['menu_position', 'parent_id'],
        'img' => ['img'],
        'gallery_img' => ['gallery_img']
    ];
    private $translate = [
        'name' => ['Название', 'Не более 100 символов.'],
        'content' => ['Контент']
    ];
    private $radio = [
        'visible' => ['Нет', 'Да', 'default' => 'Нет']
    ];
    private $blockNeedle = [
        'vg-rows' => [],
        'vg-img' => ['img'],
        'vg-content' => ['content']
    ];
    # настройки, чтобы узнать, какие таблицы считаются "корневыми"
    private $rootItems = [
        'name' => 'Корневая',
        'tables' => ['articles']
    ];

    private $validation = [
        'name' => ['empty' => true, 'trim' => true],
        'price' => ['int' => true],
        'login' => ['empty' => true, 'trim' => true],
        'password' => ['crypt' => true, 'empty' => true],
        'content' => ['count' => 100, 'trim' => true],
        'description' => ['count' => 160, 'trim' => true],
    ];

    public static function get($property)
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