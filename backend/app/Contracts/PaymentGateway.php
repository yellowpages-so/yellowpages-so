<?php

namespace App\Contracts;

interface PaymentGateway
{
    public function createIntent(array $payment): array;

    public function capture(array $payment): array;

    public function refund(array $payment, float $amount): array;
}
