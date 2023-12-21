<?php
/*error_reporting(-1);
ini_set('display_errors', 1);*/

include_once '../integrations/bitrix24/crest.php';
include_once '../vendor/autoload.php';
include_once '../config/params-bitrix.php';
include_once 'func.php';


use App\config\Params;
use App\controllers\BitrixController;
use App\models\DBC;
use App\models\WatchModel;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Keyboard\Keyboard;

$config = new Params();
$db = $config->get('params-db');
$database = new DBC($db);
$watchdb = new WatchModel($database);
$bot_config = $config->get('params-bots');
$telegram = new BotsManager($bot_config);
$bot = $telegram->bot('bitrix_bot');

//Обработчик команд
$upd = $bot->commandsHandler(true);

$bitrix = new BitrixController();

$update = json_decode(file_get_contents('php://input'), true);
//file_put_contents('log.txt', print_r($update, 1), FILE_APPEND);
$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
$dir_save = __DIR__ . "/../files/";
if (isset($update['message']['reply_to_message'])){
    $message_id = $update['message']['reply_to_message']['message_id'];
    $order = $watchdb->searchRecords('reply_message_id', $message_id, ['chat_id' => $chat_id]);
    $deal_id = $order[0]['dealId'];
    $user_status = $order[0]['status'];
    $dir_save = __DIR__ . "/../files/$deal_id/";
}

