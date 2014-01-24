<?php

$module_id = 'boomstarter_gifts';

// подключим все необходимые файлы:
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php"); // инициализация модуля
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/prolog.php"); // пролог модуля

// подключим языковой файл
IncludeModuleLangFile(__FILE__);

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
// если нет прав - отправим к форме авторизации с сообщением об ошибке
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$sTableID = "tbl_gifts"; // ID таблицы
$oSort = new CAdminSorting($sTableID, "ID", "desc"); // объект сортировки
$lAdmin = new CAdminList($sTableID, $oSort); // основной объект списка


// ******************************************************************** //
//                           ФИЛЬТР                                     //
// ******************************************************************** //

// *********************** CheckFilter ******************************** //
// проверку значений фильтра для удобства вынесем в отдельную функцию
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;

    // В данном случае проверять нечего.
    // В общем случае нужно проверять значения переменных $find_имя
    // и в случае возниконовения ошибки передавать ее обработчику
    // посредством $lAdmin->AddFilterError('текст_ошибки').

    return count($lAdmin->arFilterErrors)==0; // если ошибки есть, вернем false;
}
// *********************** /CheckFilter ******************************* //

// опишем элементы фильтра
$FilterArr = Array(
    "find",
    "find_type",
    "find_id",
    "find_lid",
    "find_active",
    "find_visible",
    "find_auto",
);

// инициализируем фильтр
$lAdmin->InitFilter($FilterArr);

// если все значения фильтра корректны, обработаем его
if (CheckFilter())
{
    // создадим массив фильтрации для выборки CRubric::GetList() на основе значений фильтра
    $arFilter = Array(
        "ID"    => ($find!="" && $find_type == "id"? $find:$find_id),
        "LID"    => $find_lid,
        "ACTIVE"  => $find_active,
        "VISIBLE"  => $find_visible,
        "AUTO"    => $find_auto,
    );
}


// ******************************************************************** //
//                ОБРАБОТКА ДЕЙСТВИЙ НАД ЭЛЕМЕНТАМИ СПИСКА              //
// ******************************************************************** //

// сохранение отредактированных элементов
if($lAdmin->EditAction() && $POST_RIGHT=="W")
{
    // пройдем по списку переданных элементов
    foreach($FIELDS as $ID=>$arFields)
    {
        if(!$lAdmin->IsUpdated($ID))
            continue;

        // сохраним изменения каждого элемента
        $DB->StartTransaction();
        $ID = IntVal($ID);
        $cData = new CRubric;
        if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch()))
        {
            foreach($arFields as $key=>$value)
                $arData[$key]=$value;
            if(!$cData->Update($ID, $arData))
            {
                $lAdmin->AddGroupError(GetMessage("rub_save_error")." ".$cData->LAST_ERROR, $ID);
                $DB->Rollback();
            }
        }
        else
        {
            $lAdmin->AddGroupError(GetMessage("rub_save_error")." ".GetMessage("rub_no_rubric"), $ID);
            $DB->Rollback();
        }
        $DB->Commit();
    }
}

// обработка одиночных и групповых действий
if(($arID = $lAdmin->GroupAction()) && $POST_RIGHT=="W")
{
    // если выбрано "Для всех элементов"
    if($_REQUEST['action_target']=='selected')
    {
        $cData = new CRubric;
        $rsData = $cData->GetList(array($by=>$order), $arFilter);
        while($arRes = $rsData->Fetch())
            $arID[] = $arRes['ID'];
    }

    // пройдем по списку элементов
    foreach($arID as $ID)
    {
        if(strlen($ID)<=0)
            continue;
        $ID = IntVal($ID);

        // для каждого элемента совершим требуемое действие
        switch($_REQUEST['action'])
        {
            // удаление
            case "delete":
                @set_time_limit(0);
                $DB->StartTransaction();
                if(!CRubric::Delete($ID))
                {
                    $DB->Rollback();
                    $lAdmin->AddGroupError(GetMessage("rub_del_err"), $ID);
                }
                $DB->Commit();
                break;

            // активация/деактивация
            case "activate":
            case "deactivate":
                $cData = new CRubric;
                if(($rsData = $cData->GetByID($ID)) && ($arFields = $rsData->Fetch()))
                {
                    $arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");
                    if(!$cData->Update($ID, $arFields))
                        $lAdmin->AddGroupError(GetMessage("rub_save_error").$cData->LAST_ERROR, $ID);
                }
                else
                    $lAdmin->AddGroupError(GetMessage("rub_save_error")." ".GetMessage("rub_no_rubric"), $ID);
                break;
        }
    }
}

