<?php

namespace App\Services\Nbn;

class MockNbnClient implements NbnClientInterface
{
    public function __construct(private string $mode = 'success') {}

    public function submitOrder(array $payload): array
    {
        $file = $this->mode === 'fail'
            ? base_path('tests/stubs/nbn-fail-response.json')
            : base_path('tests/stubs/nbn-successful-response.json');

        return json_decode(file_get_contents($file), true);
    }
}
