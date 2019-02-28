<?php

namespace SilverStripe\MFA;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\MFA\Method\Handler\RegisterHandlerInterface;
use SilverStripe\MFA\Store\StoreInterface;

/**
 * Handles registration processing for the Math Method.
 */
class MethodRegisterHandler implements RegisterHandlerInterface
{
    /**
     * Prepare to register this authentication method against a member by initialising state in session and generating
     * details to provide to a frontend React component
     *
     * @param StoreInterface $store An object that hold session data (and the Member) that can be mutated
     * @return array Props to be passed to a front-end React component
     */
    public function start(StoreInterface $store)
    {
        // Stub
        return [];
    }

    /**
     * Confirm that the provided details are valid, and create a new RegisteredMethod against the member.
     *
     * @param HTTPRequest $request
     * @param StoreInterface $store
     * @return bool
     */
    public function register(HTTPRequest $request, StoreInterface $store)
    {
        // Stub
        return true;
    }

    /**
     * Provide a string (possibly passed through i18n) that names this registration method.
     *
     * eg. "Authenticator app"
     *
     * @return string
     */
    public function getName()
    {
        return 'Basic math test';
    }

    /**
     * Provide a string (possibly passed through i18n) that describes this method.
     *
     * eg. "Verification codes are created by an app on your phone"
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Asks you to add numbers together';
    }

    /**
     * Provide a URL to a support article about this registration method.
     *
     * @return string
     */
    public function getSupportLink()
    {
        return 'https://google.com';
    }
}
