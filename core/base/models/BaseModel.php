<?php

namespace core\base\models;

use core\base\exceptions\DbException;

abstract class BaseModel extends BaseModelMethods
{
    protected $db;

    /**
     * @throws DbException
     */
    protected function connect()
    {
        try {
            # Подключаем к БД, объект содержащий выборку даных
            $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

            # Устанавливаем кодировку соединения
            $this->db->query("SET NAMES UTF8");
        } catch (\mysqli_sql_exception $e) {

            throw new DbException("Ошибка подключения к базе данных: "
                . $e->getMessage(), 1);
        }

    }
    /**
     * @param string $query
     * @param string $crud = r - SELECT, c - INSERT, u - UPDATE, d - DELETE
     * @param bool $return_id
     * @throws DbException
     */
    final public function query(string $query, string $crud = 'r', bool $return_id = false)
    {
        try {
            $result = $this->db->query($query);
            # Проверяем эффективные ряды с выборки
            if ($this->db->affected_rows === -1) {
                throw new DbException(
                    'Ошибка в SQL запросе: '
                    . $query . ' - ' . $this->db->errno . ' ' . $this->db->error,
                    1
                );
            }

            switch ($crud) {
                //read
                case 'r':
                    # Считываем колонки из таблицы
                    if ($result->num_rows) {
                        $res = [];

                        for ($i = 0; $i < $result->num_rows; $i++) {
                            $res[] = $result->fetch_assoc();
                        }
                        return $res;
                    }
                    return false;
                //creat
                case 'c':

                    if ($return_id)
                        // Возвращает id
                        return $this->db->insert_id;
                    return true;

                default:
                    return true;
            }

        } catch (\mysqli_sql_exception $e) {
            throw new DbException("Ошибка подключения к базе данных: "
                . $e->getMessage(), 1);
        }
    }
    /**
     * Универсальный метод чтения из БД
     * @param mixed $table - таблица БД
     * @param array $set
     * 'fields' => ['id', 'name'],
     * 'no_concat' => false/true если true - не присоединять имя таблицы к полям и where
     * 'where' => ['fio' => 'Smirnova', 'name' => 'Masha'],
     * 'operand' => ['=', '<>'],
     * 'condition' => ['AND'],
     * 'order' => ['fio', 'name'],
     * 'order_direction' => ['ASC', 'DESC'],
     * 'limit' => '1'
     * 'join' => [
     *     [
     *         'table' => 'join_table1',
     *         'fields' => ['id as j_id', 'name as j_name'],
     *         'type' => 'left',
     *         'where' => ['name' => 'sasha'],
     *         'operand' => ['='],
     *         'condition' => ['OR'],
     *         'on' => ['id', 'parent_id'],
     *         'group_condition' => 'AND'
     *     ],
     *     'join_table2' => [
     *         'table' => 'join_table2',
     *         'fields' => ['id as j_id', 'name as j_name'],
     *         'type' => 'left',
     *         'where' => ['name' => 'sasha'],
     *         'operand' => ['='],
     *         'condition' => ['OR'],
     *         'on' => [
     *             'table' => 'teacher',
     *             'fields' => ['id', 'parent_id']
     *         ]
     *     ],
     * ]
     */
    final public function read($table, array $set = [])
    {
        $fields = $this->createFields($set, $table);
        $where = $this->createWhere($set, $table);

        $new_where = !$where ? true : false;

        $join_arr = $this->createJoin($set, $table, $new_where);
        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $order = $this->createOrder($set, $table);

        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);
    }
    /**
     * Универсальный метод создания БД
     * @param mixed $table - таблица для вставки данных
     * @param array $set - массив параметров
     * fields => [поле=>значения]; если не указан, то обрабатывается $_POST [поле=>значения]
     * разрешена передача NOW() в качестве Mysql функции обычной строкой
     * files => [поле=>значения]; можно подать массив вида [поле=>[массив значений]]
     * except => ['исключение 1','исключение 2'] - исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет идентификатор вставленной записи
     * @return mixed
     */
    final public function create($table, $set = [])
    {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if (!$set['fields'] && !$set['files'])
            return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        $set['return_id'] = $set['return_id'] ? true : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        $query = "INSERT INTO $table {$insert_arr['fields']} VALUES {$insert_arr['values']}";
        return $this->query($query, 'c', $set['return_id']);
    }
    /**
     * Универсальный метод обновления БД
     * @param mixed $table - таблица для вставки данных
     * @param array $set - массив параметров
     * fields => [поле=>значения]; если не указан, то обрабатывается $_POST [поле=>значения]
     * разрешена передача NOW() в качестве Mysql функции обычной строкой
     * files => [поле=>значения]; можно подать массив вида [поле=>[массив значений]]
     * except => ['исключение 1','исключение 2'] - исключает данные элементы массива из добавления в запрос
     * all_rows => true|false
     * @return mixed
     */
    final public function update($table, $set = [])
    {
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if (!$set['fields'] && !$set['files'])
            return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        if (!$set["all_rows"]) {

            if ($set["where"]) {
                $where = $this->createWhere($set);
            } else {
                $columns = $this->showColumns($table);

                if (!$columns)
                    return false;

                if ($columns['id_row'] && $set['fields'][$columns['id_row']]) {
                    $where = "WHERE " . $columns['id_row'] . "=" . $set['fields'][$columns['id_row']];
                    # Удаляем переменную
                    unset($set['fields'][$columns['id_row']]);
                }
            }
        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }
    /**
     * Универсальный метод удаления БД, с возможностью сброса полей по умолчанию(если указаны)
     * @param mixed $table - таблица БД
     * @param array $set
     * 'fields' => ['id', 'name'],
     * 'where' => ['fio' => 'Smirnova', 'name' => 'Masha'],
     * 'operand' => ['=', '<>'],
     * 'condition' => ['AND'],
     * 'join' => [
     *     [
     *         'table' => 'join_table1',
     *         'fields' => ['id as j_id', 'name as j_name'],
     *         'type' => 'left',
     *         'where' => ['name' => 'sasha'],
     *         'operand' => ['='],
     *         'condition' => ['OR'],
     *         'on' => ['id', 'parent_id'],
     *         'group_condition' => 'AND'
     *     ],
     *     'join_table2' => [
     *         'table' => 'join_table2',
     *         'fields' => ['id as j_id', 'name as j_name'],
     *         'type' => 'left',
     *         'where' => ['name' => 'sasha'],
     *         'operand' => ['='],
     *         'condition' => ['OR'],
     *         'on' => [
     *             'table' => 'teacher',
     *             'fields' => ['id', 'parent_id']
     *         ]
     *     ],
     * ]
     */
    public function delete($table, $set = [])
    {
        //$table = trim($table);

        $where = $this->createWhere($set, $table);

        $columns = $this->showColumns($table);
        if (!$columns)
            return false;

        if (is_array($set['fields']) && !empty($set['fields'])) {

            if ($columns['id_row']) {
                $key = array_search($columns['id_row'], $set['fields']);
                if ($key !== false)
                    unset($set['fields'][$key]);
            }
            $fields = [];
            foreach ($set['fields'] as $field) {
                $fields[$field] = $columns[$field]['Default'];
            }

            $update = $this->createUpdate($fields, false, false);
            $query = "UPDATE $table SET $update $where";
        } else {

            $join_arr = $this->createJoin($set, $table);
            $join = $join_arr['join'];
            $join_table = $join_arr['tables'];

            $query = "DELETE $table" . "$join_table FROM $table $join $where";

        }


        return $this->query($query, "d");

    }

    /**
     * @throws DbException
     */
    final public function showColumns($table): array
    {
        $query = "SHOW COLUMNS FROM $table";
        $res = $this->query($query);

        $columns = [];

        if ($res) {
            foreach ($res as $row) {
                $columns[$row['Field']] = $row;
                if ($row['Key'] === "PRI")
                    $columns['id_row'] = $row['Field'];
            }
        }
        return $columns;
    }

    /**
     * @throws DbException
     */
    final public function showTables(): array
    {
        $query = "SHOW TABLES";

        $tables = $this->query($query);

        $table_arr = [];

        if($tables) {
            foreach ($tables as $table) {
                $table_arr[] = reset($table);
            }
        }
        return $table_arr;
    }
}