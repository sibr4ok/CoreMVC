<?php

namespace core\admin\controllers;

use core\admin\controllers\BaseAdmin;
use core\base\settings\Settings;

class AddController extends BaseAdmin
{
    protected function inputData()
    {
        # Наследуем InputData от BaseAdmin
        if (!$this->userID) {
            $this->execBase();
        }

        $this->createTableData();
        # Получение данных из связанных таблиц
        $this->createForeignData();
        # Формируем свойство для переключателя
        $this->createRadio();
        # Разбираем колонки по блокам
        $this->createOutputData();

    }
    protected function createForeignProperty($arr, $rootItems)
    {
        # Задаём имя для таблицы в ячейку id = 0
        if ($rootItems['tables'] && in_array($this->table, $rootItems['tables'])) {
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }
        # Получаем колонки из привязанной таблицы
        $columns = $this->model->showColumns($arr['REFERENCED_TABLE_NAME']);
        # Ищем поле name
        $name = '';
        if ($columns['name']) {
            $name = 'name';
        } else {
            # Если явно нет поля name
            foreach ($columns as $key => $value) {
                if (strpos($key, 'name') !== false)
                    $name = $key . ' as name';
            }
            if (!$name)
                $name = $columns['id_row'] . ' as name';
        }

        if ($this->data) {
            # Если мы отсылаемся на нашу же таблицу, то не нужно получать их данные
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table) {
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                $operand[] = '<>';
            }
        }
        # Получаем данные с второй таблицы id, name
        $foreign = $this->model->read($arr['REFERENCED_TABLE_NAME'], [
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $name],
            'where' => $where,
            'operand' => $operand
        ]);

        if ($foreign) {
            if ($this->foreignData[$arr['COLUMN_NAME']]) {

                foreach ($foreign as $value) {
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            } else {
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }
        }
    }

    protected function createForeignData($settings = false)
    {
        if (!$settings)
            $settings = Settings::getInstance();
        $rootItems = $settings::get("rootItems");

        # Получаем внешний ключ с БД
        $keys = $this->model->showForeignKeys($this->table);

        if ($keys) {
            foreach ($keys as $key) {
                $this->createForeignProperty($key, $rootItems);
            }
        } elseif ($this->columns['parent_id']) {
            $arr['COLUMN_NAME'] = 'parent_id';
            $arr['REFERENCED_TABLE_NAME'] = $this->table;
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];

            $this->createForeignProperty($arr, $rootItems);
        }
        return;
    }

}