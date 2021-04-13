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

if (count($argv) < 2) {
    echo "You must input a script name as an argument.\n";
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
        require __DIR__ . "/scripts/createMigrationsTable.php";
        if (strcmp($argv[1],'createMigrationsTable')===0) {
            exit(); // avoid calling createMigrationsTable script twice
        }
    }

    try {
            require __DIR__ . "/scripts/{$argv[1]}.php";
            echo "ran migration $argv[1] succesfully\n";

            addMigrationToTable(
                $dbHandler,
                $argv[1],
                implode(",",array_slice($argv,2)),
                true,
                $currentDateTime);

    } catch (Exception $e) {
            addMigrationToTable(
                $dbHandler,
                $argv[1],
                implode(",",array_slice($argv,2)),
                false,
                $currentDateTime,
                $e->getMessage(),
                $e->getTraceAsString());

        echo "migration $argv[1] had errors! Check the migration table for more information.\n";
    }
}

# Need to ask cv-mysql to generate the dump.
shell_exec('mysqldump -h cv-mysql --no-data community_voices > migrate/schema.sql');

function addMigrationToTable($dbHandler,$name,$arguments,$success,$dateTime,$exceptionMessage=null,$exceptionTrace=null){
    $success = intval($success); // PDO nonsense
    $InsertIntoMigrationsTable = "INSERT INTO 
        `community-voices_migrations`
        (script_name,arguments,successful,datetime_executed,exception_message,exception_trace)
        VALUES (:name, :arguments, :success, :dateTime, :exceptionMessage, :exceptionTrace)";
    $statement = $dbHandler->prepare($InsertIntoMigrationsTable);
    $statement->bindValue(':name',$name);
    $statement->bindValue(':arguments',$arguments);
    $statement->bindValue(':success',$success);
    $statement->bindValue(':dateTime',$dateTime);
    $statement->bindValue('exceptionMessage',$exceptionMessage);
    $statement->bindValue('exceptionTrace',$exceptionTrace);
    $statement->execute();
}