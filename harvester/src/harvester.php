<?php
/**
 * A simmple CLI OAI PHM Harvester
 *
 * @author    Christian Mahnke <cmahnke@gmail.com>
 * @copyright 2023-2023 Christian Mahnke
 * @license   https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License
 * @link      https://github.com/cmahnke/oai-harvester-docker
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use webignition\Guzzle\Middleware\HttpAuthentication\AuthorizationType;
use webignition\Guzzle\Middleware\HttpAuthentication\AuthorizationHeader;
use webignition\Guzzle\Middleware\HttpAuthentication\CredentialsFactory;
use webignition\Guzzle\Middleware\HttpAuthentication\HostComparer;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use Symfony\Component\Yaml\Yaml;
use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;
use Symfony\Component\Filesystem\Path;

$config = Yaml::parseFile(__DIR__ . '/../config.yaml');

$stack = HandlerStack::create();
$stack->push(GuzzleRetryMiddleware::factory());

if (getenv("OAI_USER")) {
    $config['user'] = getenv("OAI_USER");
}

if (getenv("OAI_PASS")) {
    $config['pass'] = getenv("OAI_PASS");
}

if (getenv("VERBOSE")) {
    $config['verbose'] = getenv("VERBOSE");
}

if (trim($config['user']) != '' && trim($config['pass']) != '') {
    $httpAuthenticationMiddleware = new HttpAuthenticationMiddleware(new HostComparer());
    $credentials = CredentialsFactory::createBasicCredentials(trim($config['user']), trim($config['pass']));
    $httpAuthenticationMiddleware->setType(AuthorizationType::BASIC);
    $httpAuthenticationMiddleware->setCredentials($credentials);
    $httpAuthenticationMiddleware->setHost(parse_url($config['oai_url'], PHP_URL_HOST));
    $stack->push($httpAuthenticationMiddleware, 'http-auth');
}

$guzzleClient = new GuzzleClient(['handler' => $stack]);
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter($guzzleClient);

// class ErrorReportingClient extends Client {
// protected function decodeResponse( $resp ): SimpleXMLElement {
// try {
// return parent::decodeResponse( $resp );
// }
// catch ( MalformedResponseException $exception ) {
// error_log( 'MalformedResponseException: ' . $resp );
// throw $exception;
// }
// }
// }

$client = new Client($config['oai_url'], $guzzleAdapter);
// $harvester = new Endpoint(new ErrorReportingClient($config['oai_url'], $guzzleAdapter));
$harvester = new Endpoint($client);
// $path = Path::join(__DIR__ , $config['target_dir']);
$path = $config['target_dir'];
print "Saving to " . $path . "\n";

foreach ($harvester->listRecords($config['metadata_prefix'], null, null, $config['set']) as $record) {
    $filename = Path::join($path, $record->header->identifier . '.xml');
    $domrecord = new DOMDocument('1.0', 'UTF-8');
    $domrecord->loadXML($record->asXML());
    $xpath = new DOMXpath($domrecord);
    $xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->appendChild($doc->importNode($xpath->query('//oai:metadata/*[1]')->item(0), true));
    $doc->save($filename);
    if (array_key_exists($config, 'verbose')) {
        print "Saving " . $record->header->identifier . "\n";
    } else {
        print '.';
    }
}
