<?php
namespace Vendor\Testpages\Application;

use Vendor\Testpages\Infrastructure\PageRepository;
use Vendor\Testpages\Domain\Page;

class PageService
{
    private PageRepository $repo;

    public function __construct(PageRepository $repo)
    {
        $this->repo = $repo;
    }

    public function findByUrl(string $url): ?Page
    {
        // normalise URL (ensure it starts with slash, no double slashes)
        $u = '/' . ltrim(trim($url), '/');
        // keep trailing slash as stored
        return $this->repo->getByUrl($u);
    }
}
