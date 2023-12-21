<?php

namespace App\models;

use PDO;
use PDOException;

class WatchModel
{
    private $conn;
    private $table_name = 'watch';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * @param $values
     * @return bool
     */
    public function create($values): bool
    {
        try {

            $query = "INSERT INTO " . $this->table_name . " SET ";

            $key_value_pairs = array();

            foreach ($values as $key => $value) {
                $key_value_pairs[] = $key . " = :" . $key;
            }

            $query .= implode(", ", $key_value_pairs);

            $stmt = $this->conn->prepare($query);

            foreach ($values as $key => $value) {
                $stmt->bindValue(":" . $key, $value);
            }

            if ($stmt->execute()) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            echo "Ошибка при создании записи: " . $e->getMessage();
            return false;
        }
    }


    /**
     * Обновляет запись в базе данных по указанному полю и его значению.
     * @param string $field
     * @param mixed $field_value
     * @param string $update_field
     * @param mixed $update_value
     * @param array $additional_conditions
     * @return bool
     */
    public function updateRecordByField(string $field, mixed $field_value, string $update_field, mixed $update_value, array $additional_conditions = []): bool
    {
        $query = "UPDATE " . $this->table_name . " SET $update_field = :update_value WHERE $field = :field_value";

        // Добавляем дополнительные условия в запрос, если они заданы
        if (!empty($additional_conditions)) {
            $query .= " AND " . implode(" AND ", array_map(function($condition_field) {
                    return "$condition_field = :$condition_field";
                }, array_keys($additional_conditions)));
        }

        // Подготавливаем запрос
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":field_value", $field_value);
        $stmt->bindValue(":update_value", $update_value);

        // Привязываем дополнительные условия к параметрам запроса
        foreach ($additional_conditions as $condition_field => $condition_value) {
            $stmt->bindValue(":$condition_field", $condition_value);
        }

        // Выполняем запрос и возвращаем результат
        return $stmt->execute();
    }


    /**
     * Универсальная функция поиска записей в базе данных.
     *
     * @param string $search_field
     * @param mixed $search_value
     * @param array $additional_conditions
     * @param string $operator 'AND' 'OR'
     * @return array Найденные записи.
     */
    public function searchRecords(string $search_field, mixed $search_value, array $additional_conditions = [], string $operator = 'AND'): array
    {
        $query = "SELECT * FROM {$this->table_name} WHERE $search_field = :search_value";

        $additional_conditions_clauses = [];

        foreach ($additional_conditions as $field => $value) {
            $additional_conditions_clauses[] = "$field = :$field";
        }

        if (!empty($additional_conditions_clauses)) {
            $query .= " $operator " . implode(" $operator ", $additional_conditions_clauses);
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search_value', $search_value);

        foreach ($additional_conditions as $field => $value) {
            $stmt->bindValue(":$field", $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}