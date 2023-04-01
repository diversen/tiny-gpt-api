<?php

namespace Diversen\GPT;

class ApiResult
{

    public int $tokens_used = 0;
    public string $content = '';
    public array $array;
    public int $error_code = 0;
    public string $error_message = '';

    public function setResult(string $json)
    {
        $this->array = json_decode($json, true);
    }

    public function setCompletions()
    {
        $this->tokens_used = (int)$this->array["usage"]["total_tokens"] ?? 0;
        $this->content = trim($this->array["choices"][0]["text"]);
    }

    public function setChatCompletions()
    {
        $this->tokens_used = (int)$this->array["usage"]["total_tokens"] ?? 0;
        $this->content = trim($this->array["choices"][0]["message"]["content"]);
    }

    public function isError(): bool
    {
        if ($this->error_code !== 0) {
            return true;
        }
        return false;
    }
}
