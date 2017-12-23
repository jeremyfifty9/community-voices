<?php

namespace CommunityVoices\App\Website\Controller;

use CommunityVoices\Model\Service;
use CommunityVoices\App\Website\Component;
use CommunityVoices\App\Api;
use Fracture\Http;

class User
{
    protected $recognitionAdapter;

    protected $userAPIController;
    protected $userAPIView;

    protected $secureContainer;

    public function __construct(
        Component\RecognitionAdapter $recognitionAdapter,
        Api\Controller\User $userAPIController,
        Api\Component\SecureContainer $secureContainer
    ) {
        $this->recognitionAdapter = $recognitionAdapter;

        $this->userAPIController = $userAPIController;

        $this->secureContainer = $secureContainer;
    }

    public function getProfile($request)
    {
    }

    public function getProtectedPage($request)
    {
        $this->secureContainer->contain($this->userAPIController);
        $this->secureContainer->postUser($request);
    }
}
