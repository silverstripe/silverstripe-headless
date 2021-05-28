<?php


namespace SilverStripe\Headless\Extensions;


use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\ORM\DataExtension;

class Content404Extension extends DataExtension
{
    /**
     * Prevent the catch-all ModelAsController route from doing anything.
     * @param ModelAsController $controller
     */
    public function modelascontrollerInit(ModelAsController $controller)
    {
        $controller->getResponse()->setStatusCode(404);
    }
}
