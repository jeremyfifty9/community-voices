<?php

namespace CommunityVoices\Model\Entity;

use CommunityVoices\Model\Contract\StateObserver;

class Image extends Media
{
    private $filename; /* @TODO required */

    private $title;
    private $description;
    private $generatedTags;

    private $dateTaken;
    private $photographer;
    private $organization;

    public function __construct()
    {
        $this->type = self::TYPE_IMAGE;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getGeneratedTags()
    {
        return $this->generatedTags;
    }

    public function setGeneratedTags($generatedTags)
    {
        $this->generatedTags = $generatedTags;
    }

    public function getDateTaken()
    {
        return $this->dateTaken;
    }

    public function setDateTaken($dateTaken)
    {
        $input = (int) $dateTaken;

        if ($input > 0) {
            $this->dateTaken = $input;
        }
    }

    public function getPhotographer()
    {
        return $this->photographer;
    }

    public function setPhotographer($photographer)
    {
        $this->photographer = $photographer;
    }

    public function getOrganization()
    {
        return $this->organization;
    }

    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /*
    public function validateForUpload(StatusObserver $notifier)
    {
        $isValid = true;

        // @TODO after discussion
    }
    */
}
