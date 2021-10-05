<?php


namespace SilverStripe\Headless\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

class PublishEvent extends DataObject
{
    const STATUS_QUEUED = 'QUEUED';
    const STATUS_PROGRESS = 'IN PROGRESS';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILURE = 'FAILURE';

    private static $db = [
        'Duration' => 'Int',
        'Status' => "Enum(
            '" . self::STATUS_QUEUED . "," . self::STATUS_PROGRESS . "," . self::STATUS_SUCCESS . "," . self::STATUS_FAILURE ."',
            '" . self::STATUS_QUEUED .
        "')"
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Items' => PublishQueueItem::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Status' => 'Status',
        'Created' => 'Publication date',
        'Items.Count' => 'Number of items',
        'NiceDuration' => 'Duration',
    ];

    /**
     * @var string
     */
    private static $table_name = 'PublishEvent';

    /**
     * @var string
     */
    private static $singular_name = 'Publish Event';

    /**
     * @var string
     */
    private static $plural_name = 'Publish Events';

    /**
     * @var string
     */
    private static $default_sort = 'Created DESC';

    public function getNiceDuration(): string
    {
        $init = $this->Duration;
        if ($init == 0) {
            return "--";
        }
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        if ($minutes > 0) {
            return _t(
                __CLASS__ . '.NICEDURATIONMINUTES',
                '{minutes} minutes, {seconds} seconds',
                ['minutes' => $minutes, 'seconds' => $seconds]
            );
        }

        return _t(
            __CLASS__ . '.NICEDURATIONSECONDS',
            '{seconds} seconds',
            ['seconds' => $seconds]
        );

    }
    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS_CMSMain');
    }

}
