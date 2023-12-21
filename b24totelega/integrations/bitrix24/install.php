<?php
require_once ('crest.php');

$result = CRest::installApp();
if($result['rest_only'] === false):?>
	<head>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<?php if($result['install'] == true):?>

			<script>
				BX24.init(function(){
                    var robotFields = {
                            "NAME": "Заявки в Телеграм",
                            "CODE": "my_act_in_telega",
                            "DESCRIPTION": "Это локальный виджет который отправляет оповещения в телегам by Miniap6y3",
                            "HANDLER": "https://tideways.eco-bur.ru/b24totelega/integrations/bitrix24/handler.php",
                            "AUTH_USER_ID": "1",
                            // 'USE_PLACEMENT': 'Y',
                            // 'PLACEMENT_HANDLER': 'https://tideways.eco-bur.ru/b24totelega/handler.php',
                            "PROPERTIES": {
                                "dial_id": {
                                    "Name": "ID cделки",
                                    "Description": "ID сделки не изменять",
                                    "Type": "int",
                                    "Required": "Y",
                                    "Default": "{{ID}}"
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
                            "bizproc.robot.add",
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
					BX24.installFinish();
				});
			</script><title></title>
        <?php endif;?>
	</head>
	<body>
		<?php if($result['install'] == true):?>
			Приложение успешно установлено
		<?php else:?>
			installation error
		<?php endif;?>
	</body>
<?php endif;