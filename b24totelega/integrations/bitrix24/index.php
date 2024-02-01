<?php
error_reporting(-1);
ini_set('display_errors', 1);
require_once ('crest.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Уведомления телеграм</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
          crossorigin="anonymous">
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>
    <div class="item">
        <h3 style="text-align: center;">"Заявки в телеграм"</h3>
        <button class="btn btn-primary" style="margin: 8px;" id="install"> Установить робота
        </button>
        <button class="btn btn-primary" style="margin: 8px;" id="uninstall">
            Удалить робота
        </button>
    </div>
	<div>
		<?php

        /*$result = CRest::call('user.current');
		$result = CRestCurrent::call('bizproc.robot.list');
        var_dump($result);*/

		?>
	</div>
    <script type="text/javascript">
            let fields =

        document.querySelector("#install").onclick = function installActivity() {

            let robotFields = {
                "NAME": "Заявки в Телеграм",
                "CODE": "my_act_in_telega",
                "DESCRIPTION": "Это локальный виджет который отправляет оповещения в телеграм by Miniap6y3",
                "HANDLER": "https://tideways.eco-bur.ru/b24totelega/integrations/bitrix24/handler.php",
                "AUTH_USER_ID": "1",
                "PROPERTIES": {
                    "dial_id": {
                        "Name": "ID",
                        'Type': "string",
                        'Default': "{{ID}}",
                        'Hidden': "Y",
                    },
                    "chat_id": {
                        "Name": "ID чата",
                        "Description": "ID чата для отправки сообщений",
                        "Type": "int",
                        "Required": "Y"
                    },
                    "messages": {
                        "Name": "Сообщениe",
                        "Description": "Текст сообщения для отправки",
                        "Type": "text",
                        "Required": "Y"
                    },
                    "update_messages": {
                        "Name": "Обновлять сообщение",
                        "Description": "Редактирование сообщения с помощью повторной отправки",
                        "Type": "select",
                        "Options": {
                            "true": "Да",
                            "false": "Нет"
                        },
                        "Required": "Y",
                        "Default ": "false",
                    },
                    "keyboard": {
                        "Name": "Клавиатура",
                        "Description": "Кнопка 'Взять в работу' off/on",
                        "Type": "select",
                        "Options": {
                            "true": "Да",
                            "false": "Нет"
                        },
                        "Required": "Y",
                        "Default ": "true",
                    },

                },
            };

            BX24.callMethod(
                'bizproc.robot.add',
                robotFields,
                function(result){
                    if(result.error()){
                        console.error(result.error());
                    }
                    else{
                        console.log(result.data());
                    }
                }
            );
        }

        document.querySelector("#uninstall").onclick = function uninstallActivity() {
            var params = {
                'CODE': 'my_act_in_telega'
            };

            BX24.callMethod(
                'bizproc.robot.delete',
                params,
                function (result) {
                    if (result.error())
                        alert('Error: ' + result.error());
                    else
                        alert("Успешно: " + result.data());
                }
            );
        }

    </script>
</body>
</html>