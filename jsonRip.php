#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use fXmlRpc\Client as fXmlRpcClient;
use fXmlRpc\Parser\NativeParser;
use fXmlRpc\Serializer\NativeSerializer;
use fXmlRpc\Transport\HttpAdapterTransport;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as AdapterGuzzle6Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

const APP_NAME = 'OpenSubtitles Downloader';
const APP_VERSION = '0.1';
$appname = APP_NAME . ' v' . APP_VERSION;

date_default_timezone_set('Asia/Tokyo');

if(!isset($argv[1]) || !isset($argv[2])) {
	exit();
}
$jsonFile = $argv[1];
$imdbIDs = $argv[2];

// Load the configuration
$dotenv = new Dotenv(__DIR__);
$dotenv->load();

$container = new Container;
$container['files'] = new Filesystem;

// Initialize the HTTP and XMLRPC Clients
$httpClient = new GuzzleClient();
$client = new fXmlRpcClient(
		getenv('OPENSUBTITLES_API_URL'),
		new HttpAdapterTransport(new GuzzleMessageFactory(), new AdapterGuzzle6Client($httpClient)),
		new NativeParser(),
		new NativeSerializer()
		);

if (!$container['files']->isDirectory($jsonFile)) {
	$container['files']->makeDirectory($jsonFile);
}

$errorLog = $jsonFile."/error.log";

try {
	$login = $client->call('LogIn', [getenv('OPENSUBTITLES_USERNAME'), getenv('OPENSUBTITLES_PASSWORD'), 'en', 'OSTestUserAgentTemp']);
} catch (Exception $e) {
	error_log(json_encode_exception($e), 3, $errorLog);
	exit();
}

// Die if not ok or token is not provided
if ($login['status'] != '200 OK' || empty($login['token'])) {
	error_log(json_encode_n($login), 3, $errorLog);
	exit();
}

foreach(explode(",", $imdbIDs) as $imdbID) {
	$imdbFile = $jsonFile.'/'.$imdbID.'.json';	
	if ($container['files']->exists($imdbFile)) {
		print('exists: '.$imdbID."\n");
		continue;
	}
	$data = [];
	foreach(explode(",", getenv('OPENSUBTITLES_LANGUAGES')) as $lang) {
		try {
			$data['date'] = time();
			$search = [
				'sublanguageid' => $lang,
				'imdbid' => $imdbID
			];

			$response = $client->call('SearchSubtitles', 
					[$login['token'], [$search], ['limit' => '1']]);
			$data[$lang] = $response['data'];

			if (empty($response['data'])) break;

			//error_log(
			//		json_encode_n([
			//			'response' => 'No subtitles.', 
			//			'languages' => getenv('OPENSUBTITLES_LANGUAGES'), 
			//			'imdbID' => $imdbID, 
			//			'date' => time()]),
			//		3, $errorLog);

		} catch (Exception $e) {
			error_log(json_encode_exception($e), 3, $errorLog); 
			if ($e->getCode() == 520 || $e->getCode() == 503) {
				print('error: '.$imdbID."\n");
				sleep(5);
				continue;
			}
			exit();
		}
	}

	if (count($data) >= 3) {
		$container['files']->put($imdbFile, json_encode($data));
		print($imdbID."\n");
	} else {
		print('failed: '.$imdbID."\n");
	}

	sleep(1);
}

/**
 * JSON encode an Exception
 *
 * @param Exception $e the exception to be encoded.
 */
function json_encode_exception($e)
{
	return json_encode_n([
			'error' => [
			'msg' => $e->getMessage(),
			'code' => $e->getCode()
			],
	]);
}

function json_encode_n($s) {
	return json_encode($s).PHP_EOL;
}
