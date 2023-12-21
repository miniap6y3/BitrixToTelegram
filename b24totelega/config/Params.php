<?php

namespace App\config;

class Params
{
    private $config;

    public function __construct()
    {
        $this->loadConfigs();
    }

    private function loadConfigs()
    {
        // Загрузка данных из конфигурационных файлов
        $config2 = require('params-db.php');
        $config3 = require('params-bots.php');

        // Сохраняем данные в виде ассоциативных массивов, каждый доступный по своему ключу
        $this->config['params-db'] = $config2;
        $this->config['params-bots'] = $config3;
    }

    public function get($key)
    {
        return $this->config[$key] ?? null;
    }
}