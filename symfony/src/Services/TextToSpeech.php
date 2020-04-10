<?php

namespace App\Services;

use Google\Cloud\Dialogflow\V2\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;

class TextToSpeech
{
    /**
     * @var TextToSpeechClient
     */
    private $client;

    public function textToSpeech(string $text): string
    {
        $voice = (new VoiceSelectionParams())
            ->setLanguageCode('fr-FR')
            ->setName('fr-FR-Wavenet-A');

        $audioConfig = (new AudioConfig())
            ->setAudioEncoding(AudioEncoding::MP3);

        $synthesisInputText = (new SynthesisInput())
            ->setText($text);

        $response = $this->getClient()->synthesizeSpeech($synthesisInputText, $voice, $audioConfig);

        return $response->getAudioContent();
    }

    private function getClient(): TextToSpeechClient
    {
        if (!$this->client) {
            $this->client = new TextToSpeechClient();
        }
    }
}