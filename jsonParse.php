#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;

const APP_NAME = 'OpenSubtitles Downloader';
const APP_VERSION = '0.1';
$appname = APP_NAME . ' v' . APP_VERSION;

date_default_timezone_set('Asia/Tokyo');

if(!isset($argv[1])) {
	echo "Usage> jsonParse.php <json_folder>\n";
	exit();
}
$json = $argv[1];

$fs = new Filesystem;

$files = $fs->allFiles($json);
$files = array_map('getPath', $files);
$files = array_filter($files, 'isJson');

$db = new SQLite3("./subtitles.db");

foreach($files as $file) {
	$f = json_decode($fs->get($file), true);
	if (isset($f['date']))
		$date = $f['date'];
	else
		$date = 0;
	if (!empty($f['fre']) && !empty($f['eng'])) {
		foreach (array_splice($f, 1, 2) as $info) {
			$parsedJson = parse($info[0], $date);
			$columns = implode(",", array_keys($parsedJson));
			$values = implode(",", array_values($parsedJson));

			$query = 'insert or ignore into subtitles ('.$columns.') values ('.$values.')';
			print(json_encode_n(['data' => array_values($parsedJson), 'result' => $db->exec($query)]));
		}
	}
}

function parse($a, $date) {

	$map = [
		['osName' => 'IDSubtitleFile', 'localName' => 'subtitleFileId', 'type' => 'int'],
		//['osName' => 'IDMovie', 'localName' => 'openSubsMovieId', 'type' => 'int'],
		['osName' => 'IDMovieImdb', 'localName' => 'imdbId', 'type' => 'int'],
		['osName' => 'SubLanguageID', 'localName' => 'subLanguage', 'type' => 'text'],
		['osName' => 'SubFormat', 'localName' => 'subFormat', 'type' => 'text'],
		['osName' => 'MovieReleaseName', 'localName' => 'name', 'type' => 'text'],
		['osName' => 'MovieYear', 'localName' => 'year', 'type' => 'int'],
		['osName' => 'LanguageName', 'localName' => 'language', 'type' => 'text'],
		['osName' => 'MovieKind', 'localName' => 'type', 'type' => 'text'],
		['osName' => 'SubEncoding', 'localName' => 'subEncoding', 'type' => 'text'],
		['osName' => 'SubForeignPartsOnly', 'localName' => 'subForeignPartsOnly', 'type' => 'int'],
		['osName' => 'SubDownloadLink', 'localName' => 'subDownloadLink', 'type' => 'text'],
		['osName' => 'SeriesIMDBParent', 'localName' => 'seriesImdbParent', 'type' => 'int'],
		['osName' => 'UserNickName', 'localName' => 'subberName', 'type' => 'text'],
		['osName' => 'MovieImdbRating', 'localName' => 'imdbRating', 'type' => 'int']
	];

	$t = [];
	foreach($map as $i) {	
		$value = $a[$i['osName']];
		$t[$i['localName']] = $i['type'] === 'text' ? '"'.$value.'"' : $value;
	}

	$t['queryDate'] = $date;	

	return $t;
}

function getPath($file) {
	return $file->getPathname();
}

function isJson($t) {
  $e = explode('.', $t);
  if(count($e) > 1 && $e[1] == 'json') {
    return true;
	}
  return false;
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
