<?php
//error_reporting(-1);
include_once 'crest.php';
include_once '../../vendor/autoload.php';


use App\config\Params;
use App\models\DBC;
use App\models\WatchModel;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Keyboard\Keyboard;


$config = new Params();
$db = $config->get('params-db');
$database = new DBC($db);
$watchdb = new WatchModel($database);

$bot = $config->get('params-bots');
$telegram = new BotsManager($bot);

$bot = $telegram->bot('bitrix_bot');
$bot->setWebhook(['url' => 'https://tideways.eco-bur.ru/b24totelega/bot/hook.php']);

//Получаем данные от робота
$chat_id = ($_REQUEST['properties']['chat_id']);
$message = ($_REQUEST['properties']['messages']);
$deal_id = ($_REQUEST['properties']['dial_id']);
$update_massage = ($_REQUEST['properties']['update_messages']);
$keyswitch = ($_REQUEST['properties']['keyboard']);

// Отправляем комментарий в timeline сделки
$comment = CRest::call(
    'crm.timeline.comment.add',
    [
        'fields' => [
            'ENTITY_ID' => $deal_id,
            'ENTITY_TYPE' => 'deal',
            'COMMENT' => "Сообщение в Telegram: \n" . $message,
            'AUTHOR_ID' => '1', // ID пользователя, от которого оставляется комментарий
        ]
    ]
);

// Создание кнопок.
$keyboard = new Keyboard(
    [
        'inline_keyboard' => [
            [
                ['text' => 'Взять в работу', 'callback_data' => 'inwork'],
            ],
        ]
    ]);

if ($keyswitch === 'true'){
    $reply_markup = ['reply_markup' => $keyboard];
} else {
    $reply_markup = [];
}


// Редактирование заявки.
$search_bid = $watchdb->searchRecords('dealId', $deal_id,['chat_id' => $chat_id]);
if( !empty($search_bid) && $update_massage === 'true'){
    $data_edit = [
        'chat_id' => $chat_id,
        'message_id' => $search_bid[0]['message_id'],
        'text' => $message,
    ];

    $bot->editMessageText(array_merge($data_edit,$reply_markup));

    $bot->sendMessage(
        [
            'chat_id' => $chat_id,
            'text' => 'Заявка отредактирована',
            'reply_to_message_id' => $search_bid[0]['message_id'],
        ]
    );

    $watchdb->updateRecordByField('dealId', $deal_id, 'message', $message, ['chat_id' => $chat_id]);
} else {
    // отправляем сообщение в Telegram
    $order = $bot->sendMessage(array_merge(
        [
            'chat_id' => $chat_id,
            'text' => $message,
        ],
        $reply_markup)

    );
    //получаем ответ
    $messageId = $order->messageId;

    // установим значения из заявки
    $paramsdb = [
        'chat_id' => $chat_id,
        'dealId' => $deal_id,
        'message_id' => $messageId,
        'message' => $message,
    ];

    //создание записи в бд
    $watchdb->create($paramsdb);
}



