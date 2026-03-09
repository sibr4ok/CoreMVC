<?php

function print_arr($arr): void
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

if (!function_exists('mb_str_replace')) {
    function mb_str_replace($need, $text_replace, $haystack): string
    {
        return implode($text_replace, explode($need, $haystack));
    }
}