<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Страницы");
$APPLICATION->IncludeComponent('vendor:testpage.view', '', [
    'SEF_MODE' => 'N', // we pass url via ?url=
    'IBLOCK_ID' => (int)Bitrix\Main\Config\Option::get('vendor.testpages', 'IBLOCK_ID', 0),
    'URL' => (string)($_GET['url'] ?? ''),
]);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
