<?php

/* Заново переписать*/

namespace core\admin\controllers;
use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{
    use BaseMethods;

    protected array $all_links = [];
    protected array $temp_links = [];

    protected int $maxLinks = 5000;
    protected string $parsingLogFile = 'parsing_log.txt';
    /* Без точек и пробелов ! */
    protected array $fileArr = ['jpg', 'jpeg', 'png', 'xls', 'xlsx'];

    /* Слэши экранировать ! */
    protected array $filterArr = [
        'url' => [],
        'get' => []
    ];

    protected function inputData($links_counter = 1)
    {
        /* Проверяем существование расширения curl */
        if(!function_exists('curl_init')){
            $this->cancel(0, "Library CURL as absent. Creation of sitemap impossible", "", true);
        }

        if (!$this->userID) $this->execBase();

        if(!$this->checkParsingTable()){
            $this->cancel(0, "Parsing sitemap table failed", "", true);
        }

        /* Снимаем ограничения по исполнению скрипта */
        set_time_limit(0);

        /* Возвращаем данные из таблицы */
        $reserve = $this->model->read('parsing_data')[0];

        foreach ($reserve as $name => $item){
            if($item) $this->$name = json_decode($item, true);
                else $this->$name = [SITE_URL];
        }

        $this->maxLinks = (int)$links_counter > 1 ? ceil($this->maxLinks / $links_counter) : $this->maxLinks;

        while($this->temp_links){

            $temp_link_counter = count($this->temp_links);

            $links = $this->temp_links;

            $this->temp_links = [];

            if($temp_link_counter > $this->maxLinks){

                $links = array_chunk($links, ceil($temp_link_counter / $this->maxLinks));

                $count_chunks = count($links);

                for($i = 0; $i < $count_chunks; $i++){

                    $this->parsing($links[$i]);
                    unset($links[$i]);

                    if($links){

                        $this->model->edit('parsing_data', [
                            'fields' => [
                                'temp_links' => json_encode(array_merge(...$links)),
                                'all_links' => json_encode($this->all_links)
                            ],
                        ]);
                    }
                }

            }
            //else {$this->parsing($links);}

            $this->model->edit('parsing_data', [
                'fields' => [
                    'temp_links' => json_encode($this->temp_links),
                    'all_links' => json_encode($this->all_links)
                ],
            ]);
        }

        $this->createSitemap();

        if(!$_SESSION['res']['answer']) $_SESSION['res']['answer'] = '<div class="success">Sitemap created</div>';
        $this->redirect();
    }

    /**
     * Метод для парсинга
     * @param $url string ссылка которую парсим
     * @param $index int индекс в массиве со ссылками
     * @return bool
     */
    protected function parsing(string $url, int $index = 0) : bool
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
        if(!preg_match("/HTTP\/\d\.?\d?\s+20\d/ui", $output)){

            $this->writeLog('Не корректная ссылка при парсинге -' . $url, $this->parsingLogFile);

            unset($this->all_links[$index]);
            $this->all_links = array_values($this->all_links);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap created' .'</div>';

            return false;
        }

        /* Регулярное выражение
         u - флаг поиск по многобайтным кодировкам; i - регистр независимый;
         s - многострочный поиск; \s+ - пробел более раз; */
        if(!preg_match("/content-type:\s+text\/html/ui", $output)){

            /* Разрегистрируем ячейку массива и восстановим порядок массива*/
            unset($this->all_links[$index]);
            $this->all_links = array_values($this->all_links);

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
                    if(preg_match("/$ext\s*?$|\?[^\/]/ui", $link)) continue 2;
                }

                if(str_starts_with($link, "/")){
                    $link = SITE_URL . $link;
                }

                if(str_starts_with($link, SITE_URL) && $link !== "#" && !in_array($link, $this->all_links)){

                    if($this->filter($link)){

                        $this->all_links[] = $link;
                        //$this->parsing($link, count($this->all_links) - 1);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Метод для фильтрации определенных ссылок
     * @param $link string
     * @return bool
     */
    protected function filter(string $link) : bool
    {
        if($this->filterArr){

            foreach($this->filterArr as $type => $values){

                if($values){
                    foreach($values as $item){

                        if($type === "url"){
                            if(preg_match("/^[^?]*$item/ui", $link)) return false;
                        }
                        if($type === "get"){
                            if(preg_match("/(\?|&amp;|=|&)$item(=|&amp;|&|$)/ui", $link)) return false;
                        }
                    }
                }
            }
        }
        return true;
    }


    protected function checkParsingTable(): bool
    {

        $tables = $this->model->showTables();


        if(!in_array('parsing_data', $tables)){
            $query = "CREATE TABLE parsing_data (all_links TEXT, temp_links TEXT)";

            if(!$this->model->query($query, 'c') ||
                !$this->model->create('parsing_data' , [
                'fields' => ['all_links' => '', 'temp_links' => '']
                ])){ return false; }
        }
        return true;
    }


    /** Отвечает за написание log
     * @param $success int успешность выполнения
     * @param $message string сообщение пользователю
     * @param $log_message string сообщение для log
     * @param $exit bool флаг выхода
     */
    protected function cancel(int $success = 0, string $message = "", string $log_message = "", bool $exit = false){

        $exitArr = [];

        $exitArr['success'] = $success;
        $exitArr['message'] = $message ?: "ERROR PARSING";
        $log_message = $log_message ?: $exitArr['message'];

        $class = 'success';

        if(!$exitArr['success']){

            $class = 'error';

            $this->writeLog($log_message, "parsing_log.txt");

        }

        if($exit){
            $exitArr['message'] = '<div> class="' . $class . '"' . $exitArr['message'] . '</div>';
            exit(json_encode($exitArr));
        }
    }

    /**
     * Метод для построения карты сайта .xml с расширением DOMDocument
     * @return void
     */
    protected function createSitemap(){


    }
}