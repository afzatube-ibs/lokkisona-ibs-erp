<?php

namespace App\Services\Write;

class WriteResult
{
    public bool $success;
    public string $message;
    public ?int $id;
    public array $errors;

    public function __construct(bool $success, string $message, ?int $id = null, array $errors = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->id = $id;
        $this->errors = $errors;
    }

    public static function ok(string $message, ?int $id = null): self
    {
        return new self(true, $message, $id);
    }

    public static function fail(string $message, array $errors = []): self
    {
        return new self(false, $message, null, $errors);
    }
}
