<?php

function deleteDirectory($dir): bool
{
    if (!is_dir($dir)) {
        // Если указанный путь не является директорией, ничего не делаем
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;

        if (is_dir($filePath)) {
            // Рекурсивно вызываем эту функцию для удаления вложенных директорий
            deleteDirectory($filePath);
        } else {
            // Удаляем файл
            unlink($filePath);
        }
    }

    // Удаляем саму директорию
    return rmdir($dir);
}

/**
 * @param $text
 * @return string
 */
function stripFirstLine($text): string
{
    return substr($text, strpos($text, "\n") + 1);
}

function messageError($bot, $chat_id){

    $inline_keyboard = [
        [
            ['text' => 'Написать администратору', 'url' => 'https://t.me/miniap6y3'],
        ],
    ];

    $text = "Возникла ошибка! Для связи с администратором нажмите на кнопку 'Написать администратору'.";

    return $bot->sendMessage([
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => json_encode(['inline_keyboard' => $inline_keyboard]),
    ]);
}

/**
 * @param int $from_id
 * @param array $users_data
 * @return bool
 */
function validationUser(int $from_id, array $users_data) : bool
{
    if( in_array($from_id, $users_data, true)){
        return true;
    }
    return false;
}
