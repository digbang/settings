<?php

declare(strict_types = 1);

namespace Digbang\Settings\Entities;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;

abstract class SettingTestCase extends TestCase
{
    /** @var Setting */
    protected $setting;

    /** @var string */
    protected $aKey;

    /** @var string */
    protected $aName;

    /** @var string */
    protected $aDescription;

    /** @var array */
    protected $aValue;

    protected function setUp(): void
    {
        $this->aKey = 'aKey';
        $this->aName = 'aName';
        $this->aDescription = 'A Description';
        $this->aValue = $this->aValue();

        $this->setting = $this->createMinimalSetting(
            $this->aKey,
            $this->aName,
            $this->aDescription,
            $this->aValue
        );
    }

    /**
     * Filters valid values out of the examples.
     *
     * @see SettingTestCase::invalidValues()
     */
    abstract public function onlyInvalidValues(array $examples): array;

    /** @test */
    public function itHasAKey()
    {
        $this->assertEquals($this->aKey, $this->setting->getKey());
    }

    /** @test */
    public function itHasAName()
    {
        $this->assertEquals($this->aName, $this->setting->getName());
    }

    /** @test */
    public function itHasADescription()
    {
        $this->assertEquals($this->aDescription, $this->setting->getDescription());
    }

    /** @test */
    public function itHasAValue()
    {
        $this->assertEquals($this->aValue, $this->setting->getValue());
    }

    /** @test */
    public function itIsNotNullableByDefault()
    {
        $this->assertEquals(false, $this->setting->isNullable());
    }

    /** @test */
    public function itCanBeNullable()
    {
        $setting = $this->createSetting(
            $this->aKey, $this->aName, $this->aDescription, $this->aValue,
            $nullable = true
        );

        $this->assertEquals($nullable, $setting->isNullable());
    }

    /** @test */
    public function itCanBeSetANewValue()
    {
        $newValue = $this->anotherValue();

        $this->setting->setValue($newValue);
        $this->assertEquals($newValue, $this->setting->getValue());
    }

    /** @test @dataProvider invalidValues */
    public function itCannotBeSetAnInvalidValue($invalidValue)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->setting->setValue($invalidValue);
    }

    /** @test */
    public function itCannotBeSetToNull()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->setting->setValue(null);
    }

    /** @test */
    public function itCanBeSetToNullWhenNullable()
    {
        $setting = $this->createSetting($this->aKey, $this->aName, $this->aDescription, null, true);

        $this->assertNull($setting->getValue());
    }

    public function invalidValues(): array
    {
        return $this->onlyInvalidValues([
            'array' => [['an', 'array', 'is', 'not', 'valid']],
            'boolean' => [false /* bool is not valid */],
            'date' => [Chronos::today() /* a date is not valid */],
            'datetime' => [Chronos::now() /* a datetime is not valid */],
            'email' => ['foo@bar.com' /* an email is not valid */],
            'time' => ['15:18' /* a time is not valid */],
            'float' => [3.14 /* a float is not valid */],
            'int' => [43 /* an int is not valid */],
            'string' => ['A string is not valid'],
            'url' => ['https://url.is.invalid'],
        ]);
    }

    /**
     * Should return a valid initial value.
     *
     * @return mixed
     */
    abstract protected function aValue();

    /**
     * Should return another valid value to test against.
     *
     * @return mixed
     */
    abstract protected function anotherValue();

    /**
     * Creates the specific setting based on parent's constructor.
     *
     * @param mixed $value
     */
    abstract protected function createSetting(
        string $key,
        string $name,
        string $description,
        $value,
        bool $nullable
    ): Setting;

    /**
     * Creates the specific setting based on parent's constructor,
     * without specifying nullability.
     *
     * @param mixed $value
     */
    abstract protected function createMinimalSetting(
        string $key,
        string $name,
        string $description,
        $value
    ): Setting;
}
