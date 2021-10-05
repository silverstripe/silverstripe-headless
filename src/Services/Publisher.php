<?php


namespace SilverStripe\Headless\Services;


use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Headless\Model\OutgoingWebhook;
use InvalidArgumentException;
use SilverStripe\Headless\Model\PublishEvent;
use SilverStripe\Headless\Model\PublishQueueItem;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ValidationException;

class Publisher
{
    use Injectable;

    /**
     * @var OutgoingWebhook
     */
    private $webhook;

    public function __construct(OutgoingWebhook $webhook)
    {
        if ($webhook->Event !== OutgoingWebhook::EVENT_PUBLISH) {
            throw new InvalidArgumentException(sprintf(
                '%s only accepts webhooks what are event type %s',
                static::class,
                OutgoingWebhook::EVENT_PUBLISH
            ));
        }

        $this->webhook = $webhook;
    }

    /**
     * @param DataList $items
     * @return PublishEvent|null
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function publish(DataList $items): ?PublishEvent
    {
        if ($items->dataClass() !== PublishQueueItem::class) {
            throw new InvalidArgumentException(sprintf(
                'Cannot publish items of class %s. Must be %s',
                $items->dataClass(),
                PublishQueueItem::class
            ));
        }
        $isOptimistic = $this->webhook->PublishBehaviour === OutgoingWebhook::PUBLISH_OPTIMISTIC;

        $status = $isOptimistic
            ? PublishEvent::STATUS_SUCCESS
            : PublishEvent::STATUS_PENDING;

        $response = $this->webhook->invoke();
        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 300) {
            $status = PublishEvent::STATUS_FAILURE;
        }

        $event = PublishEvent::create([
            'Status' => $status,
        ]);
        $event->write();
        if ($status !== PublishEvent::STATUS_FAILURE) {
            foreach ($items as $item) {
                $item->PublishEventID = $event->ID;
                $item->write();
            }
        }

        return $event;
    }
}
