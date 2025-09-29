<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('vendor.testpages', [
    'Vendor\\Testpages\\Domain\\Page'              => 'lib/Domain/Page.php',
    'Vendor\\Testpages\\Infrastructure\\PageRepository' => 'lib/Infrastructure/PageRepository.php',
    'Vendor\\Testpages\\Application\\PageService'  => 'lib/Application/PageService.php',
    'Vendor\\Testpages\\Controller\\PagesController' => 'lib/Controller/PagesController.php',
]);