// ******************************************************************** //
//                ВЫБОРКА ЭЛЕМЕНТОВ СПИСКА                              //
// ******************************************************************** //

// выберем список рассылок
$cData = new CRubric;
$rsData = $cData->GetList(array($by=>$order), $arFilter);

// преобразуем список в экземпляр класса CAdminResult
$rsData = new CAdminResult($rsData, $sTableID);

// аналогично CDBResult инициализируем постраничную навигацию.
$rsData->NavStart();

// отправим вывод переключателя страниц в основной объект $lAdmin
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("rub_nav")));

// ******************************************************************** //
//                ПОДГОТОВКА СПИСКА К ВЫВОДУ                            //
// ******************************************************************** //

$lAdmin->AddHeaders(array(
        array(  "id"    =>"ID",
            "content"  =>"ID",
            "sort"    =>"id",
            "align"    =>"right",
            "default"  =>true,
        ),
        array(  "id"    =>"NAME",
            "content"  =>GetMessage("rub_name"),
            "sort"    =>"name",
            "default"  =>true,
        ),
        array(  "id"    =>"LID",
            "content"  =>GetMessage("rub_site"),
            "sort"    =>"lid",
            "default"  =>true,
        ),
        array(  "id"    =>"SORT",
            "content"  =>GetMessage("rub_sort"),
            "sort"    =>"sort",
            "align"    =>"right",
            "default"  =>true,
        ),
        array(  "id"    =>"ACTIVE",
            "content"  =>GetMessage("rub_act"),
            "sort"    =>"act",
            "default"  =>true,
        ),
        array(  "id"    =>"VISIBLE",
            "content"  =>GetMessage("rub_visible"),
            "sort"    =>"visible",
            "default"  =>true,
        ),
        array(  "id"    =>"AUTO",
            "content"  =>GetMessage("rub_auto"),
            "sort"    =>"auto",
            "default"  =>true,
        ),
        array(  "id"    =>"LAST_EXECUTED",
            "content"  =>GetMessage("rub_last_exec"),
            "sort"    =>"last_executed",
            "default"  =>true,
        ),
    ));

while($arRes = $rsData->NavNext(true, "f_")):

    // создаем строку. результат - экземпляр класса CAdminListRow
    $row =& $lAdmin->AddRow($f_ID, $arRes);

    // далее настроим отображение значений при просмотре и редаткировании списка

    // параметр NAME будет редактироваться как текст, а отображаться ссылкой
    $row->AddInputField("NAME", array("size"=>20));
    $row->AddViewField("NAME", '<a href="rubric_edit.php?ID='.$f_ID.'&lang='.LANG.'">'.$f_NAME.'</a>');

    // параметр LID будет редактироваться в виде выпадающего списка языков
    $row->AddEditField("LID", CLang::SelectBox("LID", $f_LID));

    // параметр SORT будет редактироваться текстом
    $row->AddInputField("SORT", array("size"=>20));

    // флаги ACTIVE и VISIBLE будут редактироваться чекбоксами
    $row->AddCheckField("ACTIVE");
    $row->AddCheckField("VISIBLE");

    // параметр AUTO будет отображаться в виде "Да" или "Нет", полужирным при редактировании
    $row->AddViewField("AUTO", $f_AUTO=="Y"?GetMessage("POST_U_YES"):GetMessage("POST_U_NO"));
    $row->AddEditField("AUTO", "<b>".($f_AUTO=="Y"?GetMessage("POST_U_YES"):GetMessage("POST_U_NO"))."</b>");

    // сформируем контекстное меню
    $arActions = Array();

    // редактирование элемента
    $arActions[] = array(
        "ICON"=>"edit",
        "DEFAULT"=>true,
        "TEXT"=>GetMessage("rub_edit"),
        "ACTION"=>$lAdmin->ActionRedirect("rubric_edit.php?ID=".$f_ID)
    );

    // удаление элемента
    if ($POST_RIGHT>="W")
        $arActions[] = array(
            "ICON"=>"delete",
            "TEXT"=>GetMessage("rub_del"),
            "ACTION"=>"if(confirm('".GetMessage('rub_del_conf')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
        );

    // вставим разделитель
    $arActions[] = array("SEPARATOR"=>true);

    // проверка шаблона для автогенерируемых рассылок
    if (strlen($f_TEMPLATE)>0 && $f_AUTO=="Y")
        $arActions[] = array(
            "ICON"=>"",
            "TEXT"=>GetMessage("rub_check"),
            "ACTION"=>$lAdmin->ActionRedirect("template_test.php?ID=".$f_ID)
        );

    // если последний элемент - разделитель, почистим мусор.
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);

    // применим контекстное меню к строке
    $row->AddActions($arActions);

