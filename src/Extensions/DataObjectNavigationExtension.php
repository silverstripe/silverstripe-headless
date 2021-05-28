<?php


namespace SilverStripe\Headless\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Gatsby\GraphQL\ModelLoader;
use SilverStripe\Gatsby\Services\ChangeTracker;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\RelationList;
use SilverStripe\Versioned\Versioned;
use ReflectionException;

class DataObjectHashExtension extends DataExtension
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
