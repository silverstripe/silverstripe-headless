<?php


namespace SilverStripe\Headless\Services;


use SilverStripe\Control\HTTPRequest;

interface TokenValidator
{
    public function validate(HTTPRequest $request): bool;
}
