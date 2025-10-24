<?php

namespace core\admin\controllers;
use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{
    use BaseMethods;

    protected array $linkArr = [];
    protected string $parsingLogFile = 'parsing_log.txt';
    /* ! Без точек и пробелов*/
    protected array $fileArr = ['jpg', 'jpeg', 'png', 'xls', 'xlsx'];

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

        if(!$_SESSION['res']['answer']) $_SESSION['res']['answer'] = '<div class="success">Sitemap created</div>';
        $this->redirect();
    }

    /**
     * Метод для парсинга
     * @param $url string ссылка которую парсим
     * @param $index int индекс в массиве со ссылками
     * @return mixed
     */
    protected function parsing(string $url, int $index = 0)
    {

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

        /* \d - спец символ цифр; \.? - может быть точка */
        if(!preg_match("/HTTP\/\d\.?\d?\s+20\d/uis", $output)){

            $this->writeLog('Не корректная ссылка при парсинге -' . $url, $this->parsingLogFile);

            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap created' .'</div>';

            return false;
        }

        /* Регулярное выражение
         u - флаг поиск по многобайтным кодировкам; i - регистр независимый;
         s - многострочный поиск; \s+ - пробел более раз; */
        if(!preg_match("/content-type:\s+text\/html/uis", $output)){

            /* Разрегистрируем ячейку массива и восстановим порядок массива*/
            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            return false;
        }

        /* Регулярное выражение для разбора ссылок
        () - переменная; \1 - объявлении первой переменной;
        *? - любые значения; [^>] - все значения кроме '>';
        */
        preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?"(.+?)"[^>]*?>/ui', $output, $links);

        if(isset($links[1])){
            foreach($links[1] as $link){

                if($link === "/" || $link === SITE_URL . "/") continue;

                /* Проверяем ссылку на расширение */
                foreach($this->fileArr as $ext){
                    if(preg_match("/$ext\s*?$/ui", $link)) continue 2;
                }

                if(str_starts_with($link, "/")){
                    $link = SITE_URL . $link;
                }

                if(str_starts_with($link, SITE_URL) && $link !== "#" && !in_array($link, $this->linkArr)){

                    if($this->filter($link)){

                        $this->linkArr[] = $link;
                        //$this->parsing($link, count($this->linkArr) - 1);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Метод для фильтрации определенных ссылок
     * @param $link mixed
     * @return void
     */
    protected function filter($link) : bool
    {
        return true;
    }

    /**
     * Метод для построения карты сайта .xml с расширением DOMDocument
     * @return void
     */
    protected function createSitemap(){


    }
}