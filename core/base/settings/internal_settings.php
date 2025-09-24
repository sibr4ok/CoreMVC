<?php

# Проверяем константу, или завершаем работу скрипта
defined('VG_ACCESS') or die('Access denied');

# Путь к шаблоннам
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATES = 'core/admin/views/';

const COOKIE_VERSION = '1.0.0';
# Ключ шифрования для куки файлов
const CRYPT_KEY = '';
# Время сессии для админа
const COOKIE_TIME = 60;
# Время блокировки при подборе пароля
const BLOCK_TIME = 3;

# Для постраничной навигации
const QTY = 8;
const QTY_LINES = 3;

# Пути к CSS и JS файлам
const ADMIN_CSS_JS = [
    'styles' => [],
    'scripts' => []
];

const USER_CSS_JS = [
    'styles' => ['css/style.css'],
    'scripts' => []
];

use core\base\exceptions\RouteException;

function autoloadMainClasses($class_name)
{
    $class_name = str_replace('\\', '/', $class_name);

    if (!include_once $class_name . '.php') {
        throw new RouteException("Не верное имя файла для подключения - $class_name");
    }
}

spl_autoload_register('autoloadMainClasses');
