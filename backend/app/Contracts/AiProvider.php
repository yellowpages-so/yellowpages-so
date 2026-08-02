<?php

namespace App\Contracts;

interface AiProvider
{
    public function generateText(string $task, array $context): array;

    public function score(string $task, array $context): array;
}
