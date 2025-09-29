<?php
namespace Vendor\Testpages\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Vendor\Testpages\Application\PageService;
use Vendor\Testpages\Infrastructure\PageRepository;

class PagesController extends Controller
{
    protected function getDefaultPreFilters()
    {
        return [
            new ActionFilter\Csrf(false), // allow GET without CSRF
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
        ];
    }

    public function getByUrlAction(string $url): array
    {
        $iblockId = (int)\Bitrix\Main\Config\Option::get('vendor.testpages', 'IBLOCK_ID', 0);
        if ($iblockId <= 0) {
            return ['found' => false, 'error' => 'IBLOCK_ID is not configured'];
        }

        $service = new PageService(new PageRepository($iblockId));
        $page = $service->findByUrl($url);

        if (!$page) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'data' => [
                'id' => $page->id,
                'name' => $page->name,
                'code' => $page->code,
                'url' => $page->url,
                'contentHtml' => $page->contentHtml,
            ]
        ];
    }
}
