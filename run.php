<?php

require 'vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

echo "Tab-complete to select file from prompts-ssml directory. Ctrl-C to exit.\n";

while (true)
{
	// Input file
	chdir('prompts-ssml');

	while (true) 
	{
		echo "\n";
		$inputFile = readline("prompts-ssml/");

		readline_add_history($inputFile);

		if (!empty($inputFile))
		{
			if (is_file($inputFile))
				break;
			else
				echo "\"$inputFile\" is not a valid file.\n";
		}
	} 

	$ssmlText = file_get_contents($inputFile);

	chdir(__DIR__);

	// Output file

	$pathParts = pathinfo($inputFile);
	$outputFile = "prompts-wav/{$pathParts['dirname']}/{$pathParts['filename']}.wav";
	$dirName = dirname($outputFile);

	if (!is_dir($dirName))
		mkdir($dirName, 0777, true);


	// Get language

	$xml = simplexml_load_string($ssmlText);

	$language = (string) ($xml->xpath('//@xml:lang')[0] ?? 'en-US');



	$client = new TextToSpeechClient([
		'credentials' => json_decode(file_get_contents('./keyfile.json'), true)
	]);	

	$input = (new SynthesisInput)
		->setSsml($ssmlText);

	$voice = (new VoiceSelectionParams)
		->setLanguageCode($language)
		->setName("$language-Chirp3-HD-Callirrhoe");

	$audioConfig = (new AudioConfig)
		->setAudioEncoding(AudioEncoding::MULAW)
		->setSampleRateHertz(8000);

	$request = (new SynthesizeSpeechRequest)
		->setInput($input)
		->setVoice($voice)
		->setAudioConfig($audioConfig);

	$response = $client->synthesizeSpeech($request);

	$audioContent = $response->getAudioContent();

	file_put_contents($outputFile, $audioContent);

	$client->close();
}
