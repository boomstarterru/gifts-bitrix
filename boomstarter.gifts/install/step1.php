<?php

if (!check_bitrix_sessid())
    return;

echo CAdminMessage::ShowNote("Модуль dev_module установлен");

