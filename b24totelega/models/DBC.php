<?php
namespace App\models;

use PDO;
use PDOException;

class DBC
{
    private $config; // Переменная для хранения конфигурационных данных
    private $conn;   // Переменная для хранения соединения с базой данных

    // Конструктор класса, который принимает конфигурационные данные
    public function __construct($config) {
        $this->config = $config;
        $this->connect(); // Устанавливаем соединение при создании объекта
    }

    // Метод для установки соединения с базой данных
    private function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->config['host']};dbname={$this->config['database']}",
                $this->config['username'],
                $this->config['password']
            );
            // Устанавливаем режим ошибок PDO на исключения
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Устанавливаем кодировку соединения
            $this->conn->exec("SET NAMES utf8");
        } catch(PDOException $e) {
            // Обработка ошибок подключения к базе данных
            echo "Ошибка подключения: " . $e->getMessage();
            // Завершаем выполнение скрипта в случае ошибки подключения
            exit();
        }
    }

    // Метод для подготовки SQL-запроса
    public function prepare($query)
    {
        if ($this->conn) {
            return $this->conn->prepare($query);
        }

// Обработка ошибки отсутствия соединения
        echo "Соединение с базой данных отсутствует.";
        return null;
    }

}