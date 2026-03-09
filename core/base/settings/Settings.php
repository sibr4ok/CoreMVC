<?php

namespace core\base\settings;

use core\base\controllers\Singleton;

class Settings
{
    use Singleton;
    private array $routes = [
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
    private string $expansion = 'core/admin/expansion/';
    private string $messages = 'core/base/messages/';
    private string $defaultTable = "teachers";

    private string $formTemplates = PATH . 'core/admin/view/include/form_templates/';
    private array $projectTables = [
        'teachers' => ['name' => 'Учителя', 'img' => 'pages.png'],
        'students' => ['name' => 'Ученики']
    ];

    private array $templateArr = [
        'text' => ['name'],
        'textarea' => ['content'],
        'radio' => ['visible'],
        'select' => ['menu_position', 'parent_id'],
        'img' => ['img'],
        'gallery_img' => ['gallery_img']
    ];
    private array $translate = [
        'name' => ['Название', 'Не более 100 символов.'],
        'content' => ['Контент']
    ];
    private array $radio = [
        'visible' => ['Нет', 'Да', 'default' => 'Нет']
    ];
    private array $blockNeedle = [
        'vg-rows' => [],
        'vg-img' => ['img'],
        'vg-content' => ['content']
    ];
    # настройки, чтобы узнать, какие таблицы считаются "корневыми"
    private array $rootItems = [
        'name' => 'Корневая',
        'tables' => ['articles']
    ];

    private array $validation = [
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

    public function clueProperties($class): array
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
                    if (is_int($key) && !in_array($value, $baseArray)) {
                        $baseArray = $value;
                        //array_push($baseArray, $value);
                    } else {
                        $baseArray[$key] = $value;
                    }
                }
            }
        }

        return $baseArray;
    }
}