<?php

namespace Boomstarter\Gifts;

require_once('CMS.php');

if (is_dir('/home/vital/src')) {
    define('DEBUG', TRUE);
}

if (!\CModule::IncludeModule('sale')) {
    die('{"error":"Module \"sale\" not installed"}');
}

if (!\CModule::IncludeModule('catalog')) {
    die('{"error":"Module \"catalog\" not installed"}');
}

if (!\CModule::IncludeModule('iblock')) {
    die('{"error":"Module \"iblock\" not installed"}');
}

use \COption;
use \CCatalogProduct;
use \CSaleBasket;
use \CSaleOrder;
use \CUser;
use \CAdminSorting;
use \CAdminList;
use \CIBlockElement;
use \CFile;
use \CSaleOrderUserProps;


class CMSBitrix extends CMS
{
    protected $MODULE_ID="boomstarter_gifts";

    public function getOption($key)
    {
        return COption::GetOptionString($this->MODULE_ID, $key, 0);
    }

    public function getProduct($product_id)
    {
        $product_id = INTVAL($product_id);
        $product = CCatalogProduct::GetByIDEx($product_id);
        return $product;
    }

    public function getProductPrice($product)
    {
        // return \CPrice::GetBasePrice($product['ID']);
        return $product['PROPERTIES']['PRICE']['VALUE'];
    }

    public function getProductCurrency($product)
    {
        return 'RUB'; // FIXME repalce it stub
        return $product['PROPERTIES']['PRICECURRENCY']['VALUE'];
    }

    public function getProductName($product)
    {
        return $product['NAME'];
    }

    public function clearBasket()
    {
        return CSaleBasket::DeleteAll();
    }

    public function createOrder($price, $currency, $gift_uuid, $description='')
    {
        global $USER;

        $order_id = CSaleOrder::Add(array(
                "LID" => SITE_ID,
                "PERSON_TYPE_ID" => 1,
                "PRICE" => $price,
                "CURRENCY" => $currency,
                "USER_ID" => $USER->GetID(),
                //"USER_DESCRIPTION" => $description,
                //"ADDITIONAL_INFO" => $description,
                "COMMENTS" => $description,
            ));

        $order_id = IntVal($order_id);

        return $order_id;
    }

    public function addToBasket($product_id, $product_name, $price, $currency)
    {
        $arFields = array(
            "PRODUCT_ID" => $product_id,
            "PRICE" => $price,
            "CURRENCY" => $currency,
            "QUANTITY" => 1,
            "LID" => SITE_ID,
            "DELAY" => "N",
            "CAN_BUY" => "Y",
            "NAME" => $product_name,
            "MODULE" => $this->MODULE_ID,
            "NOTES" => "Подарок через Boomstarter Gifts API",
            "IGNORE_CALLBACK_FUNC" => "Y",
        );

        $result = CSaleBasket::Add($arFields);
    }

    public function getBoomstarterUser()
    {
        $dbResult = CUser::GetByLogin($this->BOOMSTARTER_USER_LOGIN); // TODO login via options
        $user = $dbResult->Fetch();

        // Пользователь
        // Если нет - создать
        if (!$user) {
            $user = $this->createUser($this->BOOMSTARTER_USER_LOGIN, $this->BOOMSTARTER_USER_EMAIL);
        }

        // Покупатель
        $user_id = $user['ID'];

        // авторизация
        $result = CUser::Authorize($user_id);

        return $user;

        // получаем FUSER_ID, если покупатель для данного пользователя существует
        $FUSER_ID = \CSaleUser::GetList(array('USER_ID' => $user_id));

        //если покупателя нет - создаем его
        if (!$FUSER_ID['ID']) {
            $FUSER_ID['ID'] = \CSaleUser::_Add(array("USER_ID" => $user_id)); //обратите внимание на нижнее подчеркивание перед Add
        }

        //если не получается создать покупателя - то тут уж ничего не поделаешь
        if (!$FUSER_ID['ID']) {
            die("Error while creating SaleUser");
        }

        $sale_user_id = intval($FUSER_ID['ID']);
        // теперь переменную $FUSER_ID можно использовать для добавления товаров в корзину пользователя с $userId.

        return $sale_user_id;
    }

    public function createUser($login, $email)
    {
        $password = randString(8); // Генерируем пароль из 8 символов. Его потом надо будет на емыл ему отправить

        $user = new CUser();

        $user_id = $user->Add(array(
                'LOGIN' => $login,
                'NAME' => $login,
                'LAST_NAME' => $login,
                'EMAIL' => $email,
                'PASSWORD' => $password, // Нах мне два пароля писать - непонятно
                'CONFIRM_PASSWORD' => $password,
                'GROUP_ID'=>COption::GetOptionInt('main', 'new_user_registration_def_group'), // Назначем группу по умолчанию
                'ACTIVE' => "Y",
                // 'PERSON_TYPE_ID' => 1,
                'ADMIN_NOTES'=>"Зарегистрирован автоматически при оформлении заказа",
            ));

        $ar_user = $user->GetById($user_id)->Fetch();

        return $ar_user;
    }

    public function orderBasket($order_id)
    {
        CSaleBasket::OrderBasket($order_id);
    }
}
