<?php

namespace CommunityVoices\Model\Entity;

use CommunityVoices\Model\Contract\FlexibleObserver;

class Article extends Media
{
    const ERR_AUTHOR_REQUIRED = 'Articles must have an author.';

    private $text;
    private $html;
    private $title;
    private $image;
    private $author;
    private $dateRecorded;
    private $dateFormat;

    public $type;

    public function __construct()
    {
        $this->type = self::TYPE_ARTICLE;
        $this->dateFormat = "Y-m-d H:i:s";
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($text)
    {
        $this->title = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function setHtml($html)
    {
        $this->html = $html;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(Image $image)
    {
        $this->image = $image;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getDateRecorded()
    {
        return $this->dateRecorded;
    }

    public function setDateRecorded($dateRecorded)
    {
        $this->dateRecorded = strtotime($dateRecorded);
    }

    public function setDateFormat($fmt)
    {
        $this->dateFormat = $fmt;
    }

    public function validateForUpload(FlexibleObserver $stateObserver)
    {
        $isValid = true;

        if (!$this->author || empty($this->author)) {
            $isValid = false;
            $stateObserver->addEntry('author', self::ERR_AUTHOR_REQUIRED);
        }

        return $isValid;
    }

    public function toArray()
    {
        return ['article' => array_merge(parent::toArray()['media'], [
            'title' => $this->title,
            'text' => $this->text,
            'html' => $this->html,
            'author' => $this->author,
            'dateRecorded' => date($this->dateFormat, $this->dateRecorded),
            'image' => $this->image->getId()
        ])];
    }
}
