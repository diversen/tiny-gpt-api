<?php

# The API implements a couple of endpoints. 
# /completions and /chat/completions
# And both of them can be streamed or not.

require_once "vendor/autoload.php";

// Throw on all kind of errors and notices
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

use Diversen\GPT\OpenAiApi;

// Read api key from file
// You may also read it from an environment variable
$api_key = "/home/dennis/.config/shell-gpt-php/api_key.txt";
$api_key = file_get_contents($api_key);


#
# /completions (no streaming)
#

$completion_params = array (
    'model' => 'text-davinci-003',
    'max_tokens' => 10,
    'temperature' => 0,
    'n' => 1,
    'stream' => false,
    'prompt' => 'Only say "Hello world!"',
);


$api = new OpenAiApi($api_key);
$result = $api->getCompletions($completion_params);
if ($result->isError()) {
    echo $result->error_message;
    exit(1);
}

echo $result->content . PHP_EOL;
echo $result->tokens_used . PHP_EOL;

#
# /chat/completions (no streaming)
#

$api = new OpenAiApi($api_key);
$chat_completions_params = array (
    'model' => 'gpt-3.5-turbo',
    'max_tokens' => 10,
    'temperature' => 0,
    'n' => 1,
    'stream' => false,
    'messages' => 
    array (
      0 => 
      array (
        'role' => 'user',
        'content' => 'say "Hello world from chat completions" and nothing more',
      ),
    ),
);

$result = $api->getChatCompletions($chat_completions_params);
if ($result->isError()) {
    echo $result->error_message;
    exit(1);
}

echo $result->content . PHP_EOL;
echo $result->tokens_used . PHP_EOL;

#
# /completions (streaming)
# 

$completion_params['stream'] = true;
$api = new OpenAiApi(api_key: $api_key, stream_sleep: 0.1, timeout: 4);
try {
    $result = $api->openAiStream('/completions', $completion_params, function ($json) {
        $content = $json['choices'][0]['text'] ?? '';
        echo $content;
    });
    echo PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}

# Or

$api = new OpenAiApi(api_key: $api_key, stream_sleep: 0.1, timeout: 4);
$result = $api->getCompletionsStream($completion_params);
if ($result->isError()) {
    echo $result->error_message;
    exit(1);
}

echo $result->content . PHP_EOL;
echo $result->tokens_used . PHP_EOL;


#
# /chat/completions (streaming)
# 

$chat_completions_params['stream'] = true;
$api = new OpenAiApi(api_key: $api_key, stream_sleep: 0.1, timeout: 4);
try {
    $result = $api->openAiStream('/chat/completions', $chat_completions_params, function ($json) {
        $content = $json['choices'][0]['delta']['content'] ?? '';
        echo $content;
    });
    echo PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}

# or

$api = new OpenAiApi(api_key: $api_key, stream_sleep: 0.1, timeout: 4);
$result = $api->getChatCompletionsStream($chat_completions_params);
if ($result->isError()) {
    echo $result->error_message;
    exit(1);
}

echo $result->content . PHP_EOL;
echo $result->tokens_used . PHP_EOL;