if (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];

    // Получаем данные из callback запроса
    $message = $callback_query['message'];
    $chat_id = $message['chat']['id'];
    $message_id = $callback_query['message']['message_id'];
    $date = $message['edit_date'] ?? $message['date'];
    $edit_date = DateTime::createFromFormat('U', $date);
    $callback_data = $callback_query['data'];
    $order = $watchdb->searchRecords('message_id', $message_id, ['chat_id' => $chat_id,]);

    if (isset($message['reply_to_message'])){
        $order = $watchdb->searchRecords('reply_message_id', $message_id, ['chat_id' => $chat_id,]);
    }else{
        $order = $watchdb->searchRecords('message_id', $message_id, ['chat_id' => $chat_id,]);
    }
    $deal_id = $order[0]['dealId'];
    $dir_save = __DIR__ . "/../files/$deal_id/";

    /** С начала геолокация
    * потом голосовое и файла
    */
    switch ($callback_data) {
        case 'inwork':

           if ($chat_id === $bot_config['chats']['engineers']) { // инженер
                $callback = 'phoned_engineers';
            } else{ // монтажи
                $callback = 'phoned';
            }


            //создаем кнопки для отправки
            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Созвонился', 'callback_data' => $callback,],
                        ['text' => 'Отказ', 'callback_data' => 'fail',],
                    ]
                ]
            ]);

            $text_edit = "Заявка в работе c {$edit_date->format("d.m.Y")} \n" . $message['text'];
            $watchdb->updateRecordByField('dealId', $deal_id, 'message', $text_edit, ['chat_id' => $chat_id]);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text_edit,
                'reply_markup' => $keyboard,
                'reply_to_message_id' => $message_id,
            ];
            $bot->editMessageText($data_edit);

            $bot->answerCallbackQuery([
                    'callback_query_id' => $callback_query['id'],
                    'text' => 'Вы нажали кнопку']
            );

            // Обновляем статус пользователя в базе данных
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','in_work_engineers', ['chat_id' => $chat_id]);

            break;
        case 'phoned_engineers':

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Выезд завершен', 'callback_data' => 'success_ing'],
                        ['text' => 'Отказ', 'callback_data' => 'fail']
                    ]
                ]
            ]);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'reply_markup' => $keyboard,
            ];

            $bot->editMessageReplyMarkup($data_edit);

            $bot->answerCallbackQuery([
                    'callback_query_id' => $callback_query['id'],
                    'text' => 'Вы нажали кнопку'
            ]);

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'На когда запланирован выезд? ответьте на это сообщение',
                'reply_to_message_id' => $message_id,
            ]);

            $watchdb->updateRecordByField('message_id', $message_id, 'reply_message_id', $message->messageId, ['chat_id' => $chat_id]);

            // Обновляем статус пользователя в базе данных
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','phoned_engineers', ['chat_id' => $chat_id]);

            break;
        case 'fail':
            $text_edit = "ОТКАЗ {$edit_date->format("d-m-y")}\n" . stripFirstLine($message['text']);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text_edit,
            ];

            $bot->editMessageText($data_edit);


            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
                'text' => 'Заявка провалена'
            ]);

            $bitrix->setTask($deal_id, 'Отказался от выезда');
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','fail', ['chat_id' => $chat_id]);

            break;

        case 'success_ing':
            $text_edit = "ВЫПОЛНЕНО! {$edit_date->format("d.m.Y")}\n" . stripFirstLine($message['text']);

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Загрузить файлы и отправить голосовое!', 'callback_data' => 'upload_files'],

                    ],
                    [
                        ['text' => 'Отправить локацию', 'callback_data' => 'location'],
                        ['text' => 'Отправить сообщение', 'callback_data' => 'send_message']
                    ],
                ],
            ]);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text_edit,
                'reply_markup' => $keyboard,
            ];

            $bot->editMessageText($data_edit);

            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
                'text' => 'Заявка выполнена'
            ]);

            // Обновляем статус пользователя в базе данных
            $watchdb->updateRecordByField('dealId', $deal_id, 'status', 'upload', ['chat_id' => $chat_id]);

            break;
        case 'stop_download':
            // Обновляем статус пользователя в базе данных
           $watchdb->updateRecordByField('reply_message_id', $message_id, 'status','end_upload', ['chat_id' => $chat_id, 'dealId' => $deal_id,]);

            //Ищем файлы в директории и убираем лишнее значения
            $files_dir = scandir($dir_save);
            $file_list = array_diff($files_dir, array('..', '.'));

            // Подготавливаем данные для загрузки
            $file_data = [];
            foreach ($file_list as $filename) {
                $file_path = $dir_save . $filename;

                // Проверяем, является ли элемент файлом (а не директорией)
                if (is_file($file_path)) {
                    $file_base64 = base64_encode(file_get_contents($file_path));
                    $file_data[] = [
                        'name' => $filename,
                        'content' => $file_base64,
                    ];
                }
            }

            //Отправка файлов в битрикс24
            $test = $bitrix->timelineFileAdd($deal_id, $file_data);

            //удаляем папку с файлами.
            if(!deleteDirectory($dir_save)){
                messageError($bot, $chat_id);
            }else{
                $keyboard = new Keyboard([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Да', 'callback_data' => 'send_message'],
                            ['text' => 'Нет', 'callback_data' => 'final'],
                        ]
                    ]
                ]);

                $message = $bot->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'Файлы успешно загружены. Отправить сообщение?',
                    'reply_markup' => $keyboard,
                    'reply_to_message_id' => $message_id,
                ]);

                $bot->answerCallbackQuery([
                    'callback_query_id' => $callback_query['id'],
                    'text' => 'Загрузка завершена'
                ]);

                $watchdb->updateRecordByField('reply_message_id', $message_id, 'reply_message_id', $message->messageId, ['chat_id' => $chat_id]);

            }

            break;
        case 'send_message':
            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
                'text' => ''
            ]);

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Для отправки сообщения менеджеру, нажмите на этот текст и выберите "ответить", затем напишите сообщение как обычно',
                'reply_to_message_id' => $message_id,
            ]);

            // Обновляем статус пользователя в базе данных
            $watchdb->updateRecordByField('reply_message_id', $message_id, 'reply_message_id', $message->messageId, ['chat_id' => $chat_id]);

           $watchdb->updateRecordByField('dealId', $deal_id, 'status','send_message', ['chat_id' => $chat_id]);

            break;

        case 'upload_files':

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [['text' => 'Завершить отправку', 'callback_data' => "stop_download" ]],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]);

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Ответом на данное сообщение загрузите файлы с выезда или отправьте голосовое! По завершению нажмите "Завершить отправку" ',
                'reply_markup' => $keyboard,
                'reply_to_message_id' => $message_id,
            ]);

            // Обновляем статус пользователя в базе данных
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','upload', ['chat_id' => $chat_id]);
            $watchdb->updateRecordByField('message_id', $message_id, 'reply_message_id',$message->messageId, ['chat_id' => $chat_id]);

            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
            ]);
            break;

        case 'location':

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Отправьте свою геолокацию,нажав на сообщение далее ответить, затем нажмите на значок скрепки внизу, и выберите "Местоположение".',
            ]);

            $watchdb->updateRecordByField('dealId', $deal_id, 'status','location', ['chat_id' => $chat_id]);
            $watchdb->updateRecordByField('message_id', $message_id, 'reply_message_id',$message->messageId, ['chat_id' => $chat_id]);

            break;

        case 'phoned':

            if($chat_id === $bot_config['chats']['logist']){
                $key = ['text' => 'Поставлен в график', 'callback_data' => 'set_to_schedule'];
            }else{
                $key = ['text' => 'Закончен', 'callback_data' => 'success_montazh'];
            }

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        $key,
                        ['text' => 'Отказ', 'callback_data' => 'fail']
                    ]
                ]
            ]);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'reply_markup' => $keyboard,
            ];

            $bot->editMessageReplyMarkup($data_edit);

            $watchdb->updateRecordByField('dealId', $deal_id, 'status','phoned', ['chat_id' => $chat_id]);

            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
            ]);

            break;

        case 'success_montazh':
            $text_edit = "ВЫПОЛНЕНО! {$edit_date->format("d.m.Y")}\n" . stripFirstLine($message['text']);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text_edit,
                'reply_markup' => [],
            ];

            $bot->editMessageText($data_edit);

            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
                'text' => 'Заявка выполнена'
            ]);

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Нажмите на этот текст и выберите "ответить", затем напишите сообщение как обычно',
                'reply_to_message_id' => $message_id
            ]);

            $bitrix->setTask($deal_id,'Заявка выполнена!');
            $watchdb->updateRecordByField('message_id', $message_id, 'reply_message_id', $message->messageId, ['chat_id' => $chat_id]);

            $watchdb->updateRecordByField('dealId', $deal_id, 'status', 'montazh', ['chat_id' => $chat_id]);
            break;

        case 'set_to_schedule':
            $text_edit = "Поставлен в график! {$edit_date->format("d.m.Y")}\n" . stripFirstLine($message['text']);

            $keyboard = new Keyboard([
                'inline_keyboard' => [
                    [
                        ['text' => 'Выполнен', 'callback_data' => 'send_message'],
                        ['text' => 'Отказ', 'callback_data' => 'fail'],
                    ]
                ]
            ]);

            $data_edit = [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => $text_edit,
                'reply_markup' => $keyboard,
            ];

            $bot->editMessageText($data_edit);

            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
                'text' => 'Заявка выполнена'
            ]);

            $message = $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Нажмите на этот текст и выберите "ответить", затем напишите сообщение как обычно',
                'reply_to_message_id' => $message_id
            ]);

            $watchdb->updateRecordByField('message_id', $message_id, 'reply_message_id', $message->messageId, ['chat_id' => $chat_id]);

            $watchdb->updateRecordByField('dealId', $deal_id, 'status', 'logist', ['chat_id' => $chat_id]);
            break;

        case 'final':
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','final', ['chat_id' => $chat_id]);
            $bitrix->setTask($deal_id, 'Заявка выполнена');
            $bot->answerCallbackQuery([
                'callback_query_id' => $callback_query['id'],
            ]);
            break;
        default:
            // Обрабатываем неизвестный callback запрос
            messageError($bot, $chat_id);
            break;
    }
} else if (isset($update['message'], $deal_id)) { //добавить проверку по id пользователя кто пишет
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $message_text = $message['text'];

    if (empty($message_text)) {

        $type_file = null;

        foreach ($bot_config['file_types'] as $key => $value) {
            if (isset($message[$key])) {
                $type_file = $value;
                break;
            }
        }

        if ($type_file === null) {
            // Обработка случая, когда тип файла не был определен
            $bot->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Я еще не умею работать с данными файлами',
            ]);
            die();
        }

        if ($type_file === 'location') {
            $location_tag = sprintf("%.5f, %.5f", $message['location']['latitude'], $message['location']['longitude']);
            $bitrix->changeDialField($deal_id, ['UF_CRM_1666169118670' => $location_tag]);
            $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Геолокация передана']);
        } else {
            $file_id = $message[$type_file]['file_id'] ?? $message[$type_file][count($message[$type_file]) - 1]['file_id'];
            $file_name = $message[$type_file]['file_name'];

            if (!is_dir($dir_save) && !mkdir($dir_save, 0755, true) && !is_dir($dir_save)) {
                messageError($bot, $chat_id);
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir_save));
            }

            $file = $bot->downloadFile($file_id, $dir_save . $file_name);
        }

    } elseif ($user_status === 'phoned_engineers') {
        // Сохраняем сообщение в bitrix
        $bitrix->timelineCommentAdd($deal_id, "Выезд запланирован на: " . $message_text);
        $bitrix->setTask($deal_id,'Проверь сделку!');
        // Отправляем сообщение "Спасибо за отправку сообщения"
        $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Спасибо!']);
        $watchdb->updateRecordByField('dealId', $deal_id, 'status',"phoned_engineers_$deal_id", ['chat_id' => $chat_id]);
    } else {
            // Сохраняем сообщение в bitrix
            $bitrix->timelineCommentAdd($deal_id, "Сообщение по заявке: " . $message_text);

            // Отправляем сообщение "Спасибо за отправку сообщения"
            $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Отправлено менеджеру']);
            $watchdb->updateRecordByField('dealId', $deal_id, 'status','send_message', ['chat_id' => $chat_id]);
    }
}





