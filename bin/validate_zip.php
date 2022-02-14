<?php

require_once __DIR__ . '/../vendor/autoload.php';

const VALIDATOR_URL = 'https://validator.prestashop.com/api/modules';

$inputFile  = $argv[1];
$apiKey = getenv('VALIDATOR_API_KEY');
//$apiKey = $argv[2];

if (empty($apiKey)) {
    throw new Exception('No API Key is set to authenticate the request to the validator. Please set the env var VALIDATOR_API_KEY');
}

if (empty($inputFile) || !file_exists($inputFile) || !is_readable($inputFile)) {
    throw new Exception(sprintf('File %s was not found, or cannot be read', $inputFile));
}

$multipart = [
    [
        'name'     => 'key',
        'contents' => $apiKey
    ]
];

// Calling the Validator API
try {

    $multipart[] = [
        'name'     => 'archive',
        'contents' => fopen($inputFile, 'r'),
    ];

	$client = new \GuzzleHttp\Client([
		'base_uri' => VALIDATOR_URL
	]);

	$response = $client->post('/api/modules', [
		'multipart' => $multipart
	]);


} catch (\Throwable $th) {
    // Maybe the Validator is not online, and we can't hold the pipeline
    print_r('Couldn\'t reach the Prestashop Validator API');
	print_r($th->getTraceAsString());
    return;
}

$stdResponse = json_decode($response->getBody()->getContents(), true);

$warningCount = $stdResponse['Details']['results']['warnings'];
$errorCount = $stdResponse['Details']['results']['errors'];

print_r("Found $warningCount warnings and $errorCount errors");

if ($errorCount === 0) {
    print_r(' -> ZIP Validation is OK');
    return;
}

print_r(' -> ZIP Validation contains errors.');
