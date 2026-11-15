<?php

declare(strict_types=1);

/**
 * Lekki walidator danych formularzy.
 * Zbiera wszystkie błędy, aby pokazać je użytkownikowi naraz.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function required(string $field, mixed $value, string $message = 'To pole jest wymagane.'): self
    {
        if (!is_string($value) ? $value === null || $value === '' : trim($value) === '') {
            $this->add($field, $message);
        }

        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        $value = (string) $value;
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->add($field, 'Podaj poprawny adres e-mail.');
        }

        return $this;
    }

    public function minLength(string $field, mixed $value, int $min, string $message = ''): self
    {
        $value = (string) $value;
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->add($field, $message !== '' ? $message : "Minimum {$min} znaków.");
        }

        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max, string $message = ''): self
    {
        $value = (string) $value;
        if (mb_strlen($value) > $max) {
            $this->add($field, $message !== '' ? $message : "Maksymalnie {$max} znaków.");
        }

        return $this;
    }

    public function integer(string $field, mixed $value, int $min, int $max, string $message = ''): self
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        if ($filtered === false || $filtered < $min || $filtered > $max) {
            $this->add($field, $message !== '' ? $message : "Podaj liczbę od {$min} do {$max}.");
        }

        return $this;
    }

    public function decimal(string $field, mixed $value, float $min, float $max, string $message = ''): self
    {
        if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            $this->add($field, $message !== '' ? $message : "Podaj wartość od {$min} do {$max}.");
        }

        return $this;
    }

    public function date(string $field, mixed $value, string $message = 'Podaj poprawną datę.'): self
    {
        $value = (string) $value;
        $parsed = DateTime::createFromFormat('Y-m-d', $value);
        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            $this->add($field, $message);
        }

        return $this;
    }

    /** Data nie może być z przeszłości. */
    public function futureDate(string $field, mixed $value, string $message = 'Data nie może być z przeszłości.'): self
    {
        $ts = strtotime((string) $value);
        if ($ts !== false && $ts < strtotime(date('Y-m-d'))) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function time(string $field, mixed $value, string $message = 'Podaj poprawną godzinę.'): self
    {
        $value = (string) $value;
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed, string $message = 'Nieprawidłowa wartość.'): self
    {
        if (!in_array((string) $value, array_map('strval', $allowed), true)) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function add(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function first(string $field): string
    {
        return $this->errors[$field] ?? '';
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }
}
