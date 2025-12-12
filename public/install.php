<?php
declare(strict_types=1);

use App\CRest;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

$result = CRest::installApp();
if($result['rest_only'] === false):?>
    <head>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <?php if($result['install'] == true):?>
            <script>
                BX24.init(function() {
                    BX24.callMethod(
                        'bizproc.activity.add',
                        {
                            'CODE': 'getReviewLink',
                            'HANDLER': 'https://crm-reviews.ru/activities/getreviewlink',
                            'AUTH_USER_ID': 96,
                            'USE_SUBSCRIPTION': 'Y',
                            'NAME': {
                                'ru': 'Ссылка на отзыв'
                            },
                            'DESCRIPTION': {
                                'ru': 'Действие генерирует и возвращает ссылки на отзывы для всех контактов сделки'
                            },
                            'PROPERTIES': {
                                'deal_id': {
                                    'Name': {
                                        'ru': 'ID сделки',
                                    },
                                    'Description': {
                                        'ru': '',
                                    },
                                    'Type': 'string',
                                    'Required': 'Y',
                                    'Multiple': 'N',
                                    'Default': '{{ID}}'
                                }
                            },
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
                                alert("Success: " + result.data());
                                BX24.installFinish();
                            }
                        }
                    );
                });
            </script>
        <?php endif;?>
    </head>
    <body>
    <?php if($result['install'] == true):?>
        installation has been finished
    <?php else:?>
        installation error
    <?php endif;?>
    </body>
<?php endif;