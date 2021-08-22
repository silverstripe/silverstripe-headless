<?php


namespace SilverStripe\Headless\Extensions;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class DataObjectNavigationExtension extends DataExtension
{
    public function getCleanLink(): ?string
    {
        if (!$this->owner->hasMethod('Link')) {
            return null;
        }
        $link = $this->owner->Link();
        $clean = preg_replace('#^/|/$#', '', $link);

        return empty($clean) ? '/' : $clean;
    }


    /**
     * @return array|null
     */
    public function getNavigationPath(): ?ArrayList
    {
        if (!$this->owner->hasExtension(Hierarchy::class)) {
            return null;
        }
        $crumbs = [];
        $ancestors = array_reverse($this->owner->getAncestors()->toArray());
        /** @var DataObject $ancestor */
        foreach ($ancestors as $ancestor) {
            $crumbs[] = $ancestor;
        }
        $crumbs[] = $this->owner;

        return ArrayList::create($crumbs);
    }

}
