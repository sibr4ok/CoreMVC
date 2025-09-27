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
     */
    final public function read($table, $set = [])
    {
        $fields = $this->createFields($table, $set);
        $where = $this->createWhere($table, $set);

        $join_arr = $this->createJoin($table, $set);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $order = $this->createOrder($table, $set);

        $limit = $set['limit'] ? $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);
    }

    protected function createFields($table = false, $set)
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

    protected function createWhere($table = false, $set, $instruction = "WHERE")
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

                    if (is_string($item) && strpos($item, 'SELECT')) {
                        $in_str = $item;
                    } else {
                        $temp_item = is_array($item) ? $item : explode(',', $item);

                        $in_str = '';

                        foreach ($temp_item as $v) {
                            $in_str .= "'" . trim($v) . "',";
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

                    $where .= $table . $key . " LIKE '" . $item . "' $condition";

                } else {
                    if (strpos($item, "SELECT") === 0) {
                        $where .= $table . $key . "$operand($item) $condition";
                    } else {
                        $where .= $table . $key . $operand . "'$item' $condition";
                    }
                }
            }
            $where = substr($where, 0, strrpos($where, " $condition"));
        }
        return $where;

    }
    protected function createOrder($table = false, $set = [])
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
                $order_by .= $table . $order . " $order_direction,";
            }
            $order_by = rtrim($order_by, ",");
        }
        return $order_by;
    }

}