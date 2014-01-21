<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 21.01.14
 * Time: 12:57
 */

namespace classes\general;


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
            $order = CreateOrder();
            $order->setProperty('boomstarter_gift_uuid', $gift->uuid);

            // send order_id
            $order_id = order->order_id;
            $gift->order($order_id);
        }
    }
}
