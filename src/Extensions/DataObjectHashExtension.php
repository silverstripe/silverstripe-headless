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
     * @var string
     * @config
     */
    private static $headless_schema = 'default';

    /**
     * @param string $class
     * @param int $id
     * @return string
     */
    public static function createHashID(string $class, int $id): string
    {
        return md5(sprintf('%s:%s', $class, $id));
    }

    /**
     * Gets a truly unique identifier to the classname and ID
     * @return string|null
     */
    public function getHashID(): ?string
    {
        if (!$this->owner->exists()) {
            return null;
        }
        return static::createHashID($this->owner->ClassName, $this->owner->ID);
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getTypeAncestry(): array
    {
        $types = [];
        $config = SchemaBuilder::singleton()->getConfig($this->owner->config()->get('headless_schema'));
        if ($config) {
            foreach (array_reverse(ClassInfo::ancestry($this->owner)) as $class) {
                if ($class === DataObject::class) {
                    break;
                }
                $types[] = [
                    $config->getTypeNameForClass($class),
                    static::createHashID($class, $this->owner->ID),
                ];
            }
        }

        return $types;
    }

}
