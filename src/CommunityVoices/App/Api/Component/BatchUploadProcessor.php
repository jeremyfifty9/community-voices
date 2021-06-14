<?php

 namespace CommunityVoices\App\Api\Component;
 use Symfony\Component\HttpFoundation;

 class BatchUploadProcessor {
     const ERR_NO_ATTRIBUTIONS = 'The source table must provide an "attribution" column';
     const ERR_NO_CONTENT_CATEGORIES = 'The quotes table must provide a "content category 1" column';
     const ERR_NO_IDENTIFIER = 'Both sheets must provide an "identifier" column';
     const ERR_NO_QUOTE = 'The quotes sheet must provide a "edited quotes" column';
     const ERR_MISSING_ATTRIBUTION = 'Quotes must have an attribution.';
     const ERR_MISSING_CONTENT_CATEGORY = 'Quote must provide at least one content category.';
     const WARNING_EMPTY_QUOTE = "Warning! This quote is empty";

     const BATCH_SOURCE_DATA = [
         'Identifier',
         'Source Type',
         'Interviewer/Researcher',
         'Interviewee or Source Document',
         'Interview or File Access Date',
         'Attribution',
         'Sub-Attribution',
         'Organization',
         'Sponsor Organization',
         'Topic/Theme of Interview',
         'URL Source Document',
         'Interviewee Email',
         'Interviewee Telephone',
         'URL Interview Consent',
         'URL T1 Survey',
         'URL T2 Survey',
         'URL Interview Transcription',
         'URL Article',
         'Date Article Approved by Interviewee',
         'URL Photograph Interviewee'
     ];

     const BATCH_QUOTE_DATA = [
         'Identifier',
         'Original Quote',
         'Edited Quotes',
         'Quotation Marks',
         'Suggested Photo Source',
         'Suggested Photo in CV',
         'Content Category 1',
         'Content Category 2',
         'Content Category 3',
         'Tag 1',
         'Tag 2',
         'Tag 3',
         'Tag 4',
         'Tag 5',
         'Create a Slide'
     ];

     public function tailRead($filepath, $lines, $endLine = PHP_INT_MAX, $startDate = false, $endDate = false) {
         // from error page
     }
     public function csvReadBatch($sourceFilePath, $quoteFilePath) {
         $columnNameErrors = [];
         $columnNameWarnings = ["unrecognized" => [], "expected" => []];
         $unpairedQuotes = [];
         $validIdentifiers = ["allIdentifiers" => []]; // allow selection for unpaired quotes
         $formattedSourceNames = array_map(array($this,'cleanString'),self::BATCH_SOURCE_DATA);
         $formattedQuoteNames = array_map(array($this,'cleanString'),self::BATCH_QUOTE_DATA);
         $sourceNameMapper = [];
         $quoteNameMapper = [];
         for ($i = 0; $i < count($formattedSourceNames); $i++) {
             $sourceNameMapper[$formattedSourceNames[$i]] = self::BATCH_SOURCE_DATA[$i];
         }

         $quoteNameMapper = [];
         for ($i = 0; $i < count($formattedQuoteNames); $i++) {
             $quoteNameMapper[$formattedQuoteNames[$i]] = self::BATCH_QUOTE_DATA[$i];
         }

         // both of these mapper arrays are required in order to associate a formatted column with its original column to display original column name on page


         // There will be errors/warnings on three levels: top level (column names), source level (relating to source info), quotes level (relating to quotes info)
         // any errors on the top level will require a re upload

         // NOTE: Skip the second row of the source file due to format given by John
         // first pass through source sheet, creating entry for each interview. Later we will add list of quotes for each interview
         if (($f = fopen($sourceFilePath, "r")) !== FALSE) {
           $columnOrder = []; // used to track column locations since we are going entirely by name instead of order
           $givenColumnNames = fgetcsv($f);
           foreach ($givenColumnNames as $column) {
               $formattedColumn = $this->cleanString($column);
               if(in_array($formattedColumn,$formattedSourceNames)) {
                   array_push($columnOrder,$formattedColumn);
               } else {
                   array_push($columnOrder, "unrecognized");
                   array_push($columnNameWarnings["unrecognized"],["item" => [$column]]); // NOTE: use this format with "item" to make it easier to call card.xslt (unless you want to change that)
               }
           }

           for($i = 0; $i < count($formattedSourceNames); $i++) {
               $formattedSourceName = $formattedSourceNames[$i];
               $unformattedSourceName = self::BATCH_SOURCE_DATA[$i];
               if (!in_array($formattedSourceName,$columnOrder))
                 array_push($columnNameWarnings["expected"],["item" => [$unformattedSourceName]]);
           }

           if(!in_array("attribution",$columnOrder)) array_push($columnNameErrors,["item" => [self::ERR_NO_ATTRIBUTIONS]]);
           if(!in_array("identifier",$columnOrder)) array_push($columnNameErrors,["item" => [self::ERR_NO_IDENTIFIER]]);

           // These are both major errors. There is no need to parse the rest of the sheet as uploading will not be allowed if one of these errors occurs

           $sheetData = [];
           if (empty($columnNameErrors))  {
              fgetcsv($f);
               while (($data = fgetcsv($f)) !== FALSE) {
                   $dataToAdd = ['rowData' => []];
                   $identifier = false;
                   for ($i = 0; $i < count($columnOrder); $i++) {
                       $columnName = $columnOrder[$i]; // XML requires no spaces
                       $currentColumnData = $this->replaceTextInTags($data[$i]);
                       if($columnName != "unrecognized") {
                           $originalName = $sourceNameMapper[$columnName];
                           if($columnName=="identifier") {
                               $identifier = "i" . $this->cleanString($currentColumnData); // identifier must start with a letter for xml parse so this takes care of that
                               if(!empty($identifier)) array_push($validIdentifiers["allIdentifiers"],["item" => $identifier]);
                           } else {
                               $dataToAdd['rowData'][$columnName] = ["originalName" => $originalName, "columnData" => $currentColumnData, "formattedName" => $columnName];
                           }
                       }
                   }
                   if($identifier) {
                       $dataToAdd['rowData']["attribution"]["error"] = self::ERR_MISSING_ATTRIBUTION;
                       $sheetData[$identifier] = $dataToAdd;
                       $sheetData[$identifier]["quotes"] = []; // allows quotes related to source info
                   }
               }
           }

           fclose($f);
        }

         if (($f= fopen($quoteFilePath, "r")) !== FALSE) {
             $columnOrder = []; // used to track column locations since we are going entirely by name instead of order
             $givenColumnNames = fgetcsv($f);
             foreach ($givenColumnNames as $column) {
                 $formattedColumn = $this->cleanString($column);
                 if(in_array($formattedColumn,$formattedQuoteNames)) {
                     array_push($columnOrder,$formattedColumn);
                 } else {
                     array_push($columnOrder,"unrecognized");
                     array_push($columnNameWarnings["unrecognized"],["item" => [$column]]);
                 }
             }

             for($i = 0; $i < count($formattedQuoteNames); $i++) {
                 $formattedQuoteName = $formattedQuoteNames[$i];
                 $unformattedQuoteName = self::BATCH_QUOTE_DATA[$i];
                 if (!in_array($formattedQuoteName,$columnOrder))
                   array_push($columnNameWarnings["expected"],["item" => [$unformattedQuoteName]]);
             }

             if(!in_array("contentcategory1",$columnOrder)) array_push($columnNameErrors,["item" => [self::ERR_NO_CONTENT_CATEGORIES]]);
             if(!in_array("editedquotes",$columnOrder)) array_push($columnNameErrors,["item" => [self::ERR_NO_QUOTE]]);
             if(!in_array("identifier",$columnOrder)) array_push($columnNameErrors,["item" => [self::ERR_NO_IDENTIFIER]]);
             // All three of these are major errors. There is no need to parse the rest of the sheet as uploading will not be allowed if one of these errors occurs

             if (empty($columnNameErrors))  {
                 while (($data = fgetcsv($f)) !== FALSE) {
                     $dataToAdd = ["contentcategories" => ["formattedName" => "contentcategories", "all" => [], "error" => null], "tags" => ["formattedName" => "tags", "all" => []]];
                     $identifier = false;
                     for ($i = 0; $i < count($columnOrder); $i++) {
                         $columnName = str_replace(" ","",$columnOrder[$i]); // XML requires no spaces
                         $currentColumnData = $this->replaceTextInTags($data[$i]);
                         if($columnName != "unrecognized") {
                             $originalName = $quoteNameMapper[$columnName];
                             if($columnName=="identifier") {
                                if(array_key_exists($this->cleanString("i" . $currentColumnData),$sheetData)) $identifier = "i" . $this->cleanString($currentColumnData); // if valid identifier
                            } else if (str_contains($columnName,"contentcategory")) { // need to process content categories and tags differently from other things
                                array_push($dataToAdd["contentcategories"]["all"],["columnData" => $currentColumnData]);
                            } else if (str_contains($columnName,"tag"))
                                array_push($dataToAdd["tags"]["all"],["columnData" => $currentColumnData]);
                             else {
                                $dataToAdd[$columnName] = ["originalName" => $originalName, "columnData" => $currentColumnData, "formattedName" => $columnName];
                             }
                         }
                     }

                    $dataToAdd['contentcategories']['error'] = self::ERR_MISSING_CONTENT_CATEGORY; // error checking of both of these is entirely in jquery -- these are just placeholders for possible errors and warnings
                    $dataToAdd['editedquotes']['warning'] = self::WARNING_EMPTY_QUOTE;

                     if($identifier===false) {
                         $quoteNumber = count($unpairedQuotes) + 1;
                          array_push($unpairedQuotes,["item" => ["quoteNumber" => $quoteNumber, "rowData" => $dataToAdd]]);
                     } else {
                         $quoteNumber = count($sheetData[$identifier]['quotes']) + 1;
                         array_push($sheetData[$identifier]["quotes"],["item" => ["quoteNumber" => $quoteNumber, "rowData" => $dataToAdd]]);
                     }
                 }
             }
         }
         return [$sheetData,$columnNameWarnings,$columnNameErrors,$unpairedQuotes,$validIdentifiers];
     }
     private function cleanString($s) {
         return strtolower(preg_replace(["/[^a-zA-Z0-9]/","/\s/"], "", $s));
     }
     private function replaceTextInTags($s) {
         return preg_replace("/<(.+?)>/","",$s);
     }
 }