endwhile;

// резюме таблицы
$lAdmin->AddFooter(
    array(
        array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // кол-во элементов
        array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // счетчик выбранных элементов
    )
);

// групповые действия
$lAdmin->AddGroupActionTable(Array(
        "delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"), // удалить выбранные элементы
        "activate"=>GetMessage("MAIN_ADMIN_LIST_ACTIVATE"), // активировать выбранные элементы
        "deactivate"=>GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"), // деактивировать выбранные элементы
    ));

// ******************************************************************** //
//                АДМИНИСТРАТИВНОЕ МЕНЮ                                 //
// ******************************************************************** //

// сформируем меню из одного пункта - добавление рассылки
$aContext = array(
    array(
        "TEXT"=>GetMessage("POST_ADD"),
        "LINK"=>"rubric_edit.php?lang=".LANG,
        "TITLE"=>GetMessage("POST_ADD_TITLE"),
        "ICON"=>"btn_new",
    ),
);

// и прикрепим его к списку
$lAdmin->AddAdminContextMenu($aContext);

// ******************************************************************** //
//                ВЫВОД                                                 //
// ******************************************************************** //

// альтернативный вывод
$lAdmin->CheckListMode();

// установим заголовок страницы
$APPLICATION->SetTitle(GetMessage("rub_title"));

// не забудем разделить подготовку данных и вывод
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

// ******************************************************************** //
//                ВЫВОД ФИЛЬТРА                                         //
// ******************************************************************** //

// создадим объект фильтра
$oFilter = new CAdminFilter(
    $sTableID."_filter",
    array(
        "ID",
        GetMessage("rub_f_site"),
        GetMessage("rub_f_active"),
        GetMessage("rub_f_public"),
        GetMessage("rub_f_auto"),
    )
);
?>
    <form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
        <?$oFilter->Begin();?>
        <tr>
            <td><b><?=GetMessage("rub_f_find")?>:</b></td>
            <td>
                <input type="text" size="25" name="find" value="<?echo htmlspecialchars($find)?>" title="<?=GetMessage("rub_f_find_title")?>">
                <?
                $arr = array(
                    "reference" => array(
                        "ID",
                    ),
                    "reference_id" => array(
                        "id",
                    )
                );
                echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?="ID"?>:</td>
            <td>
                <input type="text" name="find_id" size="47" value="<?echo htmlspecialchars($find_id)?>">
            </td>
        </tr>
        <tr>
            <td><?=GetMessage("rub_f_site").":"?></td>
            <td><input type="text" name="find_lid" size="47" value="<?echo htmlspecialchars($find_lid)?>"></td>
        </tr>
        <tr>
            <td><?=GetMessage("rub_f_active")?>:</td>
            <td>
                <?
                $arr = array(
                    "reference" => array(
                        GetMessage("POST_YES"),
                        GetMessage("POST_NO"),
                    ),
                    "reference_id" => array(
                        "Y",
                        "N",
                    )
                );
                echo SelectBoxFromArray("find_active", $arr, $find_active, GetMessage("POST_ALL"), "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?=GetMessage("rub_f_public")?>:</td>
            <td><?echo SelectBoxFromArray("find_visible", $arr, $find_visible, GetMessage("POST_ALL"), "");?></td>
        </tr>
        <tr>
            <td><?=GetMessage("rub_f_auto")?>:</td>
            <td><?echo SelectBoxFromArray("find_auto", $arr, $find_auto, GetMessage("POST_ALL"), "");?></td>
        </tr>
        <?
        $oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
        $oFilter->End();
        ?>
    </form>

<?
// выведем таблицу списка элементов
$lAdmin->DisplayList();
?>

<?
// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
