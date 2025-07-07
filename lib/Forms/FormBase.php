<?php
declare(strict_types=1);

namespace TwoQuick\Api\Forms;

use Assert\Assert;

abstract class FormBase
{
    /** @var array|string[] */
    private $errors;
    /** @var Assert */
    private $validator;

    public function __construct()
    {
        $this->validator = Assert::class;
    }

    /**
     * Добавляет ошибку в массив
     * @param string|array $message
     * @return void
     */
    public function setError($message): void
    {
        $this->errors[] = $message;
    }

    /**
     * возвращает массив с ошибками
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Проверяет наличие ошибок
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Возвращает статус прохождения валидации
     * @return bool
     */
    abstract public function validate(): bool;
}
