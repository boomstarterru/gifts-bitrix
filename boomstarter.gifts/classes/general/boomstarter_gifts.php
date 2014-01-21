<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 21.01.14
 * Time: 12:57
 */

namespace classes\general;

// CModule::IncludeModule("sale");


class boomstarter_gifts {
    static $MODULE_ID="boomstarter.gifts";
    static $SHOP_UUID_OPTION="shop_uuid";
    static $SHOP_TOKEN_OPTION="shop_token";

    /**
     * Хэндлер, отслеживающий изменения в инфоблоках
     * @param $arFields
     * @return bool
     */
    static function onBeforeElementUpdateHandler($arFields){
        // чтение параметров модуля
        // $iblock_id = COption::GetOptionString(self::$MODULE_ID, "iblock_id");

        // Активная деятельность

        // Результат
        return true;
    }

    public function init()
    {
        // api load
        $shop_uuid = COption::GetOptionString($this->MODULE_ID, $this->SHOP_UUID_OPTION, 0);
        $shop_token = COption::GetOptionString($this->MODULE_ID, $this->SHOP_TOKEN_OPTION, 0);
        $api = new \Boomstarter\API($shop_uuid, $shop_token);

        $gifts = $api->getGiftsPending();

        foreach($gifts as $gift) {
            // create order
            $order_id = CSaleOrder::Add(array(
                    // "LID" => "ru",
                    "PERSON_TYPE_ID" => 1,
                    "PAYED" => "N",
                    "CANCELED" => "N",
                    "STATUS_ID" => "N",
                    // "PRICE" => 279.32,
                    // "CURRENCY" => "USD",
                    // "USER_ID" => IntVal($USER->GetID()),
                    // "PAY_SYSTEM_ID" => 3,
                    // "PRICE_DELIVERY" => 11.37,
                    // "DELIVERY_ID" => 2,
                    // "DISCOUNT_VALUE" => 1.5,
                    // "TAX_VALUE" => 0.0,
                    "USER_DESCRIPTION" => "Подарок через Boomstarter Gifts API",
                    'BOOMSTARTER_GIFT_UUID' => $gift->uuid
                ));
            $order_id = IntVal($order_id)

            // send order_id
            $gift->order($order_id);
        }
    }
}
