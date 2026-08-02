<?php

namespace App\Contracts;

interface CommunicationChannel
{
    public function send(array $message): array;
}
