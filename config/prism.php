<?php

return [
    'api_key' => env('PRISM_API_KEY'),
    'model' => env('PRISM_MODEL', 'text-davinci-003'),
    'max_tokens' => env('PRISM_MAX_TOKENS', 150),
    'temperature' => env('PRISM_TEMPERATURE', 0.7),
];
