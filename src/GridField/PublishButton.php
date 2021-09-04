<?php


namespace SilverStripe\Headless\GridField;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Headless\Model\OutgoingWebhook;
use SilverStripe\Headless\Model\PublishQueueItem;
use SilverStripe\Headless\Services\Publisher;
use SilverStripe\ORM\SS_List;

class PublishButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator
{
    use Injectable;

    /**
     * @param GridField $gridField
     * @return string[]
     */
    public function getActions($gridField)
    {
        return ['publishqueue'];
    }

    /**
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName === 'publishqueue') {
            $webhook = OutgoingWebhook::get()->filter([
                'Event' => OutgoingWebhook::EVENT_PUBLISH
            ])->first();
            if (!$webhook) {
                return;
            }
            $publisher = Publisher::create($webhook);
            $publisher->publish(PublishQueueItem::getQueued());
        }
    }

    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        return $dataList;
    }

    /**
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        if (!$gridField->getList()->exists()) {
            return [];
        }

        return [
            'before' => GridField_FormAction::create(
                $gridField,
                'publishqueue',
                _t(
                    static::class . '.PUBLISH_QUEUE',
                    'Publish {count} items',
                    ['count' => $gridField->getList()->count()]
                ),
                'publishqueue',
                []
            )
                ->addExtraClass('btn font-icon-rocket action btn-outline-primary')
                ->forTemplate()
        ];
    }
}
