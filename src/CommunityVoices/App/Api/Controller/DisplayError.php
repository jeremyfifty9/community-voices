<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\App\Api\Component;

class DisplayError extends Component\Controller
{
    const ERRORSLOGPATH = '/var/www/html/log/access.log';

    protected function getError()
    {

    }
    protected function getErrors($request,$lines=500)
    {
        $fileProcessor = new Component\FileProcessor();
        $dates = empty($request->query->get('dateRange')) ? [false,false] : explode(" - ",$request->query->get('dateRange'));
        $linePos = empty($request->query->get('linePos')) ? PHP_INT_MAX : intval($request->query->get('linePos'));
        $numLinesToAdd = intval($request->query->get('numLines') ?? 500);
        return $fileProcessor->tailRead(self::ERRORSLOGPATH,$numLinesToAdd,$linePos,$dates[0],$dates[1]);

    }

}
