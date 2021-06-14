<?php

// Set-up
use CommunityVoices\App\Website\Bootstrap;

// Timezone
// @config
date_default_timezone_set('America/New_York');

// Error settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Composer autoloading
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/CommunityVoices/App/Website/db.php';

if (! (count($argv) == 1 || isTimestamp($argv[1]))) {
    echo "Usage: ./migrate for all valid migrations before or at NOW\n ./migrate TIMESTAMP for all migrations before or at TIMESTAMP\n";
    exit();
} else {
    $currentDateTime = date('Y-m-d H:i:s'); // format for SQL datetime

    $checkForExistanceOfMigrationTable = "SELECT * from `community-voices_migrations` LIMIT 1";
    try {
        $statement = $dbHandler->prepare($checkForExistanceOfMigrationTable);
        $statement->execute();
        $statement->fetch();
        $migrationsTableExists = true;
    } catch(Exception $e) {
        $migrationsTableExists = false;
    }
    
    if (! $migrationsTableExists) {
        require __DIR__ . "/createMigrationsTable.php"; // force creation of migration table before running other migrations
    }

    $migrationDate = count($argv) == 1 ? strtotime("now") : strtotime($argv[1]);
    $sortedFileNamesByDate = [];
    $dir = opendir('./scripts'); 
    while(false != ($file = readdir($dir))) {
        if(($file != ".") and ($file != "..")) {
                $sortedFileNamesByDate[] = $file; // put in array.
        }   
    }

    natsort($sortedFileNamesByDate);

    // filter out scripts that don't have timestamp.
    $sortedFileNamesByDate = array_filter($sortedFileNamesByDate,function($val){
        $timestampPortion = strtok($val,'_');
        return isTimestamp($timestampPortion);
    });

    $sortedFileNamesOnlyDate = array_map(function($val) {
        return strtok($val,'_');
    },$sortedFileNamesByDate);

    // get list of all migrations
    $allMigrationsRecordQuery = "SELECT * from `community-voices_migrations`";
    $statement = $dbHandler->prepare($allMigrationsRecordQuery);
    $statement->execute();
    $allMigrationsRan = array_values(array_map(function($record) {
        return $record['script_name'];
    },$statement->fetchAll()));

    // get last migration in case there are errors later to revert back to this
    $lastMigration = $dbHandler->lastInsertId();

    # Need to ask cv-mysql to generate the dump.
    shell_exec('mysqldump -h cv-mysql community_voices > tmp.sql');

    try {
        // run all migrations before specified date
        $currentPossibleMigration = 0;

        while ($sortedFileNamesOnlyDate[$currentPossibleMigration] <= $migrationDate) {
            // check if migration was already run (exists in migration table)
            if(! in_array($sortedFileNamesByDate[$currentPossibleMigration],$allMigrationsRan)) {
                echo "Running Migration" . $sortedFileNamesByDate[$currentPossibleMigration] . "\n";
                addMigrationToTable($dbHandler,$sortedFileNamesByDate[$currentPossibleMigration],date('Y-m-d H:i:s'));
            }
            if($currentPossibleMigration == count($sortedFileNamesOnlyDate) - 1) {
                break;
            }
            $currentPossibleMigration++;
        } 
    } catch (\Throwable $th) {
        shell_exec("mv tmp.sql schema.sql");
        shell_exec("docker exec -i cv-mysql mysql community_voices < schema.sql");
        shell_exec("rm tmp.sql");
    }
}

function addMigrationToTable($dbHandler,$name,$dateTime){
    $InsertIntoMigrationsTable = "INSERT INTO 
        `community-voices_migrations`
        (script_name,datetime_executed)
        VALUES (:name, :dateTime)";
    $statement = $dbHandler->prepare($InsertIntoMigrationsTable);
    $statement->bindValue(':name',$name);
    $statement->bindValue(':dateTime',$dateTime);
    $statement->execute();

    echo "executed migration...\n";
}

function isTimestamp($timestamp) {
    if(ctype_digit($timestamp) && strtotime(date('Y-m-d H:i:s',$timestamp)) === (int)$timestamp) {
        return true;
    } else {
        return false;
    }
}