<?php

namespace CommunityVoices\App\Api\Component\Exception;

class AccessDenied extends \Exception
{
    public const LOGGED_IN_MESSAGE = "Check the permissions of your user account.";
    public const LOGGED_OUT_MESSAGE = "Please log in.";

    // Our message is constructed by the constructor itself.
    // We simply need an identity in order to establish what exactly
    // we are going to pass along as the message.
    public function __construct($identity)
    {
        $message = "You are not allowed to view this page.";

        if ($identity) {
            $message .= "  " . self::LOGGED_IN_MESSAGE;
        } else {
            $message .= "  " . self::LOGGED_OUT_MESSAGE;
        }

        // Our exception code will always be 0 (the second argument),
        // and our previous exception will always be null (the third argument).
        // These are the default values.
        parent::__construct($message, 0, null);
    }
}
