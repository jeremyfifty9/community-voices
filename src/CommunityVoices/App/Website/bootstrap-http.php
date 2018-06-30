<?php

/**
 * @codeCoverageIgnore
 * @overview Bootstraps the application together
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use CommunityVoices\App\Website;
use CommunityVoices\Model;

/**
 * Set time zone
 */
date_default_timezone_set('America/New_York');

require __DIR__ . '/../../../../vendor/autoload.php';

$production_server = (getenv('SERVER') === 'environmentaldashboard');

/**
 * Injector
 */
$injector = new Auryn\Injector;

/**
 * Db handler configuration
 */
$dbHandler = new PDO('mysql:host='.getenv('MYSQL_HOST').';dbname='.getenv('MYSQL_DB').';charset=utf8;port=3306', getenv('MYSQL_USER'), getenv('MYSQL_PASS'));
$dbHandler->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$injector->share($dbHandler);

/**
 * Create and share log (required by Palladium)
 */

$logger = new Monolog\Logger('name');
$logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/../../../../log/access.log'));

$injector->share($logger);

$injector->alias('Psr\Log\LoggerInterface', 'Monolog\Logger');

/**
 * Import routes
 */

$locator = new Symfony\Component\Config\FileLocator(array(__DIR__ . '/Config'));
$loader = new Symfony\Component\Routing\Loader\PhpFileLoader($locator);

$routes = $loader->load('Routes.php');

/**
 * Routing the request
 */

$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

$context = new Symfony\Component\Routing\RequestContext();
$context->fromRequest($request);

$matcher = new Symfony\Component\Routing\Matcher\UrlMatcher($routes, $context);

$uri = isset($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI']
            : '/';

$uri = ($production_server) ? '/community-voices' . substr(explode('?', $uri)[0], 1) : explode('?', $uri)[0]; // TODO: fix!

$parameters = new Symfony\Component\HttpFoundation\ParameterBag($matcher->match($uri));

$request->attributes = $parameters;

/**
 * Create and share mapper factories
 */

$websiteMapperFactory = new Website\Component\MapperFactory($request);
$modelMapperFactory = new Model\Component\MapperFactory($dbHandler);
$pdMapperFactory = new Palladium\Component\MapperFactory($dbHandler, '`community-voices_identities`');

$injector->share($websiteMapperFactory);
$injector->share($modelMapperFactory);
$injector->share($pdMapperFactory);

$injector->alias('Palladium\Contract\CanCreateMapper', 'Palladium\Component\MapperFactory');

/**
 * Create and share access arbiter
 */

$aclRaw = json_decode(file_get_contents(__DIR__  . '/../Api/Config/AccessControlList.json'), true);

$arbiter = new CommunityVoices\App\Api\Component\Arbiter($aclRaw['roles'], $aclRaw['rules']);

$injector->share($arbiter);

/**
 * Alias CanIdentify depdencies with the local recognition service
 */

$injector->alias('CommunityVoices\App\Api\Component\Contract\CanIdentify', 'CommunityVoices\App\Website\Component\RecognitionAdapter');

/**
 * Processing request
 */

$resource = $request->attributes->get('resource');
$action = $request->attributes->get('action');
$method = $request->attributes->get('method');

$controller = $injector->make("CommunityVoices\\App\\Website\\Controller\\" . $resource);
$controller->{$action}($request);

$view = $injector->make("CommunityVoices\\App\\Api\\View\\" . $resource);
$response = $view->{$action}($routes, $context);

$response->send();