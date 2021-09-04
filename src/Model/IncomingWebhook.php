<?php


namespace SilverStripe\Headless\Model;

use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Path;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\TextField;
use SilverStripe\Headless\Controllers\IncomingWebhookController;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\RandomGenerator;

class IncomingWebhook extends DataObject
{
    const EVENT_DEPLOY_START = 'DEPLOY_START';
    const EVENT_DEPLOY_SUCCESS = 'DEPLOY_SUCCESS';
    const EVENT_DEPLOY_FAILURE = 'DEPLOY_FAILURE';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
        'Key' => 'Varchar(255)',
        'Event' => "Enum('"
            . self::EVENT_DEPLOY_START . ", "
            . self::EVENT_DEPLOY_FAILURE . ", "
            . self::EVENT_DEPLOY_SUCCESS . "', '"
            . self::EVENT_DEPLOY_FAILURE
            . "')",
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'URL' => 'URL',
        'Event' => 'Responds to',
    ];

    /**
     * @var string
     */
    private static $table_name = 'IncomingWebhook';

    /**
     * @var string
     */
    private static $singular_name = 'Incoming Webhook';

    /**
     * @var string
     */
    private static $plural_name = 'Incoming Webhooks';

    /**
     * @var string
     */
    private static $default_sort = 'ID ASC';

    /**
     * @var bool[]
     */
    private static $indexes = [
        'Event' => true,
        'Key' => true,
    ];

    /**
     * @param string $key
     * @return IncomingWebhook|null
     */
    public static function getByKey(string $key): ?IncomingWebhook
    {
        /* @var IncomingWebhook | null $result*/
        $result = static::get()->filter('Key', $key)->first();

        return $result;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root'));
        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', 'Webhook label (for reference only)'),
            TextField::create('Key'),
            DropdownField::create('Event', 'Webhook responds to', ArrayLib::valuekey([
                self::EVENT_DEPLOY_START,
                self::EVENT_DEPLOY_SUCCESS,
                self::EVENT_DEPLOY_FAILURE,
            ])),
        ]);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $events = static::singleton()->dbObject('Event')->enumValues();
        $generator = new RandomGenerator();
        foreach ($events as $event) {
            if (!static::get()->filter('Event', $event)->first()) {
                $hook = IncomingWebhook::create([
                    'Title' => $event,
                    'Event' => $event,
                    'Key' => $generator->randomToken('sha1'),
                ]);
                $hook->write();
                DB::alteration_message(sprintf('Added new incoming webhook for "%s"', $event), 'created');
            }
        }
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return Path::join(
            Director::absoluteBaseURL(),
            IncomingWebhookController::config()->get('url_segment'),
            $this->Key
        );
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

}
