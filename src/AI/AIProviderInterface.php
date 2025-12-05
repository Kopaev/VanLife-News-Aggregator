<?php

declare(strict_types=1);

namespace App\AI;

interface AIProviderInterface
{
    /**
     * Выполняет chat-completion запрос к провайдеру ИИ.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     */
    public function chat(array $messages, array $options = []): AIResponse;
}
