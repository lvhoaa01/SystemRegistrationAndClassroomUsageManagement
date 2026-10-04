<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    private function __construct(
        private readonly string $body,
        private readonly int $status,
        private readonly string $contentType,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, 'text/html; charset=UTF-8');
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            'application/json; charset=UTF-8',
        );
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: ' . $this->contentType);
        echo $this->body;
    }
}

