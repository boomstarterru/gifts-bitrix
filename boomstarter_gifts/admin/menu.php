<?php

$module_id = 'boomstarter_gifts';

IncludeModuleLangFile(__FILE__); // в menu.php точно так же можно использовать языковые файлы

if($APPLICATION->GetGroupRight("form")>"D") // проверка уровня доступа к модулю веб-форм
{
    // сформируем верхний пункт меню
    $aMenu = array(
        "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
        "sort"        => 100,                    // вес пункта меню
        "module_id"   => $module_id,
        "url"         => "boomstarter_gifts_list.php",  // ссылка на пункте меню
        "text"        => "Подарки",       // текст пункта меню
        "title"       => "Подарки через Boomstarter", // текст всплывающей подсказки
        "icon"        => "form_menu_icon", // малая иконка
        "page_icon"   => "form_page_icon", // большая иконка
        "items_id"    => "menu_gifts",  // идентификатор ветви
        "items"       => array(
            array(
                'text' => 'Оплаченные',
                'url' => 'boomstarter_gifts_list.php?only=pending',
                'title' => 'title',
            ),
        ),
    );

    // далее выберем список веб-форм и добавим для каждой соответствующий пункт меню
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");

    // вернем полученный список
    return $aMenu;
}
// если нет доступа, вернем false
return false;

