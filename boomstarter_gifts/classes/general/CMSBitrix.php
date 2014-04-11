<?php

namespace Boomstarter\Gifts;

require_once('CMS.php');

if (!\CModule::IncludeModule('sale')) {
    die('{"error":"Module \"sale\" not installed"}');
}

if (!\CModule::IncludeModule('catalog')) {
    die('{"error":"Module \"catalog\" not installed"}');
}

if (!\CModule::IncludeModule('iblock')) {
    die('{"error":"Module \"iblock\" not installed"}');
}

if (!\CModule::IncludeModule('boomstarter.gifts')) {
    die('{"error":"Module \"boomstarter.gifts\" not installed"}');
}

use \COption;
use \CCatalogProduct;
use \CSaleBasket;
use \CSaleOrder;
use \CSaleUser;
use \CCurrency;
use \CUser;
use \CAdminSorting;
use \CAdminList;
use \CIBlockElement;
use \CFile;
use \CSaleOrderUserProps;
use \boomstarter_gifts;


class CMSBitrix extends CMS
{
    public function getOption($key)
    {
        $module = new boomstarter_gifts();
        return COption::GetOptionString($module->MODULE_ID, $key, 0);
    }

    public function getProduct($product_id)
    {
        $product_id = INTVAL($product_id);
        $product = CCatalogProduct::GetByIDEx($product_id);
        return $product;
    }

    public function getProductPrice($product)
    {
        return (float)\CPrice::GetBasePrice($product['ID'])['PRICE'];
        //return $product['PROPERTIES']['PRICE']['VALUE'];
    }

    public function getProductCurrency($product)
    {
        if (class_exists('CCurrency')) {
            $currency = CCurrency::GetBaseCurrency();
        } else {
            $currency = 'RUB';
        }

        return $currency;
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
                "LID" => $this->getSiteId(),
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
        $module = new boomstarter_gifts();

        $result = CSaleBasket::Add(array(
            "PRODUCT_ID" => $product_id,
            "PRICE" => $price,
            "CURRENCY" => $currency,
            "QUANTITY" => 1,
            "LID" => $this->getSiteId(),
            "DELAY" => "N",
            "CAN_BUY" => "Y",
            "NAME" => $product_name,
            "MODULE" => $module->MODULE_ID,
            "NOTES" => "Подарок через Boomstarter Gifts API",
            "IGNORE_CALLBACK_FUNC" => "Y",
        ));

        return $result;
    }

    public function getBoomstarterUser()
    {
        $boomstarter_login = $this->getOption(boomstarter_gifts::OPTION_GIFTS_USER_NAME);
        $boomstarter_email = $this->getOption(boomstarter_gifts::OPTION_GIFTS_USER_EMAIL);

        $dbResult = CUser::GetByLogin($boomstarter_login);
        $user = $dbResult->Fetch();

        // Пользователь
        // Если нет - создать
        if (!$user) {
            $user = $this->createUser($boomstarter_login, $boomstarter_email);
        }

        // Покупатель
        $user_id = $user['ID'];

        // авторизация
        $result = CUser::Authorize($user_id);

        return $user;

        // получаем FUSER_ID, если покупатель для данного пользователя существует
        $FUSER_ID = CSaleUser::GetList(array('USER_ID' => $user_id));

        //если покупателя нет - создаем его
        if (!$FUSER_ID['ID']) {
            $FUSER_ID['ID'] = CSaleUser::_Add(array("USER_ID" => $user_id)); //обратите внимание на нижнее подчеркивание перед Add
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
        $result = CSaleBasket::OrderBasket($order_id, 0, $this->getSiteId());
    }

    public function getShopUuid()
    {
        return $this->getOption(boomstarter_gifts::OPTION_SHOP_UUID);
    }

    public function getShopToken()
    {
        return $this->getOption(boomstarter_gifts::OPTION_SHOP_TOKEN);
    }

    public function getShopOpenKey()
    {
        return $this->getOption(boomstarter_gifts::OPTIONS_SHOP_OPEN_KEY);
    }

    public function getButton($product_id, $button_class)
    {
        global $APPLICATION;

        $shop_uuid = $this->getShopUuid();
        $shop_open_key = $this->getShopOpenKey();

        $shop_uuid = $this->getShopUuid();

        $APPLICATION->AddHeadScript('http://boomstarter.ru/assets/gifts/api/v1.js');
        // $arResult['ID']
        $html = "
            <span class='{$button_class}'>
                <a href=''#' product-id='{$product_id}' boomstarter-shop-uuid='{$shop_uuid}' boomstarter-shop-key='{$shop_open_key}'>
                    Хочу в подарок
                </a>
            </span>";

        if (LANG_CHARSET != 'UTF-8') {
            $html = iconv('UTF-8', LANG_CHARSET, $html);
        }

        return $html;
    }

    public function isAdminPage()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (stristr(substr($path, 0, strlen('/bitrix/admin/')), '/bitrix/admin/')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Купить продукт
     *
     * @param $product_id
     * @param $gift \Boomstarter\Gift
     * @return mixed
     */
    public function buyProduct($product_id, $gift=NULL)
    {
        // продукт
        $product = $this->getProduct($product_id);

        $price = $this->getProductPrice($product);
        $currency = $this->getProductCurrency($product);
        $product_name = $this->getProductName($product);

        // Пользователь
        $user = $this->getCurrentUser();
        $this->getBoomstarterUser();

        // Наполнить корзину
        $this->clearBasket();
        $res = $this->addToBasket($product_id, $product_name, $price, $currency);

        // Создать заказ
        $order_id = $this->createOrder(
            $price,
            $currency,
            $gift ? : $gift->uuid,
            $gift ? "Клиент: {$gift->owner->first_name} {$gift->owner->last_name}, тел. {$gift->owner->phone}" : '---' );

        // Выполнить покупку
        $this->orderBasket($order_id);

        // Восстановить админа
        $this->authorizeUser($user);

        return $order_id;
    }

    public function getCurrentUser()
    {
        global $USER;
        return $USER->GetID();
    }

    public function authorizeUser($user)
    {
        CUser::Authorize($user);
    }

    private function getSiteId()
    {
        return $this->isAdminPage() ? 's1' : SITE_ID;
    }

}
