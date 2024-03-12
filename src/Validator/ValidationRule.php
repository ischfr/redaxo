<?php

namespace Redaxo\Core\Validator;

final class ValidationRule
{
    public const NOT_EMPTY = 'notEmpty';
    public const MIN_LENGTH = 'minLength';
    public const MAX_LENGTH = 'maxLength';
    public const MIN = 'min';
    public const MAX = 'max';
    public const URL = 'url';
    public const EMAIL = 'email';
    public const MATCH = 'match';
    public const NOT_MATCH = 'notMatch';
    public const VALUES = 'values';
    public const CUSTOM = 'custom';

    /**
     * @param ValidationRule::*|string $type Validator type, e.g. one of ValidationRule::* but could also be extended via rex-factory
     * @param string|null $message Message which is used if this validator type does not match
     * @param mixed $option Type specific option
     */
    public function __construct(
        private string $type,
        private ?string $message = null,
        private mixed $option = null,
    ) {}

    /**
     * Validator type, e.g. one of ValidationRule::* but could also be extended via rex-factory.
     *
     * @return ValidationRule::*|string $type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Type specific option.
     *
     * @return mixed
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Message which is used if this validator type does not match.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
