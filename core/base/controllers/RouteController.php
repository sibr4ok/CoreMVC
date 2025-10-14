<?php

namespace core\base\controllers;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

class RouteController extends BaseController
{
    use Singleton;

    /** Настройки */
    protected $routes;

    private function __construct()
    {
        # Получаем адресную строку
        $adress_str = $_SERVER['REQUEST_URI'];

        if ($_SERVER['QUERY_STRING']) {
            $adress_str = substr(
                $adress_str,
                0,
                strpos($adress_str, '?' . $_SERVER['QUERY_STRING'])
            );
        }

        # Директория в которой мы работаем
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));

        # Проверка на соответсвие текущей директорией с директорией указанной в настройках
        if ($path === PATH) {

            # Проверка на символ слеш в конце
            if (strrpos($adress_str, '/') === strlen($adress_str) - 1 && strrpos($adress_str, '/') !== strlen($path) - 1) {
                $this->redirect(rtrim($adress_str, '/'), 301);
            }

            # Достаем пути с настроек
            $this->routes = Settings::get('routes');
            if (!$this->routes)
                throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);

            $url = explode('/', substr($adress_str, strlen(PATH)));

            # Проверка на административную панель
            if ($url[0] && $url[0] === $this->routes['admin']['alias']) {

                # Удаляем alias админ панели из массива
                array_shift($url);

                if ($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])) {

                    # Имя плагина
                    $plugin = array_shift($url);

                    $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin) . 'Settings';

                    # Проверяем есть ли файл с настройками плагина
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php')) {
                        # Подключаем найстройки плагина
                        $pluginSettings = str_replace('/', '\\', $pluginSettings);

                        $this->routes = $pluginSettings::get('routes');
                    }

                    # Дирректория для плагина
                    $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
                    $dir = str_replace('//', '/', $dir);

                    $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;

                    # Для кого создаем маршрут
                    $route = 'plugins';
                } else {

                    $this->controller = $this->routes['admin']['path'];

                    # Для кого создаем маршрут
                    $route = 'admin';
                }
            } else {
                $url = explode('/', substr($adress_str, strlen(PATH)));

                $this->controller = $this->routes['user']['path'];

                # Для кого создаем маршрут
                $route = 'user';
            }

            $hrUrl = $this->routes[$route]['hrUrl'];

            $this->createRoute($route, $url);

            # Проверка параметров
            if ($url[1]) {
                $count = count($url);
                $key = '';

                if (!$hrUrl) {
                    $i = 1;
                } else {
                    $this->parameters['alias'] = $url[1];
                    $i = 2;
                }

                for (; $i < $count; $i++) {
                    if (!$key) {
                        $key = $url[$i];
                        $this->parameters[$key] = '';
                    } else {
                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }
        } else {
            throw new RouteException('Не коректная дирректория сайта', 1);
        }
    }

    # Определяет путь к контроллеру и методам
    private function createRoute($var, $arr)
    {
        $route = [];

        # Проверяем есть ли контроллер
        if (!empty($arr[0])) {
            # Проверяем есть ли контроллер В настройках
            if ($this->routes[$var]['routes'][$arr[0]]) {
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

                $this->controller .= ucfirst($route[0] . 'Controller');
            } else {
                $this->controller .= ucfirst($arr[0] . 'Controller');
            }
        } else {
            $this->controller .= $this->routes['default']['controller'];
        }

        $this->inputMethod = $route[1] ?: $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ?: $this->routes['default']['outputMethod'];

        return;
    }
}
