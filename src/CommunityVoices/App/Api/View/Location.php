<?php

namespace CommunityVoices\App\Api\View;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\App\Api\Component;
use Symfony\Component\HttpFoundation;

class Location extends Component\View
{
    public function __construct(
        Component\SecureContainer $secureContainer,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($secureContainer, $mapperFactory);
    }

    protected function getAllLocation()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject("locationLookup");
        $locationCollection = $stateObserver->getEntry("locationCollection")[0];

        $response = new HttpFoundation\JsonResponse($locationCollection->toArray());

        return $response;
    }
}
