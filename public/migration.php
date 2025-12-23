<?php
declare(strict_types=1);

use Bitrix24\SDK\Core\Exceptions\BaseException;
use Bitrix24\SDK\Core\Exceptions\InvalidArgumentException;
use Bitrix24\SDK\Core\Exceptions\TransportException;
use Bitrix24\SDK\Services\CRM\Common\Result\SystemFields\Types\Email;
use Bitrix24\SDK\Services\CRM\Common\Result\SystemFields\Types\InstantMessenger;
use Bitrix24\SDK\Services\CRM\Common\Result\SystemFields\Types\Phone;
use Bitrix24\SDK\Services\CRM\Common\Result\SystemFields\Types\Website;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class Migrator
{
    private const string CURRENCY_ID = 'RUB';
    private const int ENTITY_TYPE_ID_COMPANY = 4;
    private const int ENTITY_TYPE_ID_CONTACT = 3;
    private int $productIblockId;
    private array $sources = [
        'WEB'          => 'WEBFORM',
        'ADVERTISING'  => 'CALLBACK',
        'WEBFORM'      => 'RC_GENERATOR',
        'CALL'         => 'CALL',
        'RC_GENERATOR' => 'STORE',
        'STORE'        => 'BOOKING',
        'EMAIL'        => 'EMAIL',
        'CALLBACK'     => 'REPEAT_SALE',
        '2|VK'         => 'WEB',
        'UC_3210ZP'    => 'ADVERTISING',
        'UC_QHX5QT'    => 'PARTNER',
        'UC_9ZF319'    => 'RECOMMENDATION',
        'UC_N99RFE'    => 'TRADE_SHOW',
    ];
    private array $leadStages = [
        'NEW'       => 'NEW',
        'UC_I1FS88' => '2',
        'UC_PZYPHR' => 'PROCESSED',
        '1'         => 'IN_PROCESS',
        '3'         => '1',
        'CONVERTED' => 'CONVERTED',
        'JUNK'      => 'JUNK',
        '6'         => '3',
        '4'         => '4',
        '5'         => '5',
        '7'         => '6',
        '8'         => '7',
        'UC_59Z8QA' => '8',
        '10'        => '9',
        '9'         => '10',
    ];
    private array $dealCategories = [
        '1' => 0,
        '5' => 2,
    ];
    private array $dealStages = [
        'C1:NEW'                => 'NEW',
        'C1:PREPARATION'        => 'PREPARATION',
        'C1:PREPAYMENT_INVOICE' => 'PREPAYMENT_INVOICE',
        'C1:EXECUTING'          => 'EXECUTING',
        'C1:UC_56BD0G'          => 'FINAL_INVOICE',
        'C1:UC_F0FCRX'          => 'UC_HSKZNY',
        'C1:UC_JPLWUR'          => 'UC_7EL51O',
        'C1:UC_IUILZY'          => 'UC_SY1YTA',
        'C1:UC_T36BSI'          => 'UC_Z2O94M',
        'C1:UC_VN6B0P'          => 'UC_GB3R7N',
        'C1:WON'                => 'WON',
        'C1:LOSE'               => 'LOSE',
        'C1:UC_ZXTW3X'          => 'APOLOGY',

        'C5:NEW'                => 'C2:NEW',
        'C5:UC_ZQ1TDF'          => 'C2:PREPARATION',
        'C5:PREPARATION'        => 'C2:PREPAYMENT_INVOICE',
        'C5:PREPAYMENT_INVOICE' => 'C2:EXECUTING',
        'C5:EXECUTING'          => 'C2:FINAL_INVOICE',
        'C5:FINAL_INVOICE'      => 'C2:UC_4SJS9B',
        'C5:UC_BGVKMF'          => 'C2:UC_TM6503',
        'C5:WON'                => 'C2:WON',
        'C5:LOSE'               => 'C2:LOSE',
        'C5:APOLOGY'            => 'C2:APOLOGY',
        'C5:UC_1Q10H9'          => 'C2:UC_X2IEBQ',
    ];
    private array $userIds = [
        '845' => 1,
        '1261' => 12,
        '2769' => 14,
        '4824' => 16,
        '4280' => 18,
        '4099' => 20,
        '3445' => 22,
        '2158' => 24,
    ];
    private array $transportCompanies = [
        '59'   => 78,
        '60'   => 80,
        '61'   => 82,
        '62'   => 84,
        '63'   => 86,
        '64'   => 88,
        '65'   => 90,
        '66'   => 92,
        '1814' => 498,
    ];
    private array $cancellingReasons = [
        '67' => 342,
        '68' => 344,
        '69' => 346,
        '70' => 348,
        '71' => 350,
        '72' => 352,
        '73' => 354,
        '74' => 356,
        '75' => 358,
        '76' => 360,
        '77' => 362,
    ];
    private array $saleTypes = [
        '80' => 364,
        '81' => 366,
    ];
    private array $isNDS = [
        '221' => 368,
        '222' => 370,
    ];
    private array $paymentMethods = [
        '26' => 372,
        '27' => 374,
        '28' => 376,
        '29' => 378,
        '30' => 380,
    ];
    private array $shippingMethods = [
        '32' => 382,
        '34' => 384,
        '35' => 386,
        '36' => 388,
        '37' => 390,
        '38' => 392,
        '39' => 394,
    ];
    private array $shippingStatuses = [
        '54' => 396,
        '55' => 398,
        '56' => 400,
        '57' => 402,
        '58' => 404,
    ];
    private array $orderStatuses = [
        '40' => 412,
        '44' => 406,
        '46' => 416,
        '47' => 414,
        '48' => 408,
        '50' => 410,
        '53' => 418,
    ];
    private array $dealsFilter;
    private ServiceBuilder $b24From;
    private ServiceBuilder $b24To;

    /**
     * @throws InvalidArgumentException
     * @throws TransportException
     * @throws BaseException
     */
    public function __construct(
        string $whFrom,
        string $whTo,
        array  $dealsFilter = []
    ) {
        $this->b24From = ServiceBuilderFactory::createServiceBuilderFromWebhook($whFrom);
        $this->b24To = ServiceBuilderFactory::createServiceBuilderFromWebhook($whTo);
        $this->dealsFilter = $dealsFilter;

        $this->loadIblockIds();
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function migrate(): void
    {
        echo '<pre>';
        $count = $this->getDealsCount();
        echo 'Всего сделок: ' . $count . PHP_EOL;

        $index = 1;
        foreach ($this->b24From->getCRMScope()->deal()->list(
            ['ID'],
            $this->dealsFilter,
            ['*', 'UF_*'],
            0
        )->getDeals() as $deal) {
            echo PHP_EOL . PHP_EOL . $index++ . ') Переносим сделку ID ' . $deal->ID;

            $fields = [
                'TITLE'                => $deal->TITLE,
                'TYPE_ID'              => 'SALE',
                'STAGE_ID'             => $this->dealStages[$deal->STAGE_ID] ?? $this->dealStages[0],
                'CURRENCY_ID'          => self::CURRENCY_ID,
                'OPPORTUNITY'          => $deal->OPPORTUNITY,
                'TAX_VALUE'            => $deal->TAX_VALUE,
                'LEAD_ID'              => $deal->LEAD_ID ? $this->getLeadId($deal->LEAD_ID) : '',
                'COMPANY_ID'           => $deal->COMPANY_ID ? $this->getCompanyId($deal->COMPANY_ID) : '',
                'BEGINDATE'            => $deal->BEGINDATE,
                'CLOSEDATE'            => $deal->CLOSEDATE,
                'ASSIGNED_BY_ID'       => $this->userIds[$deal->ASSIGNED_BY_ID] ?? 1,
                'OPENED'               => $deal->OPENED,
                'CLOSED'               => $deal->CLOSED,
                'COMMENTS'             => $deal->COMMENTS,
                'CATEGORY_ID'          => $this->dealCategories[$deal->CATEGORY_ID] ?? 0,
                'SOURCE_ID'            => $this->sources[$deal->SOURCE_ID] ?? 'WEBFORM',
                'SOURCE_DESCRIPTION'   => $deal->SOURCE_DESCRIPTION,
                'UTM_SOURCE'           => $deal->UTM_SOURCE,
                'UTM_MEDIUM'           => $deal->UTM_MEDIUM,
                'UTM_CAMPAIGN'         => $deal->UTM_CAMPAIGN,
                'UTM_CONTENT'          => $deal->UTM_CONTENT,
                'UTM_TERM'             => $deal->UTM_TERM,
                'ORIGINATOR_ID'        => 'b24-portal.ru',
                'ORIGIN_ID'            => $deal->ID,
                'UF_CRM_6948EC6B4508B' => $deal->ID,
                'UF_CRM_TRK_AWAIT_DAT' => $deal->getUserfieldByFieldName('UF_CRM_TRK_AWAIT_DAT'),
                'UF_CRM_TRK_PVZ_ADDR'  => $deal->getUserfieldByFieldName('UF_CRM_TRK_PVZ_ADDR'),
                'UF_CRM_TRK_TRACKER'   => $deal->getUserfieldByFieldName('UF_CRM_TRK_TRACKER'),
                'UF_CRM_TRK_STATUS_B'  => $deal->getUserfieldByFieldName('UF_CRM_TRK_STATUS_B'),
                'UF_CRM_TRK_STATUS_E'  => $deal->getUserfieldByFieldName('UF_CRM_TRK_STATUS_E'),
                'UF_CRM_TRK_HISTORY'   => $deal->getUserfieldByFieldName('UF_CRM_TRK_HISTORY'),
                'UF_CRM_TRK_TRANSIT'   => $this->transportCompanies[$deal->getUserfieldByFieldName('UF_CRM_TRK_HISTORY')] ?? '',
                'UF_CRM_6948E31E37C55' => $deal->getUserfieldByFieldName('UF_CRM_UIS8F2F824737'),
                'UF_CRM_6948E31E4BF5F' => $this->cancellingReasons[$deal->getUserfieldByFieldName('UF_CRM_1667815923019')] ?? '',
                'UF_CRM_6948E31E6748B' => $this->saleTypes[$deal->getUserfieldByFieldName('UF_CRM_1711131849952')] ?? '',
                'UF_CRM_6948E31E77BDC' => $this->isNDS[$deal->getUserfieldByFieldName('UF_CRM_1716157643248')] ?? '',
                'UF_CRM_6948E31E8BB26' => $deal->getUserfieldByFieldName('UF_CRM_1736426797199'),
                'UF_CRM_6948E31EB2D4D' => $deal->getUserfieldByFieldName('UF_CRM_1749663183651'),
                'UF_CRM_6948E31EC6336' => $deal->getUserfieldByFieldName('UF_CRM_688F04F833203'),
                'UF_CRM_6948E31F1AA47' => $this->paymentMethods[$deal->getUserfieldByFieldName('UF_CRM_1615444230')] ?? '',
                'UF_CRM_6948E31F74FE9' => $deal->getUserfieldByFieldName('UF_CRM_1616840355'),
                'UF_CRM_6948E31F859A2' => $deal->getUserfieldByFieldName('UF_CRM_1619377793845'),
                'UF_CRM_6948E31F9E9D6' => $deal->getUserfieldByFieldName('UF_CRM_1615302002464'),
                'UF_CRM_6948E31FB3892' => $deal->getUserfieldByFieldName('UF_CRM_1615303137062'),
                'UF_CRM_6948E31FC67FB' => $this->shippingMethods[$deal->getUserfieldByFieldName('UF_CRM_1615444268')] ?? '',
                'UF_CRM_6948E31FDA8A6' => $deal->getUserfieldByFieldName('UF_CRM_1616331897'),
                'UF_CRM_6948E31FEB504' => $this->shippingStatuses[$deal->getUserfieldByFieldName('UF_CRM_1616761455')] ?? '',
                'UF_CRM_6948E3200D5EE' => $deal->getUserfieldByFieldName('UF_CRM_1615302232267'),
                'UF_CRM_6948E3201A318' => $deal->getUserfieldByFieldName('UF_CRM_1615303032511'),
                'UF_CRM_6948E32028C8D' => $deal->getUserfieldByFieldName('UF_CRM_1615303223096'),
                'UF_CRM_6948E3203ACA2' => $this->orderStatuses[$deal->getUserfieldByFieldName('UF_CRM_1616330958')] ?? '',
                'UF_CRM_6948E32052FFE' => $deal->getUserfieldByFieldName('UF_CRM_1616414163'),
                'UF_CRM_6948E32061B2D' => $deal->getUserfieldByFieldName('UF_CRM_1616870392'),
                'UF_CRM_6948E3206ED3E' => $deal->getUserfieldByFieldName('UF_CRM_1620590688'),
                'UF_CRM_6948E3207BB4E' => $deal->getUserfieldByFieldName('UF_CRM_1636274004254'),
                'UF_CRM_6948E3208980C' => $deal->getUserfieldByFieldName('UF_CRM_1639390200752'),
            ];


            $id = 0;
            foreach ($this->b24To->getCRMScope()->deal()->list(
                [],
                ['=ORIGIN_ID' => $deal->ID],
                ['ID']
            )->getDeals() as $item) {
                $id = $item->ID;
            }

            if ($id > 0) {
                $this->b24To->getCRMScope()->deal()->update($id, $fields);
                echo PHP_EOL . 'Обновлена сделка ' . $id;
            } else {
                $id = $this->b24To->getCRMScope()->deal()->add($fields)->getId();
                echo PHP_EOL . 'Добавлена сделка ' . $id;
            }


            if ($deal->CONTACT_ID) {
                $contactIds = [];
                foreach ($this->b24From->core->call(
                    'crm.deal.contact.items.get',
                    [
                        'id' => $deal->ID,
                    ]
                )->getResponseData()->getResult() as $contact) {
                    $contactIds[] = [
                        'CONTACT_ID' => $this->getContactId($contact['CONTACT_ID']),
                        'SORT'       => $contact['SORT'],
                        'IS_PRIMARY' => $contact['IS_PRIMARY'],
                    ];
                }

                $this->b24To->core->call('crm.deal.contact.items.set',
                    [
                        'id'    => $id,
                        'items' => $contactIds,
                    ]
                );
            }
        }
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function getDealsCount(): int
    {
        return $this->b24From->getCRMScope()->deal()->countByFilter($this->dealsFilter);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function getLeadId(int $oldId): int
    {
        if ($oldId === 0) return 0;

        $id = 0;
        foreach ($this->b24To->getCRMScope()->lead()->list(
            [],
            ['ORIGIN_ID' => $oldId],
            ['ID']
        )->getLeads() as $lead) {
            $id = $lead->ID;

            echo PHP_EOL . 'Добавлена связь с лидом ' . $id;
        }

        if ($id > 0) return $id;

        return $this->migrateLead($oldId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateLead(int $id): int
    {
        $lead = $this->b24From->getCRMScope()->lead()->get($id)->lead();

        $fields = [
            'TITLE'                => $lead->TITLE,
            'NAME'                 => $lead->NAME,
            'SECOND_NAME'          => $lead->SECOND_NAME,
            'LAST_NAME'            => $lead->LAST_NAME,
            'COMPANY_TITLE'        => $lead->COMPANY_TITLE,
            'IS_RETURN_CUSTOMER'   => $lead->IS_RETURN_CUSTOMER,
            'BIRTHDATE'            => $lead->BIRTHDATE,
            'SOURCE_ID'            => $this->sources[$lead->SOURCE_ID] ?? 'WEBFORM',
            'SOURCE_DESCRIPTION'   => $lead->SOURCE_DESCRIPTION,
            'STATUS_ID'            => $this->leadStages[$lead->STATUS_ID] ?? 'NEW',
            'POST'                 => $lead->POST,
            'COMMENTS'             => $lead->COMMENTS,
            'CURRENCY_ID'          => self::CURRENCY_ID,
            'OPPORTUNITY'          => (float) $lead->OPPORTUNITY,
            'HAS_PHONE'            => $lead->HAS_PHONE,
            'HAS_EMAIL'            => $lead->HAS_EMAIL,
            'HAS_IMOL'             => $lead->HAS_IMOL,
            'ASSIGNED_BY_ID'       => $this->userIds[$lead->ASSIGNED_BY_ID] ?? 1,
            'OPENED'               => $lead->OPENED,
            'MOVED_BY_ID'          => $this->userIds[$lead->MOVED_BY_ID] ?? 1,
            'MOVED_TIME'           => $lead->MOVED_TIME,
            'ADDRESS'              => $lead->ADDRESS,
            'ADDRESS_2'            => $lead->ADDRESS_2,
            'ADDRESS_CITY'         => $lead->ADDRESS_CITY,
            'ADDRESS_POSTAL_CODE'  => $lead->ADDRESS_POSTAL_CODE,
            'ADDRESS_REGION'       => $lead->ADDRESS_REGION,
            'ADDRESS_PROVINCE'     => $lead->ADDRESS_PROVINCE,
            'ADDRESS_COUNTRY'      => $lead->ADDRESS_COUNTRY,
            'ADDRESS_COUNTRY_CODE' => $lead->ADDRESS_COUNTRY_CODE,
            'ADDRESS_LOC_ADDR_ID'  => $lead->ADDRESS_LOC_ADDR_ID,
            'UTM_SOURCE'           => $lead->UTM_SOURCE,
            'UTM_MEDIUM'           => $lead->UTM_MEDIUM,
            'UTM_CAMPAIGN'         => $lead->UTM_CAMPAIGN,
            'UTM_CONTENT'          => $lead->UTM_CONTENT,
            'UTM_TERM'             => $lead->UTM_TERM,
            'PHONE'                => $lead->PHONE ? $this->preparePhone($lead->PHONE) : [],
            'EMAIL'                => $lead->EMAIL ? $this->prepareEmail($lead->EMAIL) : [],
            'WEB'                  => $lead->WEB ? $this->prepareWebsite($lead->WEB) : [],
            'IM'                   => $lead->IM ? $this->prepareMessenger($lead->IM) : [],
            'ORIGINATOR_ID'        => 'b24-portal.ru',
            'ORIGIN_ID'            => $lead->ID,
            'UF_CRM_1766386781'    => $lead->ID,
            'UF_CRM_1766377257'    => $lead->getUserfieldByFieldName('UF_CRM_1655880185947'),
            'UF_CRM_1766378585'    => $lead->getUserfieldByFieldName('UF_CRM_1620590688'),
        ];

        $newId = $this->b24To->getCRMScope()->lead()->add($fields)->getId();

        echo PHP_EOL . 'Создан лид ' . $newId;

        $this->migrateLeadProductRows($id, $newId);

        if ($lead->COMPANY_ID) {
            $companyId = $this->getCompanyId($lead->COMPANY_ID, $lead->ID);
            $this->b24To->getCRMScope()->lead()->update($newId, ['COMPANY_ID' => $companyId]);
        }

        if ($lead->CONTACT_ID) {
            $contactIds = [];
            foreach ($this->b24From->core->call(
                'crm.lead.contact.items.get',
                [
                    'id' => $lead->ID,
                ]
            )->getResponseData()->getResult() as $contact) {
                $contactIds[] = [
                    'CONTACT_ID' => $this->getContactId($contact['CONTACT_ID'], $lead->ID),
                    'SORT'       => $contact['SORT'],
                    'IS_PRIMARY' => $contact['IS_PRIMARY'],
                ];
            }

            $this->b24To->core->call('crm.lead.contact.items.set',
                [
                    'id'    => $newId,
                    'items' => $contactIds,
                ]
            );
        }

        return $newId;
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateLeadProductRows(int $idFrom, int $idTo): void
    {
        $rows = [];

        foreach ($this->b24From->core->call('crm.lead.productrows.get', [
                'id' => $idFrom,
        ])->getResponseData()->getResult() as $row) {
            $productId = $this->getProductId([
                'iblockId' => $this->productIblockId,
                'name'     => $row['PRODUCT_NAME'],
                'active'   => 'Y',
            ]);

            $rows[] = [
                'PRODUCT_ID' => $productId,
                'PRICE' => $row['PRICE'],
                'QUANTITY' => $row['QUANTITY'],
                'TAX_RATE' => $row['TAX_RATE'],
                'TAX_INCLUDED' => $row['TAX_INCLUDED'],
                'MEASURE_CODE' => $row['MEASURE_CODE'],
                'MEASURE_NAME' => $row['MEASURE_NAME'],
            ];
        }

        $this->b24To->core->call(
            'crm.lead.productrows.set', [
                'id' => $idTo,
                'rows' => $rows,
            ]
        );
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function getProductId(array $fields): int
    {
        $productId = 0;

        foreach ($this->b24To->getCatalogScope()->product()->list(
            ['id', 'iblockId'],
            [
                'name' => $fields['name'],
                'iblockId' => $fields['iblockId'],
            ],
            [],
            0
        )->getProducts() as $product) {
            $productId = $product->id;
        }

        if ($productId > 0) return $productId;

        $newProduct = $this->b24To->getCatalogScope()->product()->add($fields);

        return $newProduct->product()->id;
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function loadIblockIds(): void
    {
        foreach ($this->b24To->getCatalogScope()->catalog()->list(
            [],
            [
                'iblockTypeId' => 'CRM_PRODUCT_CATALOG',
                'productIblockId' => false,
            ],
            [],
            0
        )->getCatalogs() as $catalog) {
            $this->productIblockId = $catalog->iblockId;
        }
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function getCompanyId(int $oldId, int $leadId = 0): int
    {
        if ($oldId === 0) return 0;

        $id = 0;
        foreach ($this->b24To->getCRMScope()->company()->list(
            [],
            ['ORIGIN_ID' => $oldId],
            ['ID']
        )->getCompanies() as $item) {
            $id = $item->ID;

            echo PHP_EOL . 'Добавлена связь с компанией ' . $id;
        }

        if ($id > 0) return $id;

        return $this->migrateCompany($oldId, $leadId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateCompany(int $id, int $leadId): int
    {
        $company = $this->b24From->getCRMScope()->company()->get($id)->company();

        $fields = [
            'TITLE'                    => $company->TITLE,
            'LEAD_ID'                  => $leadId,
            'HAS_PHONE'                => $company->HAS_PHONE,
            'HAS_EMAIL'                => $company->HAS_EMAIL,
            'HAS_IMOL'                 => $company->HAS_IMOL,
            'ASSIGNED_BY_ID'           => $this->userIds[$company->ASSIGNED_BY_ID] ?? 1,
            'COMMENTS'                 => $company->COMMENTS,
            'ADDRESS'                  => $company->ADDRESS,
            'ADDRESS_2'                => $company->ADDRESS_2,
            'ADDRESS_CITY'             => $company->ADDRESS_CITY,
            'ADDRESS_POSTAL_CODE'      => $company->ADDRESS_POSTAL_CODE,
            'ADDRESS_REGION'           => $company->ADDRESS_REGION,
            'ADDRESS_PROVINCE'         => $company->ADDRESS_PROVINCE,
            'ADDRESS_COUNTRY'          => $company->ADDRESS_COUNTRY,
            'ADDRESS_COUNTRY_CODE'     => $company->ADDRESS_COUNTRY_CODE,
            'ADDRESS_LOC_ADDR_ID'      => $company->ADDRESS_LOC_ADDR_ID,
            'ADDRESS_LEGAL'            => $company->ADDRESS_LEGAL,
            'REG_ADDRESS'              => $company->REG_ADDRESS,
            'REG_ADDRESS_2'            => $company->REG_ADDRESS_2,
            'REG_ADDRESS_CITY'         => $company->REG_ADDRESS_CITY,
            'REG_ADDRESS_POSTAL_CODE'  => $company->REG_ADDRESS_POSTAL_CODE,
            'REG_ADDRESS_REGION'       => $company->REG_ADDRESS_REGION,
            'REG_ADDRESS_PROVINCE'     => $company->REG_ADDRESS_PROVINCE,
            'REG_ADDRESS_COUNTRY'      => $company->REG_ADDRESS_COUNTRY,
            'REG_ADDRESS_COUNTRY_CODE' => $company->REG_ADDRESS_COUNTRY_CODE,
            'REG_ADDRESS_LOC_ADDR_ID'  => $company->REG_ADDRESS_LOC_ADDR_ID,
            'UTM_SOURCE'               => $company->UTM_SOURCE,
            'UTM_MEDIUM'               => $company->UTM_MEDIUM,
            'UTM_CAMPAIGN'             => $company->UTM_CAMPAIGN,
            'UTM_CONTENT'              => $company->UTM_CONTENT,
            'UTM_TERM'                 => $company->UTM_TERM,
            'PHONE'                    => $company->PHONE ? $this->preparePhone($company->PHONE) : [],
            'EMAIL'                    => $company->EMAIL ? $this->prepareEmail($company->EMAIL) : [],
            'WEB'                      => $company->WEB ? $this->prepareWebsite($company->WEB) : [],
            'IM'                       => $company->IM ? $this->prepareMessenger($company->IM) : [],
            'ORIGINATOR_ID'            => 'b24-portal.ru',
            'ORIGIN_ID'                => $company->ID,
            'UF_CRM_1766365952'        => $company->ID,
            'UF_CRM_6948E31C0717C'     => $company->getUserfieldByFieldName('UF_CRM_UIS707361B1FA'),
            'UF_CRM_6948E31E07A5D'     => $company->getUserfieldByFieldName('UF_CRM_686B9BA3258FF'),
        ];

        $newId = $this->b24To->getCRMScope()->company()->add($fields)->getId();

        echo PHP_EOL . 'Создана компания ' . $newId;

        $this->migrateRequisite(self::ENTITY_TYPE_ID_COMPANY, $id, $newId);
        $this->migrateAddress(self::ENTITY_TYPE_ID_COMPANY, $id, $newId);

        return $newId;
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function getContactId(int $oldId, int $leadId = 0): int
    {
        if ($oldId === 0) return 0;

        $id = 0;
        foreach ($this->b24To->getCRMScope()->contact()->list(
            [],
            ['ORIGIN_ID' => $oldId],
            ['ID'],
            0
        )->getContacts() as $item) {
            $id = $item->ID;

            echo PHP_EOL . 'Добавлена связь с контактом ' . $id;
        }

        if ($id > 0) return $id;

        return $this->migrateContact($oldId, $leadId);
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateContact(int $id, int $leadId): int
    {
        $contact = $this->b24From->getCRMScope()->contact()->get($id)->contact();

        $fields = [
            'COMMENTS'            => $contact->COMMENTS,
            'NAME'                => $contact->NAME,
            'SECOND_NAME'         => $contact->SECOND_NAME,
            'LAST_NAME'           => $contact->LAST_NAME,
            'LEAD_ID'             => $leadId,
            'SOURCE_ID'           => $this->sources[$contact->SOURCE_ID] ?? 'WEBFORM',
            'SOURCE_DESCRIPTION'  => $contact->SOURCE_DESCRIPTION,
            'COMPANY_ID'          => $contact->COMPANY_ID ? $this->getCompanyId($contact->COMPANY_ID) : '',
            'BIRTHDATE'           => $contact->BIRTHDATE,
            'HAS_PHONE'           => $contact->HAS_PHONE,
            'HAS_EMAIL'           => $contact->HAS_EMAIL,
            'HAS_IMOL'            => $contact->HAS_IMOL,
            'ASSIGNED_BY_ID'      => $this->userIds[$contact->ASSIGNED_BY_ID] ?? 1,
            'ADDRESS'             => $contact->ADDRESS,
            'ADDRESS_2'           => $contact->ADDRESS_2,
            'ADDRESS_CITY'        => $contact->ADDRESS_CITY,
            'ADDRESS_POSTAL_CODE' => $contact->ADDRESS_POSTAL_CODE,
            'ADDRESS_REGION'      => $contact->ADDRESS_REGION,
            'ADDRESS_COUNTRY'     => $contact->ADDRESS_COUNTRY,
            'ADDRESS_LOC_ADDR_ID' => $contact->ADDRESS_LOC_ADDR_ID,
            'UTM_SOURCE'          => $contact->UTM_SOURCE,
            'UTM_MEDIUM'          => $contact->UTM_MEDIUM,
            'UTM_CAMPAIGN'        => $contact->UTM_CAMPAIGN,
            'UTM_CONTENT'         => $contact->UTM_CONTENT,
            'UTM_TERM'            => $contact->UTM_TERM,
            'PHONE'               => $contact->PHONE ? $this->preparePhone($contact->PHONE) : [],
            'EMAIL'               => $contact->EMAIL ? $this->prepareEmail($contact->EMAIL) : [],
            'WEB'                 => $contact->WEB ? $this->prepareWebsite($contact->WEB) : [],
            'IM'                  => $contact->IM ? $this->prepareMessenger($contact->IM) : [],
            'ORIGINATOR_ID'       => 'b24-portal.ru',
            'ORIGIN_ID'           => $contact->ID,
            'UF_CRM_1766365928'   => $contact->ID,
            'UF_CRM_1766365198'   => $contact->getUserfieldByFieldName('UF_CRM_1615484439'),
            'UF_CRM_1766365231'   => $contact->getUserfieldByFieldName('UF_CRM_1667943093'),
            'UF_CRM_1766365247'   => $contact->getUserfieldByFieldName('UF_CRM_1667943159'),
        ];

        $newId = $this->b24To->getCRMScope()->contact()->add($fields)->getId();

        echo PHP_EOL . 'Создан контакт ' . $newId;

        $this->migrateRequisite(self::ENTITY_TYPE_ID_CONTACT, $id, $newId);
        $this->migrateAddress(self::ENTITY_TYPE_ID_CONTACT, $id, $newId);

        return $newId;
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateRequisite(int $entityTypeId, int $idFrom, int $idTo): void
    {
        $result = $this->b24From->core->call('crm.requisite.link.get',
            [
                'entityTypeId' => $entityTypeId,
                'entityId'     => $idFrom,
            ]
        )->getResponseData()->getResult();

        if (isset($result['REQUISITE_ID'])) {
            $requisite = $this->b24From->getCRMScope()->requisite()->get($result['REQUISITE_ID'])->requisite();

            $fields = [
                'ACTIVE'                 => $requisite->ACTIVE,
                'RQ_NAME'                => $requisite->RQ_NAME,
                'RQ_FIRST_NAME'          => $requisite->RQ_FIRST_NAME,
                'RQ_LAST_NAME'           => $requisite->RQ_LAST_NAME,
                'RQ_SECOND_NAME'         => $requisite->RQ_SECOND_NAME,
                'RQ_COMPANY_ID'          => $requisite->RQ_COMPANY_ID,
                'RQ_COMPANY_NAME'        => $requisite->RQ_COMPANY_NAME,
                'RQ_COMPANY_FULL_NAME'   => $requisite->RQ_COMPANY_FULL_NAME,
                'RQ_COMPANY_REG_DATE'    => $requisite->RQ_COMPANY_REG_DATE,
                'RQ_DIRECTOR'            => $requisite->RQ_DIRECTOR,
                'RQ_ACCOUNTANT'          => $requisite->RQ_ACCOUNTANT,
                'RQ_CEO_NAME'            => $requisite->RQ_CEO_NAME,
                'RQ_CEO_WORK_POS'        => $requisite->RQ_CEO_WORK_POS,
                'RQ_CONTACT'             => $requisite->RQ_CONTACT,
                'RQ_EMAIL'               => $requisite->RQ_EMAIL,
                'RQ_PHONE'               => $requisite->RQ_PHONE,
                'RQ_FAX'                 => $requisite->RQ_FAX,
                'RQ_IDENT_TYPE'          => $requisite->RQ_IDENT_TYPE,
                'RQ_IDENT_DOC'           => $requisite->RQ_IDENT_DOC,
                'RQ_IDENT_DOC_SER'       => $requisite->RQ_IDENT_DOC_SER,
                'RQ_IDENT_DOC_NUM'       => $requisite->RQ_IDENT_DOC_NUM,
                'RQ_IDENT_DOC_PERS_NUM'  => $requisite->RQ_IDENT_DOC_PERS_NUM,
                'RQ_IDENT_DOC_DATE'      => $requisite->RQ_IDENT_DOC_DATE,
                'RQ_IDENT_DOC_ISSUED_BY' => $requisite->RQ_IDENT_DOC_ISSUED_BY,
                'RQ_IDENT_DOC_DEP_CODE'  => $requisite->RQ_IDENT_DOC_DEP_CODE,
                'RQ_INN'                 => $requisite->RQ_INN,
                'RQ_KPP'                 => $requisite->RQ_KPP,
                'RQ_USRLE'               => $requisite->RQ_USRLE,
                'RQ_IFNS'                => $requisite->RQ_IFNS,
                'RQ_OGRN'                => $requisite->RQ_OGRN,
                'RQ_OGRNIP'              => $requisite->RQ_OGRNIP,
                'RQ_OKPO'                => $requisite->RQ_OKPO,
                'RQ_OKTMO'               => $requisite->RQ_OKTMO,
                'RQ_OKVED'               => $requisite->RQ_OKVED,
                'RQ_EDRPOU'              => $requisite->RQ_EDRPOU,
                'RQ_DRFO'                => $requisite->RQ_DRFO,
                'RQ_KBE'                 => $requisite->RQ_KBE,
                'RQ_IIN'                 => $requisite->RQ_IIN,
                'RQ_BIN'                 => $requisite->RQ_BIN,
                'RQ_ST_CERT_SER'         => $requisite->RQ_ST_CERT_SER,
                'RQ_ST_CERT_NUM'         => $requisite->RQ_ST_CERT_NUM,
                'RQ_ST_CERT_DATE'        => $requisite->RQ_ST_CERT_DATE,
                'RQ_VAT_PAYER'           => $requisite->RQ_VAT_PAYER,
                'RQ_VAT_ID'              => $requisite->RQ_VAT_ID,
                'RQ_VAT_CERT_SER'        => $requisite->RQ_VAT_CERT_SER,
                'RQ_VAT_CERT_NUM'        => $requisite->RQ_VAT_CERT_NUM,
                'RQ_VAT_CERT_DATE'       => $requisite->RQ_VAT_CERT_DATE,
                'RQ_RESIDENCE_COUNTRY'   => $requisite->RQ_RESIDENCE_COUNTRY,
                'RQ_BASE_DOC'            => $requisite->RQ_BASE_DOC,
                'RQ_REGON'               => $requisite->RQ_REGON,
                'RQ_KRS'                 => $requisite->RQ_KRS,
                'RQ_PESEL'               => $requisite->RQ_PESEL,
                'RQ_LEGAL_FORM'          => $requisite->RQ_LEGAL_FORM,
                'RQ_SIRET'               => $requisite->RQ_SIRET,
                'RQ_SIREN'               => $requisite->RQ_SIREN,
                'RQ_CAPITAL'             => $requisite->RQ_CAPITAL,
                'RQ_RCS'                 => $requisite->RQ_RCS,
                'RQ_CNPJ'                => $requisite->RQ_CNPJ,
                'RQ_STATE_REG'           => $requisite->RQ_STATE_REG,
                'RQ_MNPL_REG'            => $requisite->RQ_MNPL_REG,
                'RQ_CPF'                 => $requisite->RQ_CPF,
            ];

            $requisiteId = $this->b24To->getCRMScope()->requisite()->add(
                $idTo,
                $entityTypeId,
                $requisite->PRESET_ID,
                $requisite->NAME,
                $fields
            )->getId();

            echo PHP_EOL . 'Добавлены реквизиты';

            if ($requisiteId > 0 && isset($result['BANK_DETAIL_ID'])) {
                $bRequisite = $this->b24From->getCRMScope()->requisiteBankdetail()->get($result['BANK_DETAIL_ID'])->bankdetail();

                $fields = [
                    'ENTITY_ID'         => $requisiteId,
                    'COUNTRY_ID'        => $bRequisite->COUNTRY_ID,
                    'NAME'              => $bRequisite->NAME,
                    'CODE'              => $bRequisite->CODE,
                    'XML_ID'            => $bRequisite->XML_ID,
                    'ACTIVE'            => $bRequisite->ACTIVE,
                    'SORT'              => $bRequisite->SORT,
                    'RQ_BANK_NAME'      => $bRequisite->RQ_BANK_NAME,
                    'RQ_BANK_CODE'      => $bRequisite->RQ_BANK_CODE,
                    'RQ_BANK_ADDR'      => $bRequisite->RQ_BANK_ADDR,
                    'RQ_BANK_ROUTE_NUM' => $bRequisite->RQ_BANK_ROUTE_NUM,
                    'RQ_BIK'            => $bRequisite->RQ_BIK,
                    'RQ_MFO'            => $bRequisite->RQ_MFO,
                    'RQ_ACC_NAME'       => $bRequisite->RQ_ACC_NAME,
                    'RQ_ACC_NUM'        => $bRequisite->RQ_ACC_NUM,
                    'RQ_ACC_TYPE'       => $bRequisite->RQ_ACC_TYPE,
                    'RQ_IIK'            => $bRequisite->RQ_IIK,
                    'RQ_ACC_CURRENCY'   => $bRequisite->RQ_ACC_CURRENCY,
                    'RQ_COR_ACC_NUM'    => $bRequisite->RQ_COR_ACC_NUM,
                    'RQ_IBAN'           => $bRequisite->RQ_IBAN,
                    'RQ_SWIFT'          => $bRequisite->RQ_SWIFT,
                    'RQ_BIC'            => $bRequisite->RQ_BIC,
                    'RQ_CODEB'          => $bRequisite->RQ_CODEB,
                    'RQ_CODEG'          => $bRequisite->RQ_CODEG,
                    'RQ_RIB'            => $bRequisite->RQ_RIB,
                    'RQ_AGENCY_NAME'    => $bRequisite->RQ_AGENCY_NAME,
                    'COMMENTS'          => $bRequisite->COMMENTS,
                ];

                $this->b24To->getCRMScope()->requisiteBankdetail()->add($fields);
            }
        }
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    private function migrateAddress(int $entityTypeId, int $idFrom, int $idTo): void
    {
        foreach ($this->b24From->getCRMScope()->address()->list(
            [],
            [
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_ID' => $idFrom,
            ],
            []
        )->getAddresses() as $address) {
            $fields = [
                'TYPE_ID'        => $address->TYPE_ID,
                'ENTITY_TYPE_ID' => $address->ENTITY_TYPE_ID,
                'ENTITY_ID'      => $idTo,
                'ADDRESS_1'      => $address->ADDRESS_1,
                'ADDRESS_2'      => $address->ADDRESS_2,
                'CITY'           => $address->CITY,
                'POSTAL_CODE'    => $address->POSTAL_CODE,
                'REGION'         => $address->REGION,
                'PROVINCE'       => $address->PROVINCE,
                'COUNTRY'        => $address->COUNTRY,
                'COUNTRY_CODE'   => $address->COUNTRY_CODE,
                'LOC_ADDR_ID'    => $address->LOC_ADDR_ID,
            ];

            $this->b24To->getCRMScope()->address()->add($fields);

            echo PHP_EOL . 'Добавлена адрес';
        }
    }

    /** @var Phone[] $phone */
    private function preparePhone(array $phone): array
    {
        $result = [];

        foreach ($phone as $item) {
            $result[] = [
                'VALUE'      => $item->VALUE,
                'VALUE_TYPE' => $item->VALUE_TYPE->value,
            ];
        }

        return $result;
    }

    /** @var Email[] $email */
    private function prepareEmail(array $email): array
    {
        $result = [];

        foreach ($email as $item) {
            $result[] = [
                'VALUE'      => $item->VALUE,
                'VALUE_TYPE' => $item->VALUE_TYPE->value,
            ];
        }

        return $result;
    }

    /** @var Website[] $website */
    private function prepareWebsite(array $website): array
    {
        $result = [];

        foreach ($website as $item) {
            $result[] = [
                'VALUE'      => $item->VALUE,
                'VALUE_TYPE' => $item->VALUE_TYPE->value,
            ];
        }

        return $result;
    }

    /** @var InstantMessenger[] $messenger */
    private function prepareMessenger(array $messenger): array
    {
        $result = [];

        foreach ($messenger as $item) {
            $result[] = [
                'VALUE'      => $item->VALUE,
                'VALUE_TYPE' => $item->VALUE_TYPE->value,
            ];
        }

        return $result;
    }
}


try {
    $migrator = new Migrator(
        $_ENV['MGR_WH_FROM'],
        $_ENV['MGR_WH_TO'],
        [
            'CATEGORY_ID' => [1, 5],
        ]
    );
    $migrator->migrate();
} catch (Throwable $e) {
    echo print_r([
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'message' => $e->getMessage()
    ], true);
}
