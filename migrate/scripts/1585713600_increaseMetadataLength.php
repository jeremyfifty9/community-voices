<?php

// #3

$cols = [
    'source_type',
    'interviewee_or_source_document',
    'organization',
    'sponsor_organization',
    'topic',
    'interviewee_email',
    'interviewee_phone',
    'url_consent_interview',
    't1_survey',
    't2_survey',
    'url_transcription',
    'url_article',
    'date_article_approved',
    'url_photograph',
    'suggested_photo_source',
    'suggested_photo_in_cv'
];

foreach ($cols as $col) {
    $query = "
        ALTER TABLE `community-voices_oberlin_metadata`
        MODIFY COLUMN `${col}` varchar(255);
    ";

    echo $query;

    $statement = $dbHandler->prepare($query);
    $statement->execute();
}
