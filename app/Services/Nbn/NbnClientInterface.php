<?php

namespace App\Services\Nbn;

interface NbnClientInterface
{
    public function submitOrder(array $payload): array;
}
