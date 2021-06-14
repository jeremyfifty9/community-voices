<?php

namespace CommunityVoices\Model\Entity;

use CommunityVoices\Model\Contract\FlexibleObserver;

class Image extends Media
{
    CONST ERR_INVALID_FILE_FORMAT = "File must have an image format!";
    private $filename; /* @TODO required */

    private $title;
    private $description;
    private $generatedTags;
    private $dateTaken;
    private $photographer;
    private $organization;

    private $cropRect = ['x' => 0, 'y' => 0, 'height' => 0, 'width' => 0];

    private $relatedSlide;

    public $type;

    private $metaData;

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
        if (is_numeric($dateTaken)) {
            $input = (int) $dateTaken;
        } else {
            $input = strtotime($dateTaken);
        }

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

    public function getCropRect()
    {
        return $this->cropRect;
    }

    public function setRelatedSlide($slideId)
    {
        $this->relatedSlide = $slideId;
    }

    public function setCropRect($rect)
    {
        if (is_array($rect)) {
            $this->cropRect = $rect;
        } elseif (is_string($rect)) {
            $parts = explode(',', $rect);
            $this->cropRect = [
                'x' => (int) $parts[0],
                'y' => (int) $parts[1],
                'height' => (int) $parts[2],
                'width' => (int) $parts[3]
            ];
        }
    }

    public function setMetaData($metaData,$validMetaData) {
        $this->metaData = array_filter($metaData,function($key) use ($validMetaData) {
            return in_array($key,$validMetaData);
        },ARRAY_FILTER_USE_KEY);
    }

    public function getMetaData() {
        return $this->metaData;
    }

    public function toArray()
    {
        return ['image' => array_merge(parent::toArray()['media'], [
            'filename' => $this->filename,
            'title' => $this->title,
            'description' => $this->description,
            'generatedTags' => $this->generatedTags,
            'dateTaken' => date('Y-m-d H:i:s', $this->dateTaken),
            'photographer' => $this->photographer,
            'organization' => $this->organization,
            'crop_x' => $this->cropRect['x'],
            'crop_y' => $this->cropRect['y'],
            'crop_height' => $this->cropRect['height'],
            'crop_width' => $this->cropRect['width'],
            'relatedSlide' => $this->relatedSlide
        ])];
    }

    public function validateForUpload(FlexibleObserver $stateObserver)
    {
        $isValid = true;

        // add cases where it wouldn't be valid!!

        return $isValid;
    }
}
