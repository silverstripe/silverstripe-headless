<?php


namespace SilverStripe\Headless\GridField;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Headless\Model\PublishEvent;
use SilverStripe\Headless\Model\Webhook;
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
            $webhooks = Webhook::get()->filter([
                'Event' => Webhook::EVENT_PUBLISH
            ]);
            $success = true;
            foreach ($webhooks as $webhook) {
                $response = $webhook->invoke();
                $code = $response->getStatusCode();
                if ($code < 200 || $code >= 300) {
                    $success = false;
                    break;
                }
            }
            $event = PublishEvent::create([
                'Status' => $success ? PublishEvent::STATUS_SUCCESS : PublishEvent::STATUS_FAILURE,
            ]);
            $event->write();
            if ($success) {
                foreach ($gridField->getList() as $item) {
                    $item->PublishEventID = $event->ID;
                    $item->write();
                }
            }
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
