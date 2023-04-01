<?php


use PHPUnit\Framework\TestCase;
use Diversen\GPT\ApiResult;
use Diversen\GPT\OpenAiApi;
use Diversen\GPT\Base;

final class OpenApiTest extends TestCase
{
    public function test_getCompletions(): void
    {

        $params = array (
            'model' => 'text-davinci-003',
            'max_tokens' => 10,
            'temperature' => 0,
            'n' => 1,
            // 'stream' => false,
            'prompt' => 'Say "Hello world!" and nothing more',
        );

        $api_key = file_get_contents('./api_key.txt');
        $api = new OpenAiApi($api_key);

        // completions
        $result = $api->getCompletions($params);
        $this->assertInstanceOf(ApiResult::class, $result);
        $this->assertEquals('Hello world!', $result->content);
        $this->assertLessThan(100, (int)$result->tokens_used);


    }

    public function test_getChatCompletions() {
        $params = array (
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 2048,
            'temperature' => 0,
            'n' => 1,
            'stream' => false,
            'messages' => 
            array (
              0 => 
              array (
                'role' => 'user',
                'content' => 'say "Hello world!" and nothing more',
              ),
            ),
        );

        $api_key = file_get_contents('./api_key.txt');
        $api = new OpenAiApi($api_key);
        $api = new OpenAiApi($api_key);

        // Chat completions
        $result = $api->getChatCompletions($params);
        $this->assertInstanceOf(ApiResult::class, $result);
        $this->assertEquals('Hello world!', $result->content);
        $this->assertLessThan(100, (int)$result->tokens_used);

    }

    public function test_getChatCompletionsStream() {
        $params = array (
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 2048,
            'temperature' => 0,
            'n' => 1,
            'stream' => true,
            'messages' => 
            array (
              0 => 
              array (
                'role' => 'user',
                'content' => 'say "Hello world!" and nothing more',
              ),
            ),
        );

        $api_key = file_get_contents('./api_key.txt');
        $api = new OpenAiApi($api_key);  

        ob_start();
        $result = $api->getChatCompletionsStream($params);
        $this->assertInstanceOf(ApiResult::class, $result);
        $this->assertEquals('Hello world!', $result->content);
        $this->assertLessThan(100, (int)$result->tokens_used);

        $output = ob_get_clean();
        $this->assertStringContainsString('Hello world!', $output);

    }
}