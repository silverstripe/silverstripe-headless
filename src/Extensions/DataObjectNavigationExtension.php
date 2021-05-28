<?php


namespace SilverStripe\Headless\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class DataObjectNavigationExtension extends DataExtension
{

    /**
     * @return array|null
     */
    public function getNavigationPath(): ?array
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

        return $crumbs;
    }

}
