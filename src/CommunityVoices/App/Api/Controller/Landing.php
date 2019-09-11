<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\Model\Entity;
use CommunityVoices\Model\Service;

use CommunityVoices\App\Api\Component;

class Landing extends Component\Controller
{
    protected $slideLookup;
    protected $slideManagement;

    public function __construct(
        Component\Contract\CanIdentify $identifier,
        \Psr\Log\LoggerInterface $logger,
        \Auryn\Injector $injector,

        Service\SlideLookup $slideLookup,
        Service\SlideManagement $slideManagement
    ) {
        parent::__construct($identifier, $logger, $injector);

        $this->slideLookup = $slideLookup;
        $this->slideManagement = $slideManagement;
    }

    /**
     * Grabs all slides from databbase
     * @param  Request $request A request from the client's machine
     * @return SlideCollection  A collection of all slides in the database
     */
    protected function getLanding($request)
    {
        $this->slideLookup->findAll(
            0, // page
            5, // limit
            0, // offset
            'rand', // order
            '', // search
            null, // tags
            null, // photographers
            null, // orgs
            null, // attributions
            [1] // content category
        );
    }
}
