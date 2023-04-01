<?php


use PHPUnit\Framework\TestCase;
use Diversen\GPT\ApiResult;
use Diversen\GPT\OpenAiApi;
use Diversen\GPT\Tokens;

final class TokensTest extends TestCase
{
    public function test_estimate(): void
    {

        $tokens = Tokens::estimate("Hello world!", "max");
        $this->assertEquals(3, $tokens);

        $tokens = Tokens::estimate("Hello world!", "min");
        $this->assertEquals(2, $tokens);

        $tokens = Tokens::estimate("Hello world!", "average");
        $this->assertEquals(2, $tokens);

        // // 2063 tokens. Fail
        // $text = file_get_contents('tests/the-whale-too-large.txt');
        // echo Tokens::estimate($text, 'max');

        // // 1989 tokens. OK
        // $text = file_get_contents('tests/the-whale-large.txt');
        // echo Tokens::estimate($text, 'average');

    }
}