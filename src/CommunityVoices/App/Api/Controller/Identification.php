<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\Model\Entity;
use CommunityVoices\App\Api\Component;

class Identification extends Component\Controller
{
    protected $recognitionAdapter;

    public function __construct(
        Component\Contract\CanIdentify $identifier,
        \Psr\Log\LoggerInterface $logger,

        Component\RecognitionAdapter $recognitionAdapter
    ) {
        parent::__construct($identifier, $logger);

        $this->recognitionAdapter = $recognitionAdapter;
    }

    protected function CANgetIdentity($user)
    {
        return $user->isRoleAtLeast(Entity\User::ROLE_GUEST);
    }

    protected function getIdentity($request)
    {
    }

    protected function CANpostLogin($user)
    {
        return $user->isRoleAtLeast(Entity\User::ROLE_GUEST);
    }

    /**
     * User authentication
     */
    protected function postLogin($request)
    {
        $email    = $request->request->get('email');
        $password = $request->request->get('password');
        $remember = $request->request->get('remember') === 'on';

        $this->recognitionAdapter->authenticate($email, $password, $remember);
    }

    protected function CANpostLogout($user)
    {
        return $user->isRoleAtLeast(Entity\User::ROLE_USER);
    }

    protected function postLogout($request)
    {
        $this->recognitionAdapter->logout();
    }
}
