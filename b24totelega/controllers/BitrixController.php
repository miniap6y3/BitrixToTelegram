<?php

namespace App\controllers;

use CRest;
use Exception;

class BitrixController
{
    /**
     * Получаем информацию о сделке в битрикс24 по id.
     * @param int $deal_id
     * @return array
     */
    public function getDealInfo(int $deal_id): array
    {
        try {
            $data = CRest::call(
                'crm.deal.get',
                [
                    'id' => $deal_id,
                ]
            );

            if (isset($data['result'])){
                return $data['result'];
            }

            throw new \RuntimeException("Сделка с №$deal_id не найдена.");
        } catch (Exception $e)  {
            // Обработка ошибок
            error_log("Ошибка при получении статуса: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return [];
        }
    }

    /**
     * Получаем название стадии в битрикс 24
     * @param string $stageId
     * @return string
     */
    public function getNameStatus(string $stageId): string
    {
        try {
            $data = CRest::call(
                'crm.status.list',
                [
                    'filter' => [
                        'ENTITY_ID' => 'DEAL_STAGE',
                        'STATUS_ID' => $stageId,
                    ]
                ]
            );

            if (isset($data['result'][0]['NAME'])) {
                return $data['result'][0]['NAME'];
            }

            throw new \RuntimeException("Статус с ID $stageId не найден.");
        } catch (Exception $e) {
            // Обработка ошибок
            error_log("Ошибка при получении статуса: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return "Ошибка при получении статуса.";
        }
    }

    /**
     * Отправка комментария в сделку
     * @param int $deal_id
     * @param string $comments
     * @return mixed|string
     */
    public function timelineCommentAdd(int $deal_id, string $comments): mixed
    {
        try {
            $data = CRest::call(
                'crm.timeline.comment.add',
                [
                    'fields' => [
                        'ENTITY_ID' => $deal_id,
                        'ENTITY_TYPE' => 'deal',
                        'COMMENT' => $comments,
                        'AUTHOR_ID' => '1', // ID пользователя, от которого оставляется комментарий
                    ]
                ]
            );

            if (isset($data['result'])){
                return $data['result'];
            }

            throw new \RuntimeException("Статус с ID $deal_id не найден.");
        } catch (Exception $e) {
            // Обработка ошибок
            error_log("Ошибка при отправке комментария: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return "Ошибка при отправке комментария.";
        }
    }

    /**
     * Изменение статуса сделки
     * @param int $deal_id
     * @param string $status
     * @return mixed|string
     */
    public function changeDialStatus(int $deal_id, string $status): mixed
    {
        try {
            $data = CRest::call(
                'crm.deal.update',
                [
                    'id' => $deal_id,
                    'fields' => [
                        'ENTITY_ID' => 'DEAL_STAGE',
                        'STAGE_ID' => $status,
                        'AUTHOR_ID' => '1', // ID пользователя, от которого оставляется комментарий
                    ],
                    'params' => [ 'REGISTER_SONET_EVENT' => 'Y' ],
                ]
            );

            if (isset($data['result'])){
                return $data['result'];
            }

            throw new \RuntimeException("Сделка с ID $deal_id не найден.");
        } catch (Exception $e) {
            // Обработка ошибок
            error_log("Ошибка при изменении статуса: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return "Статус не изменен, ошибка!.";
        }
    }

    /**
     * Изменение значения поля сделки
     * @param int $deal_id
     * @param array $field формат массива [fields=>values]
     * @return mixed|string
     */
    public function changeDialField(int $deal_id, array $field, ): mixed
    {
        try {
            $data = CRest::call(
                'crm.deal.update',
                [
                    'id' => $deal_id,
                    'fields' => $field,
                    'params' => [ 'REGISTER_SONET_EVENT' => 'Y' ],
                ]
            );

            if (isset($data['result'])){
                return $data['result'];
            }

            throw new \RuntimeException("Сделка с ID $deal_id не найден.");
        } catch (Exception $e) {
            // Обработка ошибок
            error_log("Ошибка при изменении статуса: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return "Статус не изменен, ошибка!.";
        }
    }

    /**
     * @param $deal_id
     * @param array $fileContent
     * @return array|mixed|string|string[]
     */
    public function timelineFileAdd($deal_id, array $fileContent)
    {
        return CRest::call(
            'crm.timeline.comment.add',
            [
                'fields' => [
                    'ENTITY_ID' => $deal_id,
                    'ENTITY_TYPE' => 'deal',
                    'AUTHOR_ID' => '1', // ID пользователя, от которого оставляется комментарий
                    'COMMENT' => 'Из телеграм по заявке:',
                    'FILES' => $fileContent,
                ]
            ]
        );
    }


    /**
     * Изменение значения поля сделки
     * @param int $deal_id
     * @param string $title
     * @return mixed|string
     */
    public function setTask (int $deal_id, string $title ): mixed
    {
        $deal_info = $this->getDealInfo($deal_id);
        $responsible_id = $deal_info['MODIFY_BY_ID'];

        try {
            $data = CRest::call(
                'tasks.task.add',
                [
                    'fields' => [
                        "TITLE" => $title,
                        "PRIORITY" => 2,
                        "CREATED_BY" => 1,
			            "RESPONSIBLE_ID" => $responsible_id,
                        "UF_CRM_TASK" => "D_" . $deal_id,

                    ]
                ]
            );

            if (isset($data['result'])){
                return $data['result'];
            }

            throw new \RuntimeException("Сделка с ID $deal_id не найден.");
        } catch (Exception $e) {
            // Обработка ошибок
            error_log("Ошибка при изменении статуса: " . $e->getMessage(), 3, '../logs/bitrix.log');
            // Вернуть сообщение об ошибке или что-то другое в зависимости от требований вашего приложения
            return "Статус не изменен, ошибка!.";
        }
    }
}