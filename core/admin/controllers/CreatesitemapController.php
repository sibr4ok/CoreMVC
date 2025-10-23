<?php

namespace core\admin\controllers;
use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{
    use BaseMethods;

    protected array $linkArr = [];
    protected string $parsingLogFile = 'parsing_log.txt';
    protected array $fileArr = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'xls','xlsx'];

    protected array $filterArr = [
        'url' => [],
        'get' => []
    ];

    protected function inputData()
    {
        /* Проверяем существование расширения curl */
        if(!function_exists('curl_init')){

            $this->writeLog('Отсутствует библиотека CURL');
            $_SESSION['res']['answer'] = '<div class="error">Library CURL as absent. Creation of sitemap impossible</div>';
            $this->redirect();
        }

        /* Снимаем ограничения по исполнению скрипта */
        set_time_limit(0);
        /* Удаляем лог файл */
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile))
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);

        $this->parsing(SITE_URL);

        $this->createSitemap();

        if(!$_SESSION['res']['answer']) $_SESSION['res']['answer'] = '<div class="success">Sitemap created</div>"';
        $this->redirect();
    }

    /**
     * Метод для парсинга
     * @param $url string ссылка которую парсим
     * @param $index int индекс в массиве со ссылками
     * @return void
     */
    protected function parsing(string $url, int $index = 0)
    {
        //if(mb_strlen(SITE_URL)+1 === mb_strlen($url) && mb_strrpos($url,'/') === mb_strlen($url)-1) return;

        /* Инициализируем дескриптор */
        $curl = curl_init();

        /* Настраиваем curl:
        - Куда отправлять запросы.
        - Получаем ответы от сервера.
        - Возвращаем заголовки ответов.
        - Следовать curl за redirect
        - Ожидание ответа от сервера
        - Ограничиваем количество загружаемых данных
        */
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_RANGE, 0 - 4194304);

        /* Отправляем запрос */
        $output = curl_exec($curl);

        curl_close($curl);


        /* Регулярное выражение
         u - флаг поиск по многобайтным кодировкам; i - регистр независимый;
         s - многострочный поиск; \s+ - пробел более раз; */
        if(!preg_match("/content-type:\s+text\/html/uis", $output)){

            /* Разрегистрируем ячейку массива и восстановим порядок массива*/
            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            return;
        }

        /* \d - спец символ цифр; \.? - может быть точка */
        if(!preg_match("/HTTP\/\d\.?\d?\s+20\d/uis", $output)){

            $this->writeLog('Не корректная ссылка при парсинге -' . $url, $this->parsingLogFile);

            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap created' .'</div>';

            return;
        }


    }

    /**
     * Метод для фильтрации определенных ссылок
     * @param $link mixed
     * @return void
     */
    protected function filter($link){


    }

    /**
     * Метод для построения карты сайта .xml с расширением DOMDocument
     * @return void
     */
    protected function createSitemap(){


    }
}