<?php

namespace App\bot\commands;

use Telegram\Bot\Commands\Command;
use App\controllers\BitrixController;;

class GetStatus extends Command
{
    protected string $name = 'status';
    protected string $pattern = '{dialId}';
    protected string $description = 'Команда для получения статуса сделки в Bitrix по id';

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $dialId = $this->argument('dialId');

        if(!$dialId){
            $this->replyWithMessage([
                'text' => 'Пожалуйста укажите номер сделки. Пример: /status 12345'
            ]);

            return;
        }

        if(mb_strlen($dialId, 'UTF-8') < 6){
            $bitrixHelpers = new BitrixController();
            $dialInfo = $bitrixHelpers->getDealInfo($dialId);
            $statusDial = $bitrixHelpers->getNameStatus($dialInfo['STAGE_ID']);
        } else {
            $statusDial = mb_strlen($dialId);
        }


        $this->replyWithMessage([
            'text' => 'Текущий статус: ' . $statusDial,
        ]);
    }
}