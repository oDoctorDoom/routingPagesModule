<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();



use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Vendor\Testpages\Application\PageService;
use Vendor\Testpages\Infrastructure\PageRepository;
if (!Loader::includeModule('vendor.testpages')) {
    ShowError('Модуль vendor.testpages не установлен');
    return;
}

Loc::loadMessages(__FILE__);

// ---- НОРМАЛИЗАЦИЯ ПАРАМЕТРОВ -------------------------------------------------
$arParams['SEF_MODE']         = ($arParams['SEF_MODE'] === 'Y' ? 'Y' : 'N');
$arParams['SEF_FOLDER']       = rtrim($arParams['SEF_FOLDER'] ?? '/pages/', '/') . '/';
$arParams['VARIABLE_ALIASES'] = is_array($arParams['VARIABLE_ALIASES'] ?? null) ? $arParams['VARIABLE_ALIASES'] : [];
$arParams['IBLOCK_ID']        = (int)($arParams['IBLOCK_ID'] ?? 0);
$arParams['CACHE_TYPE']       = $arParams['CACHE_TYPE'] ?? 'A';
$arParams['CACHE_TIME']       = (int)($arParams['CACHE_TIME'] ?? 300);
$arParams['SET_STATUS_404']   = ($arParams['SET_STATUS_404'] ?? 'N') === 'Y' ? 'Y' : 'N';

// ---- ПРОВЕРКИ ----------------------------------------------------------------
if (!Loader::includeModule('iblock'))
{
    ShowError(Loc::getMessage('VTP_ERR_NO_IBLOCK') ?: 'Модуль iblock не установлен');
    return;
}

// ---- РАЗБОР URL ---------------------------------------------------------------
$url = (string)($arParams['URL'] ?? '');

if ($arParams['SEF_MODE'] === 'Y')
{
    $arDefaultUrlTemplates = ['detail' => '#URL#'];
    $arComponentVariables  = ['URL'];

    $arUrlTemplates   = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates, $arParams['SEF_URL_TEMPLATES']);
    $arVariableAliases = CComponentEngine::MakeComponentVariableAliases([], $arParams['VARIABLE_ALIASES']);

    $arVariables = [];
    $componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

    if ($componentPage === 'detail' && isset($arVariables['URL']))
    {
        $url = rtrim($arParams['SEF_FOLDER'], '/') . '/' . ltrim((string)$arVariables['URL'], '/');

        if (substr($url, -1) !== '/') { $url .= '/'; }
    }
}
else
{
    if ($url === '')
    {
        $url = (string)($_GET['url'] ?? '');
    }
}
// ---- ДАННЫЕ -------------------------------------------------------------------
$iblockId = (int)($arParams['IBLOCK_ID'] ?: Option::get('vendor.testpages', 'IBLOCK_ID', 0));
$service  = new PageService(new PageRepository($iblockId));

// кешируем только на найденный URL + ID ИБ
$cacheKey = [$iblockId, $url];

if ($this->StartResultCache(false, $cacheKey))
{
    $page = $service->findByUrl($url);


    if (!$page)
    {
        // если включено — отдадим 404
        if ($arParams['SET_STATUS_404'] === 'Y')
        {
            @CHTTP::SetStatus('404 Not Found');
            if (defined('ERROR_404')) { define('ERROR_404', 'Y'); }
        }

        $arResult['FOUND'] = false;
        $arResult['PAGE']  = null;
        $this->IncludeComponentTemplate();
        return;
    }

    $arResult['FOUND'] = true;
    $arResult['PAGE']  = $page;

    $this->IncludeComponentTemplate();
}