<?php


namespace SilverStripe\Headless\Dev;


use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

class HeadlessAdmin extends Controller
{
    private static $url_segment = '/dev/generate-included-classes';

    private static $allowed_actions = [
        'Form',
    ];

    protected function init()
    {
        parent::init();
        if (!Director::isDev()) {
            return $this->httpError(404);
        }
        if (!Permission::check('ADMIN')) {
            return Security::permissionFailure($this, 'This tool requires admin permissions');
        }
        if (Director::is_cli()) {
            throw new \RuntimeException('This tool requires a browser');
        }
    }

    public function index(HTTPRequest $request)
    {
        return $this->Form()->forTemplate();
    }

    public function Form()
    {
        $manifest = [];
        $fields = FieldList::create();
        foreach (ClassInfo::subclassesFor(DataObject::class, false) as $class) {
            $parts = explode('\\', $class);
            $name = array_pop($parts);
            $section = implode('\\', $parts);
            if (!isset($manifest[$section])) {
                $manifest[$section] = [];
            }
            $manifest[$section][] = $name;
        }

        foreach ($manifest as $section => $classes) {
            $fields->push(CheckboxField::create($section . '\\*', $section . '\\* (include all below)'));
            foreach ($classes as $class) {
                $fields->push(
                    CheckboxField::create($section . '\\' . $class, $class)
                        ->setAttribute('style', 'margin-left: 30px')
                );
            }
        }

        return Form::create(
            $this,
            __FUNCTION__,
            $fields,
            FieldList::create(FormAction::create('doSubmit', 'Generate'))
        );
    }


    public function doSubmit(array $data)
    {
        $vals = array_filter(array_keys($data), function ($field) {
            return !in_array($field, [SecurityToken::inst()->getName(), 'action_doSubmit']);
        });
        $lines = implode("<br>    - ", $vals);
        $ret = <<<HTML
<pre>
SilverStripe\Headless\GraphQL\ModelLoader:
  included_dataobjects:
    - $lines
</pre>
HTML;

        return $ret;
    }
}
