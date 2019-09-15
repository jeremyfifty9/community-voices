<?php

namespace CommunityVoices\App\Api\AccessControl;

use CommunityVoices\Model\Component\StateObserver;
use CommunityVoices\Model\Entity;
use CommunityVoices\App\Api\Component\Contract;
use CommunityVoices\App\Api\Component\AccessController;

class Article extends AccessController
{
    public function __construct(
        Contract\CanIdentify $identifier,
        \Psr\Log\LoggerInterface $logger,
        StateObserver $stateObserver
    ) {
        parent::__construct($identifier, $logger, $stateObserver);
    }

    public function getArticle()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_GUEST);
    }

    public function getAllArticle()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_GUEST);
    }

    public function getArticleUpload()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_MANAGER);
    }

    public function postArticleUpload()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_MANAGER);
    }

    public function getArticleUpdate()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_MANAGER);
    }

    public function postArticleUpdate()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_MANAGER);
    }

    public function searchByStatus()
    {
        return $this->getUserEntity()->isRoleAtLeast(Entity\User::ROLE_MANAGER);
    }
}
