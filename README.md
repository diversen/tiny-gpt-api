# shell-gpt-php

A tiny GPT client with no dependencies. 

## Installation

composer require diversen/tiny-gpt-client

## Usage

The API has methods for completions and chat completions

* /completions
* /chat/completions

For both endpoints there is streaming and non-streaming
responses. 

When streaming the response the token usage is calculated using
a `Token` class. This may not be an exact calculation.

When using the non-streaming response the usage of tokens 
should be exact. 

## Usage example

See: [example](example/index.php)

## License

MIT © [Dennis Iversen](https://github.com/diversen)
