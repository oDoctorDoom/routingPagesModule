<?php
namespace Vendor\Testpages\Domain;

final class Page
{
    public int $id;
    public string $name;
    public string $code;
    public string $url; // property URL
    public string $contentHtml; // property CONTENT (HTML)

    public function __construct(int $id, string $name, string $code, string $url, string $contentHtml)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->url = $url;
        $this->contentHtml = $contentHtml;
    }
}
