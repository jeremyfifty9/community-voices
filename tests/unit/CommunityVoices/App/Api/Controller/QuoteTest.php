<?php

namespace CommunityVoices\App\Api\Controller;

use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
  public function test_Post_Quote_Upload() 
  {
      $text = 'I always close my eyes when I pee!';
      $attribution = 'Lars Dreith';
      $subAttribution = 'Oberlin College 2020';
      $dateRecorded = 'January 24th, 2018';
      $publicDocumentLink = '';
      $sourceDocumentLink = '';

      $requestBuilder = new \Fracture\Http\RequestBuilder;

      $request = $requestBuilder->create([
          'get' => [
              'text' => $text,
              'attribution' => $attribution,
              'subAttribution' => $subAttribution,
              'dateRecorded' => $dateRecorded,
              'publicDocumentLink' => $publicDocumentLink,
              'sourceDocumentLink' => $sourceDocumentLink
          ]
      ]);

      $quoteUpload = $this->createMock(Service\QuoteUpload::class);

      $quoteUpload
          ->expects($this->once())
          ->method('newQuote')
          ->with($this->equalTo($text),
                  $this->equalTo($attribution),
                  $this->equalTo($subAttribution),
                  $this->equalTo($dateRecorded),
                  $this->equalTo($publicDocumentLink),
                  $this->equalTo($sourceDocumentLink));

      $quoteController = new Quote($quoteUpload);

      $quoteController->postQuote($request);
  }
}
