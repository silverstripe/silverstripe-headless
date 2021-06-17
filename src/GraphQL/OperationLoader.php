<?php


namespace SilverStripe\Headless\GraphQL;


use SilverStripe\GraphQL\Schema\Interfaces\SchemaUpdater;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class OperationLoader implements SchemaUpdater
{
    public static function updateSchema(Schema $schema, array $config = []): void
    {
        foreach (ModelLoader::getIncludedClasses() as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) use ($schema) {
                $model->addOperation('read');
                $model->addOperation('readOne');
            });
        }
    }
}
