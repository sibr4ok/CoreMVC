<?php

namespace core\base\models;

use core\base\controllers\Singleton;
use core\base\exceptions\DbException;

class BaseModel extends BaseModelMethods
{
    use Singleton;

    protected $db;

    private function __construct()
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
     * @param mixed $query
     * @param mixed $crud = r - SELECT, c - INSERT, u - UPDATE, d - DELETE
     * @param mixed $return_id
     * @throws \core\base\exceptions\DbException
     */
    final public function query($query, $crud = 'r', $return_id = false)
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
     * Summary of create
     * @param mixed $table - таблица БД
     * @param array $set
     * 'fields' => ['id', 'name'],
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
    final public function read($table, $set = [])
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
     * @param mixed $table - таблица для вставки данных
     * @param array $set - массив параметров
     * fields => [поле=>значения]; если не указан, то обрабатывается $_POST [поле=>значения]
     * разрешена передача NOW() в качестве Mysql функции обычной строкой
     * files => [поле=>значения]; можно подать массив вида [поле=>[массив значений]]
     * except => ['исключение 1','исключение 2'] - исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет идентификатор вставленной записи
     * @return mixed
     */
    final public function create($table, $set)
    {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : false;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        $set['return_id'] = $set['return_id'] ? true : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        if ($insert_arr) {
            $query = "INSERT INTO $table ({$insert_arr['fields']}) VALUES ({$insert_arr['values']})";

            return $this->query($query, 'c', $set['return_id']);
        }

        return false;
    }
}