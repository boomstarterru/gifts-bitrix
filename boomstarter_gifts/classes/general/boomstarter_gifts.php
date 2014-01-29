<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 21.01.14
 * Time: 12:57
 */

namespace classes\general;

if (is_dir('/home/vital/src')) {
    define('DEBUG', TRUE);
}

use \CModule;
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

if (!CModule::IncludeModule('sale')) {
    die('{"error":"Module \"sale\" not installed"}');
}

if (!CModule::IncludeModule('catalog')) {
    die('{"error":"Module \"catalog\" not installed"}');
}

if (!CModule::IncludeModule('iblock')) {
    die('{"error":"Module \"iblock\" not installed"}');
}


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

        if (defined('DEBUG') && DEBUG) {
            $api->setApiUrl('http://localhost:8000/api/v1.1/partners');
        }

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
        $ar_res = GetIBlockElement($product_id);
        return $ar_res;
    }
}


class ControllerBitrix extends Controller
{
    var $lAdmin = NULL;
    private $BOOMSTARTER_USER_LOGIN="boomstarter"; // TODO via options
    private $BOOMSTARTER_USER_EMAIL="api@boomstarter.ru"; // TODO via options

    function __construct()
    {
        if ($this->inAdminPage()) {
            $sTableID = "tbl_gifts";
            $oSort = new CAdminSorting($sTableID, "id", "desc");
            $this->lAdmin = new CAdminList($sTableID, $oSort);
        }
    }

    private function inAdminPage()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (stristr(substr($path, 0, strlen('/bitrix/admin/')), '/bitrix/admin/')) {
            return TRUE;
        } else {
            return FALSE;
        }
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
        $arFilter = array("ID" => $gifts->getValues("product_id"));
        $data = CIBlockElement::GetList(array(), $arFilter, FALSE, FALSE, $arSelect);

        $products = array();

        while ($row = $data->GetNext()) {
            $products[$row["ID"]] = $row;
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

            // продукт
            $product = $this->getProduct($gift->product_id);

            $price = $this->getProductPrice($product);
            $currency = $this->getProductCurrency($product);
            $product_name = $this->getProductName($product);

            // Пользователь
            $user = $this->getBoomstarterUser();

            // Создать заказ
            $order_id = $this->createOrder($price, $currency, $gift->uuid, $user);

            // Наполнить корзину
            $this->clearBasket();
            $this->addToBasket($gift->product_id, $product_name, $price, $currency);

            // Выполнить покупку
            $this->orderBasket($order_id, $user);

            // Отправить код заказа
            $gift->order($order_id);
        }
    }

    private function getProduct($product_id)
    {
        $product_id = INTVAL($product_id);
        $product = CCatalogProduct::GetByIDEx($product_id);
        return $product;
    }

    private function getProductPrice($product)
    {
        return $product['PROPERTIES']['PRICE'];
    }

    private function getProductCurrency($product)
    {
        return 'RUB';
        return $product['PROPERTIES']['PRICECURRENCY']['VALUE'];
    }

    private function getProductName($product)
    {
        return $product['NAME'];
    }

    private function clearBasket()
    {
        return CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID(), False);
    }

    private function createOrder($price, $currency, $gift_uuid, $user)
    {
        $order_id = CSaleOrder::Add(array(
                "LID" => SITE_ID,
                "PERSON_TYPE_ID" => 1,
                "PRICE" => $price,
                "CURRENCY" => $currency,
                "USER_ID" => IntVal($user['ID']),
                "PAY_SYSTEM_ID" => $user["PAY_SYSTEM_ID"],
                // "DELIVERY_ID" => 2,
                "USER_DESCRIPTION" => "Подарок через Boomstarter Gifts API",
                //'BOOMSTARTER_GIFT_UUID' => $gift_uuid
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

    private function getBoomstarterUser()
    {
        $dbResult = CUser::GetByLogin($this->BOOMSTARTER_USER_LOGIN); // TODO login via options
        $user = $dbResult->Fetch();

        // Если нет - создать
        if (!$user) {
            $user = $this->createUser($this->BOOMSTARTER_USER_LOGIN, $this->BOOMSTARTER_USER_EMAIL);
        }

        return $user;
    }

    private function createUser($login, $email)
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
                'ADMIN_NOTES'=>"Зарегистрирован автоматически при оформлении заказа"
            ));

        /*
        $arFields = array(
            "NAME" => "Boomstarter",
            "USER_ID" => $user_id,
            "PERSON_TYPE_ID" => 2
        );
        $USER_PROPS_ID = CSaleOrderUserProps::Add($arFields);
        */

        $ar_user = $user->GetById($user_id)->Fetch();

        return $ar_user;
    }

    private function orderBasket($order_id, $user)
    {
        CSaleBasket::OrderBasket($order_id, $user["ID"]);
    }

    protected function getCMS()
    {
        return CMSBitrix::getInstance();
    }
}
