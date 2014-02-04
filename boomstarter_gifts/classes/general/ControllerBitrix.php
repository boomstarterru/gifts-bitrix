<?php

namespace Boomstarter\Gifts;

require_once('Controller.php');
require_once('CMSBitrix.php');

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

class ControllerBitrix extends Controller
{
    var $lAdmin = NULL;
    private $BOOMSTARTER_USER_LOGIN="boomstarter"; // TODO via options
    private $BOOMSTARTER_USER_EMAIL="api@boomstarter.ru"; // TODO via options

    function __construct()
    {
        if ($this->isAdminPage()) {
            $sTableID = "tbl_gifts";
            $oSort = new CAdminSorting($sTableID, "id", "desc");
            $this->lAdmin = new CAdminList($sTableID, $oSort);
        }
    }

    private function isAdminPage()
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

    /**
     * @return CMS
     */
    protected function getCMS()
    {
        return CMSBitrix::getInstance();
    }
}
