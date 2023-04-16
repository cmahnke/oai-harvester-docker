<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Symfony\Component\Yaml\Yaml;
use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;
use Symfony\Component\Filesystem\Path;

$config = Yaml::parseFile(__DIR__ . '/../config.yaml');

$stack = HandlerStack::create();
$stack->push(GuzzleRetryMiddleware::factory());
$guzzleClient = new GuzzleClient(['handler' => $stack]);
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter($guzzleClient);

#class ErrorReportingClient extends Client {
#	protected function decodeResponse( $resp ): SimpleXMLElement {
#		try {
#			return parent::decodeResponse( $resp );
#		}
#		catch ( MalformedResponseException $exception ) {
#			error_log( 'MalformedResponseException: ' . $resp );
#			throw $exception;
#		}
#	}
#}

$client = new Client($config['oai_url'], $guzzleAdapter);
#$harvester = new Endpoint(new ErrorReportingClient($config['oai_url'], $guzzleAdapter));
$harvester = new Endpoint($client);
#$path = Path::join(__DIR__ , $config['target_dir']);
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
    print '.';
}
