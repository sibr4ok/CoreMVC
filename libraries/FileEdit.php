<?php

namespace libraries;

class FileEdit
{
    protected array $imgArray = [];
    protected string|bool $directory;

    public function addFile($directory = false): array
    {

        if(!$directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            else $this->directory = $directory;

        foreach($_FILES as $key => $file){

            if(is_array($file['name'])){
                $file_arr = [];

                for($i = 0; $i < count($file['name']); $i++){
                    if(!empty($file['name'][$i])){

                        $file_arr['name'] = $file['name'][$i];
                        $file_arr['type'] = $file['type'][$i];
                        $file_arr['tmp_name'] = $file['tmp_name'][$i];
                        $file_arr['error'] = $file['error'][$i];
                        $file_arr['size'] = $file['size'][$i];

                        $res_name = $this->createFile($file_arr);

                        if($res_name) $this->imgArray[$key][] = $res_name;
                    }
                }
            }else{
                if($file['name']){

                    $res_name = $this->createFile($file);

                    if($res_name) $this->imgArray[$key] = $res_name;
                }
            }
        }

        return  $this->getFiles();
    }

    protected function createFile(array $file): false|string
    {
        // Разбираем название файла
        $fileNameArr = explode('.', $file['name']);
        // Вытаскиваем расширение файла
        $ext = $fileNameArr[count($fileNameArr) - 1];
        unset($fileNameArr[count($fileNameArr) - 1]);
        // Собираем название файла без расширения
        $fileName = implode('.', $fileNameArr);

        $fileName = new TextModify()->translit($fileName);

        $fileName = $this->checkFile($fileName, $ext);
        // формируем полный путь
        $fileFullName = $this->directory . $fileName;

        // перемещаем файл
        if($this->uploadFile($file['tmp_name'], $fileFullName))
            return $fileName;

        return false;
    }

    protected function checkFile($fileName, $ext, $FileLastName = ''): string
    {
        // Проверяем существование файла
        if(!file_exists($this->directory . $fileName . $FileLastName . '.' . $ext))
            return $fileName . $FileLastName . '.' . $ext;

        return $this->checkFile($fileName, $ext, '_' . hash('crc32', time() . mt_rand(1, 1000)));
    }

    protected function uploadFile($tmpName, $destination): bool
    {
        if(move_uploaded_file($tmpName, $destination))
            return true;

        return false;
    }

    public function getFiles(): array
    {
        return $this->imgArray;
    }

}