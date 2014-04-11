<?php

$module_id = 'boomstarter.gifts';

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/'.$module_id.'/admin/menu.php', 'ru.'.LANG_CHARSET);

if($APPLICATION->GetGroupRight("form")>"D") // проверка уровня доступа к модулю веб-форм
{
    // сформируем верхний пункт меню
    $aMenu = array(
        "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
        "sort"        => 100,                    // вес пункта меню
        "module_id"   => $module_id,
        "url"         => "boomstarter_gifts_list.php",  // ссылка на пункте меню
        "text"        => GetMessage('GIFTS_TEXT'),       // текст пункта меню "Подарки"
        "title"       => GetMessage('GIFTS_TITLE'), // текст всплывающей подсказки "Подарки через Boomstarter"
        "icon"        => "form_menu_icon", // малая иконка
        "page_icon"   => "form_page_icon", // большая иконка
        "items_id"    => "menu_gifts",  // идентификатор ветви
        "items"       => array(
            array(
                'text' => GetMessage('GIFTS_PENDING'), // 'Оплаченные'
                'url' => 'boomstarter_gifts_list.php?only=pending',
                'title' => 'title',
            ),
        ),
    );

    // вернем полученный список
    return $aMenu;
}
// если нет доступа, вернем false
return false;

