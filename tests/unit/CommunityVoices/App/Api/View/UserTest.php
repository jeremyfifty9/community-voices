<?php

namespace CommunityVoices\App\Api\View;

use PHPUnit\Framework\TestCase;
use CommunityVoices\Model\Mapper;
use CommunityVoices\Model\Component;

class UserTest extends TestCase
{
    public function test_Post_User_Registration()
    {
        $stateMapper = $this->createMock(Mapper\ApplicationState::class);

        $stateMapper
            ->method('retrieve')
            ->will($this->returnValue(false));

        $mapperFactory = $this->createMock(Component\MapperFactory::class);

        $mapperFactory
            ->method('createClientStateMapper')
            ->will($this->returnValue($stateMapper));

        $userView = new User($mapperFactory);

        $this->assertTrue($userView->postUser(null));
    }
}
