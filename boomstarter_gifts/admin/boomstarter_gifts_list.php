<?php

//require_once(dirname(__FILE__)."/../include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/API.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/boomstarter_gifts/classes/general/boomstarter_gifts.php");

CModule::IncludeModule("boomstarter_gifts");


class BoomstarterGiftsAdmin
{
    const ACTION_LIST = "list";
    const ACTION_ORDER = "order";
    const ACTION_DELIVERY = "delivery";

    var $lAdmin = NULL;

    public function run()
    {
        $action = $this->getAction();

        switch ($action) {
            case self::ACTION_LIST:
                $this->actionList();
                break;

            case self::ACTION_DELIVERY:
                if (!$this->checkAccess('delivery')) {
                    $this->showAccessDenied();
                    return false;
                }

                $this->actionDelivery();
                break;
        }
    }

    private function getAction()
    {
        $action = self::ACTION_LIST;

        $sTableID = "tbl_gifts";
        $oSort = new CAdminSorting($sTableID, "id", "desc");
        $this->lAdmin = new CAdminList($sTableID, $oSort);

        if ($this->lAdmin->EditAction()) {
            $action = self::ACTION_LIST;
        } else {
            $action = self::ACTION_LIST;
        }

        return $action;
    }

    private function checkAccess($access)
    {
        global $USER;

        return $USER->CanDoOperation($access);
    }

    private function showAccessDenied()
    {
        global $APPLICATION;

        $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
    }

    private function actionList()
    {
        global $APPLICATION;
        global $DOCUMENT_ROOT;

        $aHeaders = array(
            array("id"=>"PRODUCT_ID", "content"=>"PRODUCT_ID", "sort"=>"product_id", "default"=>true),
            array("id"=>"NAME", "content"=>"Нименование", "sort"=>"name", "default"=>true),
            array("id"=>"PRICE", "content"=>"Цена", "sort"=>"price", "default"=>true),
            array("id"=>"IMAGE", "content"=>"Фото", "default"=>true),
        );

        $this->lAdmin->AddHeaders($aHeaders);

        $api = \classes\general\boomstarter_gifts::getApi();

        //$gifts = $api->getGiftsPending();
        $gifts = array(
            new \Boomstarter\Gift(new \Boomstarter\Transport(), array(
                'name' => 'test 1',
                'pledged' => '100',
                'product_id' => '1',
                'delivery_state' => 'pending',
                'uuid' => 'z1',
            )),
            new \Boomstarter\Gift(new \Boomstarter\Transport(), array(
                'name' => 'test 2',
                'pledged' => '100',
                'product_id' => '2',
                'delivery_state' => 'pending',
                'uuid' => 'z1',
            ))
        );

        foreach($gifts as $gift)
        {
            $row =& $this->lAdmin->AddRow($gift->uuid, array(
                    'product_id' => $gift->product_id,
                    'name' => $gift->name,
                    'price' => $gift->pledged,
                    'image' => '',
                ));
            $row->AddField("PRODUCT_ID", $gift->product_id);
            $row->AddField("NAME", $gift->name);
            $row->AddField("PRICE", $gift->pledged);

            $arActions = Array(
                array(
                    "ICON"    => "edit",
                    "DEFAULT" => true,
                    "TEXT"    => "Отметить как 'отправлен'",
                    "ACTION"  => $this->lAdmin->ActionRedirect("boomstarter_gifts_delivery.php?ID=".$gift->uuid)
                ),
            );
            $row->AddActions($arActions);
        }

        $this->lAdmin->CheckListMode();

        $APPLICATION->SetTitle("Список оплаченных подарков");

        require_once ($DOCUMENT_ROOT.BX_ROOT."/modules/main/include/prolog_admin_after.php");

        $this->lAdmin->DisplayList();
    }

    private function actionDelivery()
    {
        global $FIELDS;
        global $APPLICATION;

        foreach($FIELDS as $ID=>$arFields)
        {
            $ID = IntVal($ID);
            if($ID <= 0)
                continue;

            $uuid = $ID;
            //$uuid = $arFields['uuid'];

            try {
                $api = \classes\general\boomstarter_gifts::getApi();
                $gift = $api->getGift($uuid);
                $gift->setStateDelivery();
            } catch (\Boomstarter\Exception $ex) {
                $e = $APPLICATION->GetException();
                $this->lAdmin->AddUpdateError(($e ? $e->getString() : "Gift delivery error"), $ID);
            }
        }

    }
}


$admin = new BoomstarterGiftsAdmin();
$admin->run();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
