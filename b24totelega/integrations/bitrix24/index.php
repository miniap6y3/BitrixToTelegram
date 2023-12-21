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
        <h3 style="text-align: center;">"Заявки в телегу"</h3>
        <button class="btn btn-primary" style="margin-right: 8px;" onclick="installActivity();"><i
                    class="bi bi-download"></i> Установить робота
        </button>
        <button class="btn btn-primary" onclick="uninstallActivity('my_act_in_telega');"><i class="bi bi-x-square"></i>
            Удалить робота
        </button>
    </div>
	<div id="auth-data">OAuth 2.0 data from REQUEST:
		<pre><?php
//			print_r($_REQUEST);
			?>
		</pre>
	</div>
	<div>
		<?php

        /*$result = CRest::call('user.current');
		$result = CRestCurrent::call('bizproc.robot.list');
        var_dump($result);*/

		?>
	</div>
    <script type="text/javascript">
        function installActivity() {
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
                        'Hidden': true,
                    },
                    "chat_id": {
                        "Name": "ID чата",
                        "Description": "ID чата для отправки сообщений",
                        "Type": "int",
                        "Required": "Y"
                    },
                    "messages": {
                        "Name": "Сообщения",
                        "Description": "Список сообщений для отправки в чат",
                        "Type": "text",
                        "Required": "Y"
                    }
                }
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

        function uninstallActivity(my_act_in_telega) {
            let params = {
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