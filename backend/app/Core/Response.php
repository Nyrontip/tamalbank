<?php
declare(strict_types=1);

/**
 * Response - PSR-7 inspired Response object
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function setStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setJson(array $data, int $code = 0): self
    {
        $this->headers['Content-Type'] = 'application/json';
        if ($code > 0) {
            $this->statusCode = $code;
        }
        $this->body = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function setText(string $text): self
    {
        $this->body = $text;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
        exit;
    }

    // Static helpers for quick responses
    public static function json(array $data, int $code = 0): void
    {
        $response = new self();
        $response->setJson($data, $code)->send();
    }

    public static function error(string $message, int $code = 400): void
    {
        $response = new self();
        $response->setJson(['error' => $message], $code)->send();
    }

    public static function success(array $data, int $code = 200): void
    {
        $response = new self();
        $response->setJson($data, $code)->send();
    }
}