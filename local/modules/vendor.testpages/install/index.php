<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class vendor_testpages extends CModule
{
    public $MODULE_ID = 'vendor.testpages';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Vendor';
    public $PARTNER_URI  = 'https://example.com';

    public function __construct()
    {
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('VENDOR_TESTPAGES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VENDOR_TESTPAGES_MODULE_DESC');
    }

    public function DoInstall()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        ModuleManager::RegisterModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallUrlRewrite();
    }

    public function DoUninstall()
    {
        ModuleManager::UnRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // copy public router /pages/index.php
        CopyDirFiles(__DIR__ . '/public/pages', $_SERVER['DOCUMENT_ROOT'] . '/pages', true, true);
        // register component files to /bitrix/components on install
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
        return true;
    }

    public function UnInstallFiles()
    {
        // keep public pages by default; remove if needed
        // DeleteDirFilesEx('/pages');
        // components are copied under /bitrix/components/vendor/testpage.view — keep for safety
        return true;
    }

    public function InstallUrlRewrite()
    {
        if (!class_exists('CUrlRewriter')) require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');
        $rule = [
            'CONDITION' => '#^/pages/(.+?)/?$#',
            'RULE' => 'url=/$1/',
            'ID' => '',
            'PATH' => '/pages/index.php',
            'SORT' => 100,
        ];
        // check existing
        $exists = false;
        $rules = [];
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/urlrewrite.php')) {
            include $_SERVER['DOCUMENT_ROOT'].'/urlrewrite.php';
            $rules = $arUrlRewrite ?? [];
        }
        foreach ($rules as $r) {
            if ($r['CONDITION'] === $rule['CONDITION'] && $r['PATH'] === $rule['PATH']) {
                $exists = True; break;
            }
        }
        if (!$exists) {
            CUrlRewriter::Add($rule);
        }
        return true;
    }

    public function UnInstallUrlRewrite()
    {
        if (!class_exists('CUrlRewriter')) require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');
        $condition = '#^/pages/(.+?)/?$#';
        $rules = [];
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/urlrewrite.php')) {
            include $_SERVER['DOCUMENT_ROOT'].'/urlrewrite.php';
            $rules = $arUrlRewrite ?? [];
        }
        foreach ($rules as $r) {
            if ($r['CONDITION'] === $condition && $r['PATH'] === '/pages/index.php') {
                CUrlRewriter::Delete($r);
            }
        }
        return true;
    }

    // Creates iblock type, iblock, properties and a demo element
    public function InstallDB()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            throw new \RuntimeException('IBlock module not installed');
        }

        $iblockTypeId = 'vendor_pages';
        $iblockCode   = 'vendor_test_pages';

        // 1) Create iblock type if not exists
        $typeRes = \CIBlockType::GetByID($iblockTypeId);
        if (!$typeRes->Fetch()) {
            $ibType = new \CIBlockType();
            $ok = $ibType->Add([
                'ID' => $iblockTypeId,
                'SECTIONS' => 'N',
                'IN_RSS' => 'N',
                'SORT' => 100,
                'LANG' => [
                    'ru' => ['NAME' => 'Страницы Vendor', 'ELEMENT_NAME' => 'Страница'],
                    'en' => ['NAME' => 'Vendor Pages', 'ELEMENT_NAME' => 'Page'],
                ]
            ]);
            if (!$ok) {
                throw new \RuntimeException('Failed to create IBlockType: ' . $ibType->LAST_ERROR);
            }
        }

        // 2) Create iblock if not exists
        $ib = \CIBlock::GetList([], ['TYPE' => $iblockTypeId, 'CODE' => $iblockCode])->Fetch();
        $iblockId = 0;
        if (!$ib) {
            $iblock = new \CIBlock();
            $iblockId = (int)$iblock->Add([
                'ACTIVE' => 'Y',
                'NAME' => 'Страницы (тест)',
                'CODE' => $iblockCode,
                'IBLOCK_TYPE_ID' => $iblockTypeId,
                'SITE_ID' => 's1',
                'SORT' => 100,
                'GROUP_ID' => ['2' => 'R'], // read for all users
            ]);
            if ($iblockId <= 0) {
                throw new \RuntimeException('Failed to create IBlock: ' . $iblock->LAST_ERROR);
            }
        } else {
            $iblockId = (int)$ib['ID'];
        }

        // store IBLOCK_ID in options for controller/component
        Option::set($this->MODULE_ID, 'IBLOCK_ID', (string)$iblockId);

        // 3) Create properties CONTENT (HTML) and URL (String) if not exist
        $needProp = function($code) use ($iblockId) {
            $res = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
            return !$res->Fetch();
        };

        if ($needProp('CONTENT')) {
            $prop = new \CIBlockProperty();
            $ok = $prop->Add([
                'IBLOCK_ID' => $iblockId,
                'NAME' => 'Контент',
                'ACTIVE' => 'Y',
                'SORT' => 100,
                'CODE' => 'CONTENT',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'HTML', // HTML editor
                'FILTRABLE' => 'N',
                'MULTIPLE' => 'N',
                'IS_REQUIRED' => 'N',
            ]);
            if (!$ok) {
                throw new \RuntimeException('Failed to create property CONTENT: ' . $prop->LAST_ERROR);
            }
        }

        if ($needProp('URL')) {
            $prop = new \CIBlockProperty();
            $ok = $prop->Add([
                'IBLOCK_ID' => $iblockId,
                'NAME' => 'Ссылка',
                'ACTIVE' => 'Y',
                'SORT' => 110,
                'CODE' => 'URL',
                'PROPERTY_TYPE' => 'S',
                'FILTRABLE' => 'Y',
                'MULTIPLE' => 'N',
                'IS_REQUIRED' => 'N',
            ]);
            if (!$ok) {
                throw new \RuntimeException('Failed to create property URL: ' . $prop->LAST_ERROR);
            }
        }

        // 4) Create demo element if not exists
        $el = new \CIBlockElement();
        $exists = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'test-page'])->Fetch();
        if (!$exists) {
            $content = [
                'VALUE' => [
                    'TEXT' => '<h1>Тестовая страница</h1><p>Это демо-контент, который хранится в свойстве инфоблока.</p><p>Откройте: <code>/pages/test-page/</code></p>',
                    'TYPE' => 'HTML'
                ]
            ];
            $url = '/test-page/';
            $elementId = $el->Add([
                'IBLOCK_ID' => $iblockId,
                'NAME' => 'Тестовая страница',
                'CODE' => 'test-page',
                'ACTIVE' => 'Y',
                'PROPERTY_VALUES' => [
                    'CONTENT' => $content,
                    'URL' => $url,
                ]
            ]);
            if (!$elementId) {
                throw new \RuntimeException('Failed to create demo element: ' . $el->LAST_ERROR);
            }
        }

        return true;
    }

    public function UnInstallDB()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return true;
        }

        $iblockTypeId = 'vendor_pages';
        $iblockCode   = 'vendor_test_pages';

        // Remove iblock (keep type)
        if ($ib = \CIBlock::GetList([], ['TYPE' => $iblockTypeId, 'CODE' => $iblockCode])->Fetch()) {
            \CIBlock::Delete((int)$ib['ID']);
        }

        // clean option
        Option::delete($this->MODULE_ID, ['name' => 'IBLOCK_ID']);

        return true;
    }
}
