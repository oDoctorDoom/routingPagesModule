<?php
namespace Vendor\Testpages\Infrastructure;

use Vendor\Testpages\Domain\Page;

class PageRepository
{
    private int $iblockId;

    public function __construct(int $iblockId)
    {
        $this->iblockId = $iblockId;
    }

    public function getByUrl(string $url): ?Page
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return null;
        }

        $url = trim($url);
        if ($url === '') return null;

        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y', '=PROPERTY_URL' => $url],
            false,
            false,
            ['ID','NAME','CODE','PROPERTY_URL','PROPERTY_CONTENT']
        );
        if ($row = $res->Fetch()) {
            $html = '';
            if (is_array($row['PROPERTY_CONTENT_VALUE']) && isset($row['PROPERTY_CONTENT_VALUE']['TEXT'])) {
                $html = (string)$row['PROPERTY_CONTENT_VALUE']['TEXT'];
            } elseif (is_string($row['PROPERTY_CONTENT_VALUE'])) {
                $html = (string)$row['PROPERTY_CONTENT_VALUE'];
            }
            return new Page((int)$row['ID'], (string)$row['NAME'], (string)$row['CODE'], (string)$row['PROPERTY_URL_VALUE'], $html);
        }
        return null;
    }
}
