<?php

namespace core\base\models;

use core\base\exceptions\DbException;

abstract class BaseModelMethods
{
    protected array $sql_func = ['NOW() '];
    protected array $table_rows;

    /**
     * @throws DbException
     */
    protected function createFields($set, $table = false, $join = false): string
    {
        $fields = "";
        $join_structure = false;

        if(($join || isset($set['join_structure']) && $set['join_structure']) && $table) {
            $join_structure = true;

            $this->showColumns($table);

            if(isset($this->table_rows[$table]['multi_id_row'])) $set['fields'] = [];
        }

        $concat_table = $table && !$set['no_concat'] ? $table . '.' : '';

        if(!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']) {
            if(!$join) {
                $fields = $concat_table . '*,';
            }else{
                foreach($this->table_rows[$table] as $key => $item){
                    if($key !== 'id_row' && $key !== 'multi_id_row'){
                        $fields .= $concat_table . $key . ' as TABLE' . $table . 'TABLE_' . $key . ',';
                    }
                }
            }
        }else{
            $id_field = false;

            foreach($set['fields'] as $field){
                if($join_structure && !$id_field && $this->table_rows[$table] === $field) {
                    $id_field = true;
                }

                if($field){
                    if($join && $join_structure) {
                        if(preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $match)) {
                            $fields .= $concat_table . $match[1] . ' as TABLE' . $table . 'TABLE_' . $match[2] . ',';
                        }else{
                            $fields .= $concat_table . $field . ' as TABLE' . $table . 'TABLE_' . $field . ',';
                        }
                    }else{
                        $fields .= $concat_table . $field . ',';
                    }
                }
            }
            if(!$id_field && $join_structure) {
                if($join){
                    $fields .= $concat_table . $this->table_rows[$table]['id_row'] . ' as TABLE' . $table . 'TABLE_' . $this->table_rows[$table]['id_row'] . ',';
                }else{
                    $fields .= $concat_table . $this->table_rows[$table]['id_row'] . ',';
                }
            }
        }
        return $fields;
    }

    protected function createWhere($set, $table = false, $instruction = "WHERE"): string
    {
        $table = ($table && !$set['no_concat']) ? "$table." : "";
        $where = "";

        if (is_string($set["where"])) {
            return $instruction . ' ' . trim($set['where']);
        }
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
                    if (is_string($item) && str_starts_with($item, 'SELECT')) {
                        $in_str = $item;
                    } else {
                        $temp_item = is_array($item) ? $item : explode(',', $item);

                        $in_str = '';
                        foreach ($temp_item as $v) {
                            $in_str .= "'" . addslashes(trim($v)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . " (" . rtrim($in_str, ',') . ") " . $condition;
                } elseif (str_contains($operand, "LIKE")) {

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
                    if (str_starts_with($item, "SELECT")) {
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

    /**
     * @throws DbException
     */
    protected function createJoin($set, $table, $new_where = false) : array
    {
        $fields = '';
        $join = '';
        $where = '';
        $tables = '';

        if ($set['join']) {
            $join_table = $table;

            foreach ($set['join'] as $key => $item) {

                if (is_int($key)) {
                    if (!$item['table']) continue;
                        else $key = $item['table'];
                }
                if ($join) $join .= ' ';

                if ($item['on']) {
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

                    if (!$item['type']) $join .= 'LEFT JOIN ';
                        else $join .= trim(strtoupper($item['type'])) . " JOIN ";

                    $join .= $key . ' ON ';

                    if ($item['on']['table']) $join .= $item['on']['table'];
                        else $join .= $join_table;

                    $join .= "." . $join_fields[0] . '=' . $key . '.' . $join_fields[1];
                    $join_table = $key;
                    $tables .= "," . trim($join_table);

                    if ($new_where) {
                        if ($item['where']) {
                            $new_where = false;
                        }
                        $group_condition = 'WHERE';
                    } else {
                        $group_condition = $item['group_condition'] ?: ' AND';
                    }

                    $fields .= $this->createFields($item, $key, $set['join_structure']);
                    $where .= $this->createWhere($item, $key, $group_condition);
                }
            }
        }
        return compact('fields', 'join', 'where', 'tables');
    }
    protected function createOrder($set = [], $table = false) : string
    {
        $table = ($table && !$set['no_concat']) ? "$table." : "";

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

    protected function createInsert($fields, $files, $except) :array
    {
        $insert_arr = [];
        $insert_arr['fields'] = '(';
        $array_type = array_keys($fields)[0];

        if (is_int($array_type)) {
            $check_fields = false;
            $count_fields = 0;

            foreach ($fields as $item) {
                $insert_arr['values'] .= '(';
                if (!$count_fields) $count_fields = count($item);
                $j = 0;

                foreach ($item as $row => $value) {
                    if ($except && in_array($row, $except)) continue;
                    if (!$check_fields) $insert_arr['fields'] .= $row . ',';

                    if (in_array($value, $this->sql_func)) {
                        $insert_arr['values'] .= $value . ',';
                    } elseif ($value === 'NULL' || $value === NULL) {
                        $insert_arr['values'] .= "NULL" . ',';
                    } else {
                        $insert_arr['values'] .= "'" . addslashes($value) . "',";
                    }
                    $j++;

                    if ($j === $count_fields) break;
                }

                if ($j < $count_fields) {
                    for (; $j < $count_fields; $j++) {
                        $insert_arr['values'] .= "NULL,";
                    }
                }

                $insert_arr['values'] = rtrim($insert_arr['values'], ',') . '),';

                if (!$check_fields) $check_fields = true;
            }
            $insert_arr['values'] = rtrim($insert_arr['values'], ',');

        } else {
            $insert_arr['values'] = '(';

            if ($fields) {
                foreach ($fields as $row => $value) {

                    if ($except && in_array($row, $except)) continue;

                    $insert_arr['fields'] .= $row . ',';

                    if (in_array($value, $this->sql_func)) {
                        $insert_arr['values'] .= $value . ',';
                    } elseif ($value === 'NULL' || $value === NULL) {
                        $insert_arr['values'] .= "NULL" . ',';
                    } else {
                        $insert_arr['values'] .= "'" . addslashes($value) . "',";
                    }
                }
            }
            if ($files) {
                foreach ($files as $row => $file) {
                    $insert_arr['fields'] .= $row . ',';

                    if (is_array($file))
                        $insert_arr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                    else
                        $insert_arr['values'] .= "'" . addslashes($file) . "',";
                }
            }
            $insert_arr['values'] = rtrim($insert_arr['values'], ',') . ')';
        }
        $insert_arr['fields'] = rtrim($insert_arr['fields'], ',') . ')';

        return $insert_arr;
    }
    protected function createUpdate($fields, $files, $except) : string
    {
        $update = '';
        if ($fields) {
            foreach ($fields as $row => $value) {
                if ($except && in_array($row, $except)) continue;

                $update .= $row . '=';
                if (in_array($value, $this->sql_func)) {
                    $update .= "$value,";
                } else {
                    $update .= "'" . addslashes($value) . "',";
                }
            }
        }

        if ($files) {
            foreach ($files as $row => $file) {

                $update .= $row . "=";
                if (is_array($file))
                    $update .= "'" . addslashes(string: json_encode($file)) . "',";
                else
                    $update .= "'" . addslashes($file) . "',";
            }
        }
        return rtrim($update, ",");
    }

    protected function joinStructure($res, $table): array
    {
        $join_arr = [];
        $id_row = $this->table_rows[$table]['id_row'];

        foreach ($res as $value){
            if($value){
                if(!isset($join_arr[$value[$id_row]])) $join_arr[$value[$id_row]] = [];

                foreach ($value as $key => $item){
                    if(preg_match('/TABLE(.+)?TABLE/u', $key, $match)){
                        $table_name_normal = $match[1];

                        if(!isset($this->table_rows[$table_name_normal]['multi_id_row'])){
                            $join_id_row = $value[$match[0] . '_' . $this->table_rows[$table_name_normal]['id_row']];
                        }else{
                            $join_id_row = '';
                            foreach($this->table_rows[$table_name_normal]['multi_id_row'] as $multi){
                                $join_id_row .= $value[$match[0] . '_' . $multi];
                            }
                        }
                        $row = preg_replace('/TABLE(.+)?TABLE_/u', '', $key);

                        if($join_id_row && !isset($join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row])){
                            $join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row] = $item;
                        }
                    }
                    $join_arr[$value[$id_row]][$key] = $item;
                }
            }
        }
        return $join_arr;
    }
}