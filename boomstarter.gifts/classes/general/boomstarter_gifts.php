<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 21.01.14
 * Time: 12:57
 */

namespace classes\general;

CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");


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
            $product_id = $gift->product_id;

            $product = $this->getProduct($product_id);

            $price = $product['PURCHASING_PRICE'];
            $currency = $product['PURCHASING_CURRENCY'];
            $product_name = $product['NAME']; // ?

            $order_id = $this->createOrder($price, $currency, $gift->uuid);

            $this->addToBasket($product_id, $product_name, $price, $currency);

            CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID(), False);

            Add2BasketByProductID($PRODUCT_ID = $name_and_price[$i]["id"], $QUANTITY = 1, true);

            $gift->order($order_id);
        }
    }

    private function createOrder($price, $currency, $gift_uuid)
    {
        $order_id = CSaleOrder::Add(array(
                // "LID" => "ru", // LANG
                "PERSON_TYPE_ID" => 1,
                "PAYED" => "N",
                "CANCELED" => "N",
                "STATUS_ID" => "N",
                "PRICE" => $price,
                "CURRENCY" => $currency,
                // "USER_ID" => IntVal($USER->GetID()),
                // "PAY_SYSTEM_ID" => 3,
                "PRICE_DELIVERY" => 0,
                // "DELIVERY_ID" => 2,
                "DISCOUNT_VALUE" => 0,
                "TAX_VALUE" => 0.0,
                "USER_DESCRIPTION" => "Подарок через Boomstarter Gifts API",
                'BOOMSTARTER_GIFT_UUID' => $gift_uuid
            ));
        $order_id = IntVal($order_id)
    }

    private function getProduct($product_id)
    {
        $ar_res = CCatalogProduct::GetByID($product_id);
        return $ar_res;
    }

    private function addToBasket($product_id, $product_name, $price, $currency)
    {
        $site = ''; // ? LID - сайт, на котором сделана покупка (обязательное поле);

        $arFields = array(
            "PRODUCT_ID" => $product_id,
            "PRODUCT_PRICE_ID" => 0,
            "PRICE" => $price,
            "CURRENCY" => $currency,
            "QUANTITY" => 1,
            "LID" => $site,
            "DELAY" => "Y",
            "CAN_BUY" => "Y",
            "NAME" => $product_name,
            "MODULE" => $this->MODULE_ID,
            "NOTES" => "Подарок через Boomstarter Gifts API",
            // "DETAIL_PAGE_URL" => "/".LANG."/detail.php?ID=".$product_id
        );

        CSaleBasket::Add($arFields);
    }
}
