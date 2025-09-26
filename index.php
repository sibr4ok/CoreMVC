<?php

# Константа безопасности
define('VG_ACCESS', true);

# Отправляем заголовки с типом контента и кодировки
header('Content-Type: text/html; charset=utf-8');

# Запускаем сессию / суперглобальный массив
session_start();

# Подключает файл с конфигом и настройками один раз
require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';

use core\base\exceptions\RouteException;
use core\base\exceptions\DbException;
use core\base\controllers\RouteController;

try {

    # Обращаемся к статическому методу без создания объекта
    RouteController::getInstance()->route();
} catch (RouteException $e) {

    exit($e->getMessage());
} catch (DbException $e) {

    exit($e->getMessage());
}