<?php

namespace Digbang\Settings\Entities;

abstract class Setting
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var string|null */
    protected $value;

    /** @var bool */
    protected $nullable;

    public function __construct(string $key, string $name, string $description, $value, bool $nullable = false)
    {
        $this->key = $key;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->description = $description;

        $this->setValue($value);
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param mixed|null $value
     *
     * @throws \InvalidArgumentException
     */
    public function setValue($value): void
    {
        $this->assertNullable($value);

        if ($value === null) {
            $this->value = null;

            return;
        }

        $this->assertValid($value);
        $this->value = $this->encode($value);
    }

    /** @param string $name */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @param string $description */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getValue()
    {
        if ($this->value === null && $this->isNullable()) {
            return null;
        }

        return $this->decode($this->value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    abstract protected function assertValid($value): void;

    protected function encode($value)
    {
        return $value;
    }

    protected function decode($value)
    {
        return $value;
    }

    private function assertNullable($value)
    {
        if ($value === null && ! $this->isNullable()) {
            throw new \InvalidArgumentException('This setting does not allow null values.');
        }
    }
}
