<?php

// namespace classes\general;

define('DEBUG', TRUE);

//require_once(dirname(__FILE__)."/../include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/API.php");

/*
use \CModule;
use \COption;
use \CCatalogProduct;
use \CSaleBasket;
use \CSaleOrder;
use \CUser;
*/

CModule::IncludeModule("sale");
CModule::IncludeModule("catalog");


class CMS
{
    protected static $instance = NULL;

    public function getOption($key)
    {
        throw new Exception("Not Implemented");
        // return '';
    }

    public static function &getInstance()
    {
        $className = get_called_class();

        if (!static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}


abstract class Controller
{
    protected $SHOP_UUID_OPTION = "SHOP_UUID";
    protected $SHOP_TOKEN_OPTION = "SHOP_TOKEN";

    public function actionList()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSetStateDelivery()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSetOrderId()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSchedule()
    {
        throw new Exception("Not Implemented");
    }

    public function run()
    {
        $action = $this->getAction();
        $method = "action".$action;

        // find method
        if (!method_exists($this, $method)) {
            throw new Exception("Unsupported action: ".$action);
        }

        // check access
        if (!$this->checkAccess($action)) {
            $this->showAccessDenied();
            return false;
        }

        // run action
        $this->$method();
    }

    public function getApi()
    {
        // api load
        $shop_uuid = $this->getCMS()->getOption($this->SHOP_UUID_OPTION);
        $shop_token = $this->getCMS()->getOption($this->SHOP_TOKEN_OPTION);

        $api = new \Boomstarter\API($shop_uuid, $shop_token);

        return $api;
    }

    abstract protected function getAction();
    abstract protected function checkAccess($access);
    abstract protected function showAccessDenied();
    abstract protected function getCMS();
}


class CMSBitrix extends CMS
{
    protected $MODULE_ID="boomstarter_gifts";

    public function getOption($key)
    {
        return COption::GetOptionString($this->MODULE_ID, $key, 0);
    }

    private function getProduct($product_id)
    {
        $ar_res = CCatalogProduct::GetByID($product_id);
        return $ar_res;
    }
}


class ControllerBitrix extends Controller
{
    var $lAdmin = NULL;

    function __construct()
    {
        $sTableID = "tbl_gifts";
        $oSort = new CAdminSorting($sTableID, "id", "desc");
        $this->lAdmin = new CAdminList($sTableID, $oSort);
    }

    protected function getAction()
    {
        $action = "list";

        if (isset($_GET['action'])) {
            $action = $_GET['action'];
        }

        return $action;
    }

    protected function checkAccess($access)
    {
        global $USER;
        return $USER->CanDoOperation($access);
    }

    protected function showAccessDenied()
    {
        global $APPLICATION;
        $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
    }

    public function actionList()
    {
        global $APPLICATION;
        global $DOCUMENT_ROOT;

        $aHeaders = array(
            array("id"=>"IMAGE", "content"=>"Фото", "default"=>true),
            array("id"=>"NAME", "content"=>"Нименование", "sort"=>"name", "default"=>true),
            array("id"=>"PRICE", "content"=>"Цена", "sort"=>"price", "default"=>true),
            array("id"=>"PRODUCT_ID", "content"=>"PRODUCT_ID", "sort"=>"product_id", "default"=>true),
            array("id"=>"ORDER", "content"=>"Заказ", "sort"=>"ORDER", "default"=>true),
            array("id"=>"ACTION", "content"=>"", "default"=>true),
        );

        $this->lAdmin->AddHeaders($aHeaders);

        // get gifts
        $api = $this->getApi();
        $gifts = $api->getGiftsPending();

        // cache products
        $arSelect = array("ID", "NAME", "DETAIL_PAGE_URL", "PREVIEW_PICTURE");
        // FIXME remove this stub
        if (DEBUG) {
            $arFilter = array("ID" => array(6, 7));
        } else {
            $arFilter = array("ID" => $gifts->getValues("product_id"));
        }
        $data = CIBlockElement::GetList(array(), $arFilter, FALSE, FALSE, $arSelect);

        $products = array();

        while ($row = $data->GetNext()) {
            $products[$row["ID"]] = $row;
        }

        // FIXME remove this stub
        if (DEBUG) {
            foreach($gifts as $gift) {
                if (intval($gift->product_id) == 128298) {
                    $gift->product_id = 6;
                }
                if (intval($gift->product_id) == 35727) {
                    $gift->product_id = 7;
                }
            }
        }

        foreach($gifts as $gift)
        {
            $row =& $this->lAdmin->AddRow($gift->uuid, array(
                    'product_id' => $gift->product_id,
                    'name' => $gift->name,
                    'price' => $gift->pledged,
                    'image' => '',
                ));
            $row->AddViewField("IMAGE", '<img src="'.CFile::GetPath($products[$gift->product_id]["PREVIEW_PICTURE"]).'"/>');
            $row->AddViewField("NAME", '<a href="'.$products[$gift->product_id]["DETAIL_PAGE_URL"].'" target="_blank">'.$gift->name.'</a>');
            $row->AddViewField("PRICE", number_format($gift->pledged, 0, '.', ' '));
            $row->AddField("PRODUCT_ID", $gift->product_id);
            $row->AddField("ORDER", $gift->order_id);
            $row->AddViewField("ACTION", '<a href="'.$APPLICATION->GetCurPageParam('action=SetStateDelivery&uuid=stub'.$gift->uuid, array("id", "d")).'" class="adm-btn">Delivery</a>');
        }

        $this->lAdmin->CheckListMode();

        $APPLICATION->SetTitle("Список оплаченных подарков");

        require_once ($DOCUMENT_ROOT.BX_ROOT."/modules/main/include/prolog_admin_after.php");

        $this->lAdmin->DisplayList();
    }

    public function actionSetStateDelivery()
    {
        global $APPLICATION;

        $uuid = $_GET['uuid'];

        try {
            $api = \classes\general\boomstarter_gifts::getApi();
            $api->setGiftStateDelivery($uuid);
        } catch (\Boomstarter\Exception $ex) {
            $e = $APPLICATION->GetException();
            $this->lAdmin->AddUpdateError(($e ? $e->getString() : "Gift delivery error"), $uuid);
            $this->lAdmin->DisplayList();
        }

        // $this->lAdmin->ActionRedirect($APPLICATION->GetCurPage());
    }

    public function actionCron()
    {
        $api = $this->getApi();
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

    protected function getCMS()
    {
        return CMSBitrix::getInstance();
    }
}


$admin = new ControllerBitrix();
$admin->run();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
