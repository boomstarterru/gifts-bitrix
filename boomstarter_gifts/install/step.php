<?php

if (!check_bitrix_sessid())
    return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/boomstarter_gifts/install/step.php', 'ru.'.LANG_CHARSET);

echo CAdminMessage::ShowNote(GetMessage('INSTALL_MODULE_SUCCESS'));

