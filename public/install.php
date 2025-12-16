<?php
declare(strict_types=1);

use App\Support\CRest;
use App\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/bootstrap.php';

$result = CRest::installApp();

Logger::info('Установка приложения', [
    'request' => $_REQUEST,
    'result'  => $result,
]);

if ($result['rest_only'] === false):?>
    <head>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <?php if ($result['install']):?>
            <script>
                BX24.init(function() {
                    BX24.callMethod('user.current', {}, function(current_user) {
                        if(current_user.error()) {
                            alert("Error: " + current_user.error());
                        } else {
                            BX24.callMethod(
                                'bizproc.activity.add',
                                {
                                    'CODE': 'getReviewLink',
                                    'HANDLER': 'https://crm-reviews.ru/activities/getreviewlinks',
                                    'AUTH_USER_ID': current_user.data().ID,
                                    'USE_SUBSCRIPTION': 'Y',
                                    'NAME': {
                                        'ru': 'Ссылка на отзыв'
                                    },
                                    'DESCRIPTION': {
                                        'ru': 'Действие генерирует и возвращает ссылки на отзывы для всех контактов сделки'
                                    },
                                    'PROPERTIES': { },
                                    'RETURN_PROPERTIES': {
                                        'link': {
                                            'Name': {
                                                'ru': 'Ссылки на отзывы'
                                            },
                                            'Type': 'string',
                                            'Multiple': 'Y',
                                            'Default': null
                                        }
                                    },
                                    'DOCUMENT_TYPE': ['crm', 'CCrmDocumentDeal', 'DEAL'],
                                    'FILTER': {
                                        INCLUDE: [
                                            ['crm', 'CCrmDocumentDeal']
                                        ]
                                    }
                                },
                                function(result) {
                                    if(result.error()) {
                                        alert("Error: " + result.error());
                                    } else {
                                        BX24.installFinish();
                                    }
                                }
                            );
                        }
                    });
                });
            </script>
        <?php endif;?>
    </head>
    <body>
    <?php if ($result['install']):?>
        Приложение успешно установлено
    <?php else:?>
        Во время установки приложения возникла ошибка
    <?php endif;?>
    </body>
<?php endif;