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


class boomstarter_gifts
{
    static $MODULE_ID="boomstarter_gifts";
    static $SHOP_UUID_OPTION="SHOP_UUID";
    static $SHOP_TOKEN_OPTION="SHOP_TOKEN";

    public function process()
    {
        // api load
        $shop_uuid = $this->getOption($this->SHOP_UUID_OPTION);
        $shop_token = $this->getOption($this->SHOP_TOKEN_OPTION);

        $api = new \Boomstarter\API($shop_uuid, $shop_token);

        $gifts = $api->getGiftsPending();

        /* @var $gift \Boomstarter\Gift */
        foreach($gifts as $gift) {

            // попустить оформленные
            if ($gift->order_id) {
                continue;
            }

            $product = $this->getProduct($gift->product_id);

            $price = $this->getProductPrice($product);
            $currency = $this->getProductCurrency($product);
            $product_name = $this->getProductName($product);

            // Пользователь
            $user = $this->getUserByEmail($gift->owner->email);

            // Если нет - создать
            if (!$user) {
                $user_login = $gift->owner->email;

                $user = $this->createUser(
                    $user_login,
                    $gift->owner->first_name,
                    $gift->owner->last_name,
                    $gift->owner->email,
                    $gift->phone);
            }

            // Создать заказ
            $order_id = $this->createOrder($price, $currency, $gift->uuid);

            // Наполнить корзину
            $this->clearBasket();
            $this->addToBasket($gift->product_id, $product_name, $price, $currency);

            // Выполнить покупку

            // Отправить код заказа
            $gift->order($order_id);
        }
    }

    private function getOption($key)
    {
        return COption::GetOptionString($this->MODULE_ID, $key, 0);
    }

    private function getProduct($product_id)
    {
        $ar_res = CCatalogProduct::GetByID($product_id);
        return $ar_res;
    }

    private function getProductPrice($product)
    {
        return $product['PURCHASING_PRICE'];
    }

    private function getProductCurrency($product)
    {
        return $product['PURCHASING_CURRENCY'];
    }

    private function getProductName($product)
    {
        return $product['PRODUCT_NAME'];
    }

    private function clearBasket()
    {
        return CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID(), False);
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
        $order_id = IntVal($order_id);

        return $order_id;
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

    private function getUserByEmail($email)
    {
        $user = CUser::getByEmail($email);

        return $user;
    }

    private function createUser($login, $first_name, $last_name, $email)
    {
        $password = randString(8); // Генерируем пароль из 8 символов. Его потом надо будет на емыл ему отправить

        $USER = new CUser();

        $new_user_id = $USER->Add(array(
                'LOGIN' => $login,
                'NAME' => $first_name,
                'LAST_NAME' => $last_name,
                'EMAIL' => $email,
                'PASSWORD' => $password, // Нах мне два пароля писать - непонятно
                'CONFIRM_PASSWORD' => $password,
                'GROUP_ID'=>COption::GetOptionInt('main', 'new_user_registration_def_group'), // Назначем группу по умолчанию
                'ACTIVE' => "Y",
                'ADMIN_NOTES'=>"Зарегистрирован автоматически при оформлении заказа"
            ));

        if ($new_user_id > 0) {
            $USER->Authorize($new_user_id);
            $arResult['NEW_USER'] = array(
                'LOGIN' => $login,
                'EMAIL' => $email,
                'PASSWORD' => $password,
            );
        }
    }
}
