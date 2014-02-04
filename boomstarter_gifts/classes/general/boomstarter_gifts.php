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

use Boomstarter\Exception;
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
            array("id"=>"STATUS", "content"=>"Статус", "sort"=>"delivery_state", "default"=>true),
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
            $row->AddField("STATUS", $gift->delivery_state);
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
            $api = $this->getApi();
            $api->setGiftStateDelivery($uuid);
            ShowMessage("Статус обновлен.");
            $this->actionList();
        } catch (\Boomstarter\Exception $ex) {
            $e = $APPLICATION->GetException();
            $this->lAdmin->AddUpdateError(($e ? $e->getString() : "Gift delivery error"), $uuid);
            $this->lAdmin->DisplayList();
        }
    }

    public function actionCron()
    {
        $api = $this->getApi();
        $gifts = $api->getGiftsPending();
        $log_messages = array();

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
            $this->getBoomstarterUser();

            // Наполнить корзину
            $this->clearBasket();
            $this->addToBasket($gift->product_id, $product_name, $price, $currency);

            // Создать заказ
            $order_id = $this->createOrder(
                $price,
                $currency,
                $gift->uuid,
                "Клиент: {$gift->owner->first_name} {$gift->owner->last_name}, тел. {$gift->owner->phone}");

            // Выполнить покупку
            $this->orderBasket($order_id);

            // Отправить код заказа
            try {
                $gift->order($order_id);
                $success = TRUE;
            } catch (Exception $e) {
                $success = FALSE;
            }

            // log
            $log_messages[] = array(
                    'date' => date('Y-m-d H:i:s'),
                    'action' => "order",
                    'uuid' => $gift->uuid,
                    'order_id' => $order_id,
                    'product_id' => $gift->product_id,
                    'product_name' => $gift->name,
                    'success' => $success,
                );
        }

        // show log
        echo json_encode($log_messages);
    }

    private function getProduct($product_id)
    {
        $product_id = INTVAL($product_id);
        $product = CCatalogProduct::GetByIDEx($product_id);
        return $product;
    }

    private function getProductPrice($product)
    {
        // return \CPrice::GetBasePrice($product['ID']);
        return $product['PROPERTIES']['PRICE']['VALUE'];
    }

    private function getProductCurrency($product)
    {
        return 'RUB'; // FIXME repalce it stub
        return $product['PROPERTIES']['PRICECURRENCY']['VALUE'];
    }

    private function getProductName($product)
    {
        return $product['NAME'];
    }

    private function clearBasket()
    {
        return CSaleBasket::DeleteAll();
    }

    private function createOrder($price, $currency, $gift_uuid, $description='')
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

    private function addToBasket($product_id, $product_name, $price, $currency)
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

    private function getBoomstarterUser()
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
                'ADMIN_NOTES'=>"Зарегистрирован автоматически при оформлении заказа",
            ));

        $ar_user = $user->GetById($user_id)->Fetch();

        return $ar_user;
    }

    private function orderBasket($order_id)
    {
        CSaleBasket::OrderBasket($order_id);
    }

    protected function getCMS()
    {
        return CMSBitrix::getInstance();
    }
}
