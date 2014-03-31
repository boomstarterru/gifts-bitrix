<?php

if (!check_bitrix_sessid())
    return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/boomstarter_gifts/install/step.php', 'ru.'.LANG_CHARSET);

echo CAdminMessage::ShowNote(GetMessage('INSTALL_MODULE_SUCCESS'));

?>
<div style="font-size: 12px;"></div>
<br>
<form action="<?echo $APPLICATION->GetCurPage()?>">
    <input type="hidden" name="lang" value="<?echo LANG?>">
    <input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
</form>
