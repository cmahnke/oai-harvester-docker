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

/**
 * Class ListRecordsByIdentifiers
 *
 * @implements \Iterator<\SimpleXMLElement>
 */
class ListRecordsByIdentifiers implements Iterator
{
    /**
     * The list of identifiers
     *
     * @var array<string> $_array
     */
    private array $_array;
    /**
     * The OAI PMH endpoint
     *
     * @var Endpoint $endpoint
     */
    private Endpoint $_endpoint;
    /**
     * The metadata prefix
     *
     * @var string $_metadataPrefix
     */
    private string $_metadataPrefix;

    /**
     * Constructs a new ListRecordsByIdentifiers
     * 
     * @param Endpoint      $endpoint
     * @param array<string> $identifiers
     * @param string        $metadataPrefix
     */
    public function __construct(Endpoint $endpoint, array $identifiers, string $metadataPrefix = "")
    {
        $this->_endpoint = $endpoint;
        $this->_array = $identifiers;
        $this->_metadataPrefix = $metadataPrefix;
    }

    /**
     * Returns the current Record
     *
     * @return \SimpleXMLElement An XML document corresponding to the record
     */
    public function current() : \SimpleXMLElement
    {
        $id = current($this->_array);
        return $this->_endpoint->getRecord($id, $this->_metadataPrefix);
    }
    /**
     * Move forward to next record
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->_array);
    }

    /**
     * Rewind the Iterator to the first record
     *
     *  @return void
     */    
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->_array);
    }

    /**
     * Return the key (identifier) of the current record
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return current($this->_array);
    }
   
    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->key() !== null;
    }
}

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

if (getenv("OAI_URL")) {
    $config['oai_url'] = getenv("OAI_URL");
}

if ($config['oai_url'] === null) {
    fwrite(STDERR, "No URL given!\n");
    exit(1);
}

if (array_key_exists('user', $config) && trim($config['user']) != '' && array_key_exists('pass', $config) && trim($config['pass']) != '') {
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
fwrite(STDERR, "Saving Set '" . $config['set'] ."', metadata prefix '" . $config['metadata_prefix'] . "' from " . $config['oai_url'] . " to " . $path . "\n");

if (array_key_exists('mode', $config) && $config['mode'] && strtolower($config['mode']) === strtolower('ListIdentifiers')) {
    $identifiers = iterator_to_array($harvester->listIdentifiers($config['metadata_prefix'], null, null, $config['set']));
    $iterator = new ListRecordsByIdentifiers($harvester, $identifiers);
} else {
    $iterator = $harvester->listRecords($config['metadata_prefix'], null, null, $config['set']);
}

foreach ($iterator as $record) {
    $filename = Path::join($path, $record->header->identifier . '.xml');
    $domrecord = new DOMDocument('1.0', 'UTF-8');
    $domrecord->loadXML($record->asXML());
    $xpath = new DOMXpath($domrecord);
    $xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->appendChild($doc->importNode($xpath->query('//oai:metadata/*[1]')->item(0), true));
    $doc->save($filename);
    if (array_key_exists('verbose', $config)) {
        fwrite(STDERR, "Saving " . $record->header->identifier . " to " . $filename . "\n");
    } else {
        fwrite(STDERR, '.');
    }
}
