<?php


namespace SilverStripe\Headless\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Path;
use SilverStripe\Headless\Model\IncomingWebhook;
use SilverStripe\Headless\Model\PublishEvent;
use SilverStripe\Headless\Model\PublishQueueItem;
use SilverStripe\Headless\Services\TokenValidator;

class IncomingWebhookController extends Controller
{
    /**
     * @var string
     * @config
     */
    private static $url_segment = 'hooks';

    private static $url_handlers = [
        'POST $Key!' => 'handleHook',
    ];

    private static $allowed_actions = [
        'handleHook',
    ];

    /**
     * @var TokenValidator | null
     */
    private $tokenValidator;

    public function handleHook(HTTPRequest $request)
    {
        if ($this->getTokenValidator()) {
            $valid = $this->getTokenValidator()->validate($request);
            if (!$valid) {
                $this->httpError(403, 'Invalid token');
                return;
            }
        }
        $hook = IncomingWebhook::getByKey($request->param('Key'));
        if (!$hook) {
            $this->httpError(404);
            return;
        }
        $event = $hook->Event;

        $events = PublishEvent::get()->sort('Created DESC');
        $progress = $events->filter('Status', PublishEvent::STATUS_PROGRESS)
            ->first();
        $queued = $events->filter('Status', PublishEvent::STATUS_QUEUED)
            ->first();
        
        $duration = $progress ? (time() - $progress->obj('Created')->getTimestamp()) : 0;
        $queued->Items()->setByIDList(PublishQueueItem::getQueued()->column());

        switch ($event) {
            case IncomingWebhook::EVENT_DEPLOY_START:
                if ($queued) {
                    $queued->Status = PublishEvent::STATUS_PROGRESS;
                    $queued->write();
                }
                break;

            case IncomingWebhook::EVENT_DEPLOY_SUCCESS:
                if ($progress) {
                    $progress->Status = PublishEvent::STATUS_SUCCESS;
                    $progress->Duration = $duration;
                    $progress->write();
                }
                break;

            case IncomingWebhook::EVENT_DEPLOY_FAILURE:
                if ($progress) {
                    $progress->Status = PublishEvent::STATUS_FAILURE;
                    $progress->Duration = $duration;
                    $progress->write();
                }
                break;
        }
    }

    /**
     * @param string|null
     */
    public function Link($action = null): string
    {
        return Path::join(static::config()->get('hooks'), $action);
    }

    /**
     * @param TokenValidator $validator
     * @return $this
     */
    public function setTokenValidator(TokenValidator $validator): self
    {
        $this->tokenValidator = $validator;

        return $this;
    }

    /**
     * @return TokenValidator|null
     */
    public function getTokenValidator(): ?TokenValidator
    {
        return $this->tokenValidator;
    }
}
