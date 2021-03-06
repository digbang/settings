<?php

namespace Digbang\Settings\Entities;

class UrlSetting extends Setting
{
    public function getValue(): ?string
    {
        if ($this->isNullable() && $this->value === null) {
            return null;
        }

        return (string) $this->value;
    }

    protected function assertValid($value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException;
        }
    }
}
