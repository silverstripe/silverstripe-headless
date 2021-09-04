<?php


namespace SilverStripe\Headless\Services;


use Firebase\JWT\JWT;
use SilverStripe\Control\HTTPRequest;

class NetlifyTokenValidator implements TokenValidator
{
    private $secret;

    /**
     * @param string $secret
     */
    public function construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function validate(HTTPRequest $request): bool
    {
        $signature = $request->getHeader('X-Webhook-Signature');
        if (!$signature) {
            return false;
        }
        $decoded = JWT::decode($signature, $this->secret, ['HS256']);
        var_dump($decoded);

        return true;
    }
}
