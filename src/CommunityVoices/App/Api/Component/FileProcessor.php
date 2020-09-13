<?php

namespace CommunityVoices\App\Api\Component;
use Symfony\Component\HttpFoundation;

class FileProcessor {
    public function tailRead($filepath, $lines, $endLine = PHP_INT_MAX, $startDate = false, $endDate = false) {
            if($endLine < 0) return false; // dummy response indicating nothing was read
            $f = new \SplFileObject($filepath);

            // Jump to endline of range
            $f->seek($endLine);
            $currentLine = $f->key();

            // last line in file is blank, probably due to newline
            if($endLine == PHP_INT_MAX)
                $currentLine--;

            $errors = [];

            if($startDate == false && $endDate == false) {
                while ($currentLine >= 0 && $lines > 0) {
                    $f->seek($currentLine);
                    $line = $f->current();
                    $timeStamp = mb_substr($line,1,19);
                    $message = mb_substr($line,21);
                    $item = ['Time' => $timeStamp, 'Message' => $message, 'unixTime' => strtotime($timeStamp)];

                    $errors[] = $line;
                    $currentLine--;
                    $lines--;
                }
            } else {
                $startDate = strtotime($startDate);
                $endDate = strtotime($endDate);
                while($currentLine >= 0) {
                    $f->seek($currentLine);
                    $line = $f->current();
                    $timeStamp = mb_substr($line,1,19);
                    $unixTimeStamp = strtotime($timeStamp);
                    $message = mb_substr($line,21);
                    $item = ['Time' => $timeStamp, 'Message' => $message, 'unixTime' => $unixTimeStamp];

                    if($unixTimeStamp > $endDate) {
                        $currentLine --;
                    } else if ($unixTimeStamp >= $startDate) {
                        array_push($errors,$item);
                        $currentLine --;
                    } else {
                        break;
                    }
                }
            }

            $f = null; // closes file
            return [$errors,$currentLine];
    }
    // batch upload processor will go here
}
