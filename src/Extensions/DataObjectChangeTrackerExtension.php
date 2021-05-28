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

class DataObjectChangeTrackerExtension extends DataExtension
{
    /**
     * @var bool
     * @config
     */
    private static $apply_publish_queue_filter = true;

    /**
     * @throws ReflectionException
     */
    public function onAfterWrite()
    {
        if (!ModelLoader::includes($this->owner)) {
            return;
        }

        $stage = $this->owner->hasExtension(Versioned::class) ? Versioned::DRAFT : ChangeTracker::STAGE_ALL;
        ChangeTracker::singleton()->record(
            $this->owner,
            ChangeTracker::TYPE_UPDATED,
            $stage
        );
    }

    /**
     * @throws ReflectionException
     */
    public function onAfterDelete()
    {
        if (!ModelLoader::includes($this->owner)) {
            return;
        }

        $stage = $this->owner->hasExtension(Versioned::class) ? Versioned::DRAFT : ChangeTracker::STAGE_ALL;
        ChangeTracker::singleton()->record(
            $this->owner,
            ChangeTracker::TYPE_UPDATED,
            $stage
        );
    }

    /**
     * @throws ReflectionException
     */
    public function onAfterPublish()
    {
        if (!ModelLoader::includes($this->owner)) {
            return;
        }

        ChangeTracker::singleton()->record(
            $this->owner,
            ChangeTracker::TYPE_UPDATED,
            Versioned::LIVE
        );
    }

    /**
     * @throws ReflectionException
     */
    public function onAfterUnpublish()
    {
        if (!ModelLoader::includes($this->owner)) {
            return;
        }

        ChangeTracker::singleton()->record(
            $this->owner,
            ChangeTracker::TYPE_DELETED,
            Versioned::LIVE
        );
    }

    /**
     * @throws ReflectionException
     */
    public function onAfterArchive()
    {
        if (!ModelLoader::includes($this->owner)) {
            return;
        }

        ChangeTracker::singleton()->record(
            $this->owner,
            ChangeTracker::TYPE_DELETED,
            Versioned::DRAFT
        );
    }

    /**
     * Ensure that changes to many_many will record that the parent record has changed.
     * @param RelationList $list
     */
    public function updateManyManyComponents(RelationList $list)
    {
        /* @var DataObject $owner */
        $owner = $this->getOwner();
        $callback = function (RelationList $list) use ($owner) {
            if (!$list instanceof ManyManyList && !$list instanceof ManyManyThroughList) {
                return;
            }
            // Plain many_many can't be versioned. Applies to all stages
            $stage = $list instanceof ManyManyList
                ? ChangeTracker::STAGE_ALL
                : Versioned::get_stage();

            ChangeTracker::singleton()->record(
                $owner,
                ChangeTracker::TYPE_UPDATED,
                $stage
            );
        };

        $list->addCallbacks()->add($callback);
        $list->addCallbacks()->add($callback);
    }

}
