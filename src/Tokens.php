<?php

namespace Diversen\GPT;

class Tokens
{
    public static function estimate(string $text, string $method = "max"): int
    {
        // method can be "average", "words", "chars", "max", "min", defaults to "max"
        // "average" is the average of words and chars
        // "words" is the word count divided by 0.75
        // "chars" is the char count divided by 4
        // "max" is the max of word and char
        // "min" is the min of word and char
        $word_count = count(explode(" ", $text));
        $char_count = strlen($text);
        $tokens_count_word_est = $word_count / 0.75;
        $tokens_count_char_est = $char_count / 4.0;
        $output = 0;
        if ($method == "average") {
            $output = ($tokens_count_word_est + $tokens_count_char_est) / 2;
        } elseif ($method == "words") {
            $output = $tokens_count_word_est;
        } elseif ($method == "chars") {
            $output = $tokens_count_char_est;
        } elseif ($method == 'max') {
            $output = max($tokens_count_word_est, $tokens_count_char_est);
        } elseif ($method == 'min') {
            $output = min($tokens_count_word_est, $tokens_count_char_est);
        } else {
            // return invalid method message
            return "Invalid method. Use 'average', 'words', 'chars', 'max', or 'min'.";
        }
        return (int)$output;
    }
}
