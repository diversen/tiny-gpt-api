<?php

namespace Diversen\GPT;

use Diversen\GPT\ApiResult;
use Exception;
use Throwable;

class OpenAiApi
{

    private string $base_path = 'https://api.openai.com/v1';
    private string $api_key = '';
    private float $timeout = 4;
    private int $stream_sleep = 100000;

    /**
     * @param string $api_key
     * @param int $timeout request timeout in seconds
     * @param int $stream_sleep sleep time in seconds between stream reads
     */
    public function __construct(string $api_key, float $timeout = 4, float $stream_sleep = 0.1)
    {
        $this->api_key = $api_key;
        $this->timeout = $timeout;
        $this->stream_sleep = $stream_sleep * 1000000;
        $this->stream_sleep = (int) $this->stream_sleep;
    }

    private function parseHeaders($headers)
    {
        $head = array();
        foreach ($headers as $key => $header) {
            $key_value = explode(':', $header, 2);
            if (isset($key_value[1]))
                $head[trim($key_value[0])] = trim($key_value[1]);
            else {
                // HTTP/1.1 200 OK 
                // This is the only case where a header will not have a key
                $head[] = $header;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $header, $out))
                    $head['response_code'] = intval($out[1]);
            }
        }
        return $head;
    }

    private function openAiRequest($endpoint, $params)
    {

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $this->api_key;

        $options = array(
            'http' => array(
                'header'  => $headers,
                'method'  => 'POST',
                'content' => json_encode($params),
                'timeout' => $this->timeout,
            ),
        );

        $context  = stream_context_create($options);

        try {
            $stream = fopen($endpoint, 'r', false, $context);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }

        $result = stream_get_contents($stream);
        $headers = $this->parseHeaders($http_response_header);
        if ($headers['response_code'] >= 400) {
            $error_message = $this->getAPIError($result);
            throw new Exception($error_message, $headers['response_code']);
        }

        return $result;
    }

    private function getAPIError($result)
    {
        $result = json_decode($result, true);
        return "API ERROR: " . $result["error"]["message"];
    }

    public function getCompletions(array $params): ApiResult
    {
        $endpoint = '/completions';
        $api_result = new ApiResult();

        try {
            $endpoint = $this->base_path . $endpoint;
            $result = $this->openAiRequest($endpoint, $params);

            $api_result->setResult($result);
            $api_result->setCompletions();
        } catch (Throwable $e) {

            $api_result->error_code = $e->getCode();
            $api_result->error_message = $e->getMessage();
        }

        return $api_result;
    }

    public function getChatCompletions(array $params): ApiResult
    {
        $endpoint = '/chat/completions';
        $api_result = new ApiResult();

        try {
            $endpoint = $this->base_path . $endpoint;
            $result = $this->openAiRequest($endpoint, $params);

            $api_result->setResult($result);
            $api_result->setChatCompletions();
        } catch (Throwable $e) {
            $api_result->error_code = $e->getCode();
            $api_result->error_message = $e->getMessage();
        }

        return $api_result;
    }

    public function getCompletionsStream(array $params): ApiResult
    {
        $endpoint = '/completions';
        $result = new ApiResult();

        $tokens_prompt = Tokens::estimate(json_encode($params['prompt']), 'max');
        $tokens_answer = 0;
        $complete_response = '';
        $first_content = false;

        try {
            $this->openAiStream($endpoint, $params, function ($json) use (&$complete_response, &$tokens_answer, &$first_content) {
                $content = $json['choices'][0]['text'] ?? '';
                if (!empty(trim($content))) {
                    $first_content = true;
                }
                if ($first_content) {
                    $complete_response .= $content;
                    echo $content;
                }

                $tokens_answer += 1;
            });
        } catch (Throwable $e) {
            $result->error_message = $e->getMessage();
            $result->error_code = $e->getCode();
            return $result;
        }

        $result->content = $complete_response;
        $result->tokens_used = $tokens_prompt + $tokens_answer;

        return $result;
    }

    public function getChatCompletionsStream(array $params): ApiResult
    {
        $endpoint = '/chat/completions';
        $result = new ApiResult();

        $tokens_messages = Tokens::estimate(json_encode($params['messages']), 'max');
        $tokens_answer = 0;
        $complete_response = '';

        try {
            $this->openAiStream($endpoint, $params, function ($json) use (&$complete_response, &$tokens_answer) {
                $content = $json['choices'][0]['delta']['content'] ?? '';
                $complete_response .= $content;
                $tokens_answer += 1;
                echo $content;
            });
        } catch (Throwable $e) {
            $result->error_message = $e->getMessage();
            $result->error_code = $e->getCode();
            return $result;
        }

        $result->content = $complete_response;
        $result->tokens_used = $tokens_messages + $tokens_answer;

        return $result;
    }

    public function openAiStream(string $endpoint, array $params, callable $callback)
    {

        $endpoint = $this->base_path . $endpoint;
        $params['stream'] = true;

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $this->api_key;
        $headers[] = 'Accept: text/event-stream';

        $options = array(
            'http' => array(
                'header'  => $headers,
                'method'  => 'POST',
                'content' => json_encode($params),
                'timeout' => $this->timeout,
            ),
        );

        $context  = stream_context_create($options);

        try {
            $stream = fopen($endpoint, 'r', false, $context);
        } catch (Throwable $e) {
            throw new Exception("Could not read from API endpoint.", 500);
        }

        while (!feof($stream)) {


            $line = fgets($stream);
            if ($line === false) {
                throw new Exception("Error reading data from API endpoint.", 500);
            }
            
            $json_str = explode('data: ', $line)[1] ?? '';
            $message = explode('data: ', $line)[0] ?? '';

            if (strpos($message, '[DONE]') === 0) {
                fclose($stream);
                break;
            }

            if (empty($json_str)) {
                continue;
            }

            $json = json_decode($json_str, true);
            $callback($json);
            $finish_reason = $json['finish_reason'] ?? '';
            if ($finish_reason) {
                fclose($stream);
                break;
            }
            usleep($this->stream_sleep);
        }
    }
}
