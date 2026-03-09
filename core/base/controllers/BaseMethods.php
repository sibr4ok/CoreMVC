<?php

namespace core\base\controllers;

trait BaseMethods
{

    protected function clearStr($str): array|string
    {
        if (is_array($str)) {
            foreach ($str as $key => $item)
                $str[$key] = trim(strip_tags($item));
            return $str;
        } else {
            return trim(strip_tags($str));
        }
    }

    protected function clearNum($num): int
    {
        if (is_numeric($num))
            return $num *= 1;
        return 0;
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] == "POST";
    }

    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === "XMLHttpRequest";
    }

    /** Перенаправляет адрес сайта*/
    protected function redirect($http = false, $code = 0)
    {
        if ($code) {
            $codes = ["301" => "http/1.1 301 Move Permanently"];

            if ($codes[$code])
                header($codes[$code]);
        }

        if ($http)
            $redirect = $http;
        else
            $redirect = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : PATH;

        header("Location: $redirect");

        exit;
    }

    protected function writeLog($message, $file = 'log.txt', $event = "Fault")
    {
        $dateTime = new \DateTime();

        $str = "$event: " . $dateTime->format('d-m-Y G:i:s') . " - $message\r\n";

        file_put_contents("log/$file", $str, FILE_APPEND);
    }
}