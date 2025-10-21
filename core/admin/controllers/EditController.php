<?php

namespace core\admin\controllers;

class EditController extends BaseAdmin
{
    protected function inputData(){
        # Наследуем InputData от BaseAdmin
        if (!$this->userID) $this->execBase();

    }

    protected function checkOldAlias($id): void
    {

        $tables = $this->model->showTables();

        if(in_array('old_alias',$tables)){
            // Читаем ячейку alias нашей таблицы
            $old_alias = $this->model->read($this->table, [
                'fields' => ['alias'],
                'where' => [$this->columns['id_row'] => $id],
            ])[0]["alias"];

            if(isset($old_alias) && $old_alias != $_POST['alias']){
                // На всякий удаляем эту ячейку в old_alias
                $this->model->delete('old_alias', [
                    'where' => ['alias' => $old_alias, 'table_name' => $this->table],
                ]);

                $this->model->delete('old_alias', [
                    'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table],
                ]);

                $this->model->create('old_alias', [
                    'fields' => ['alias' => $old_alias, 'table_name' => $this->table, 'table_id' => $id],
                ]);
            }

        }
    }
}