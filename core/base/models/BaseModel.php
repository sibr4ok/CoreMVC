<?php

namespace core\base\models;

use core\base\controllers\Singleton;
use core\base\exceptions\DbException;

class BaseModel
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
     * @param mixed $set
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

    protected function createFields($set, $table = false)
    {
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields']))
            ? $set['fields'] : ['*'];

        $table = $table ? "$table." : "";

        $fields = "";

        foreach ($set["fields"] as $field) {
            $fields .= $table . $field . ",";
        }

        return $fields;
    }

    protected function createWhere($set, $table = false, $instruction = "WHERE")
    {
        $table = $table ? "$table." : "";

        $where = "";

        if (is_array($set["where"]) && !empty($set["where"])) {

            $set["operand"] = ((is_array($set["operand"])) && !empty($set["operand"]))
                ? $set["operand"] : ["="];

            $set["condition"] = ((is_array($set["condition"])) && !empty($set["condition"]))
                ? $set["condition"] : ["AND"];

            $where = $instruction;

            $operand_count = 0;
            $condition_count = 0;

            foreach ($set["where"] as $key => $item) {
                $where .= ' ';

                if ($set["operand"][$operand_count]) {
                    $operand = $set["operand"][$operand_count];
                    $operand_count++;
                } else {
                    $operand = $set["operand"][$operand_count - 1];
                }

                if ($set["condition"][$condition_count]) {
                    $condition = $set["condition"][$condition_count];
                    $condition_count++;
                } else {
                    $condition = $set["condition"][$condition_count - 1];
                }

                if ($operand === 'IN' || $operand === 'NOT IN') {

                    if (is_string($item) && strpos($item, 'SELECT') === 0) {
                        $in_str = $item;
                    } else {
                        $temp_item = is_array($item) ? $item : explode(',', $item);

                        $in_str = '';

                        foreach ($temp_item as $v) {
                            $in_str .= "'" . addslashes(trim($v)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . " (" . rtrim($in_str, ',') . ") " . $condition;
                } elseif (strpos($operand, "LIKE") !== false) {

                    $like_template = explode("%", $operand);

                    foreach ($like_template as $lt_key => $lt_value) {
                        if (!$lt_value) {
                            if (!$lt_key) {
                                $item = "%" . $item;
                            } else {
                                $item .= "%";
                            }
                        }
                    }

                    $where .= $table . $key . " LIKE '" . addslashes($item) . "' $condition";

                } else {
                    if (strpos($item, "SELECT") === 0) {
                        $where .= $table . $key . "$operand(" . addslashes($item) . ") $condition";
                    } else {
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
                    }
                }
            }
            $where = substr($where, 0, strrpos($where, " $condition"));
        }
        return $where;

    }

    protected function createJoin($set, $table, $new_where = false)
    {

        $fields = '';
        $join = '';
        $where = '';

        if ($set['join']) {

            $join_table = $table;

            foreach ($set['join'] as $key => $item) {

                if (is_int($key)) {
                    if (!$item['table'])
                        continue;
                    else
                        $key = $item['table'];
                }
                if ($join)
                    $join .= ' ';
                if ($item['on']) {

                    $join_fields = [];

                    switch (2) {
                        case isset($item['on']['fields']) && count($item['on']['fields']):
                            $join_fields = $item['on']['fields'];
                            break;

                        case count($item['on']):
                            $join_fields = $item['on'];
                            break;

                        default:
                            continue 2;
                    }

                    if (!$item['type'])
                        $join .= 'LEFT JOIN ';
                    else
                        $join .= trim(strtoupper($item['type'])) . " JOIN ";

                    $join .= $key . ' ON ';

                    if ($item['on']['table'])
                        $join .= $item['on']['table'];
                    else
                        $join .= $join_table;

                    $join .= "." . $join_fields[0] . '=' . $key . '.' . $join_fields[1];

                    $join_table = $key;

                    if ($new_where) {

                        if ($item['where']) {
                            $new_where = false;
                        }

                        $group_condition = 'WHERE';
                    } else {
                        $group_condition = $item['group_condition'] ? $item['group_condition'] : ' AND';
                    }

                    $fields .= $this->createFields($item, $key);
                    $where .= $this->createWhere($item, $key, $group_condition);
                }
            }
        }
        return compact('fields', 'join', 'where');
    }
    protected function createOrder($set = [], $table = false)
    {
        $table = $table ? "$table." : "";

        $order_by = '';

        if (is_array($set['order']) && !empty($set['order'])) {
            $set['order_direction'] = (is_array($set['order_direction']) && !empty($set['order_direction']))
                ? $set['order_direction'] : ['ASC'];

            $order_by = "ORDER BY ";

            $direct_count = 0;
            foreach ($set['order'] as $order) {
                if ($set['order_direction'][$direct_count]) {
                    $order_direction = strtoupper($set['order_direction'][$direct_count]);
                    $direct_count++;
                } else {
                    $order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
                }
                $order_by .= is_int($order) ? $order . " $order_direction," : $table . $order . " $order_direction,";
            }
            $order_by = rtrim($order_by, ",");
        }
        return $order_by;
    }

}