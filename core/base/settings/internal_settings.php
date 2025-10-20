<?php

# Проверяем константу, или завершаем работу скрипта
defined('VG_ACCESS') or die('Access denied');

# Путь к шаблону
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view/';
const UPLOAD_DIR = 'userfiles/';

const COOKIE_VERSION = '1.0.0';
# Ключ шифрования для кука файлов
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
    'styles' => ['css/main.css'],
    'scripts' => []
];

const USER_CSS_JS = [
    'styles' => [],
    'scripts' => []
];

use core\base\exceptions\RouteException;

/**
 * @throws RouteException
 */
function autoloadMainClasses($class_name): void
{
    $class_name = str_replace('\\', '/', $class_name);

    if (!include_once $class_name . '.php') {
        throw new RouteException("Не верное имя файла для подключения - $class_name", 1);
    }
}

spl_autoload_register('autoloadMainClasses');
