<?php

namespace App\Tests\Unit\Account\Domain\ValueObject;

use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testMoneyCreation(): void
    {
        $money = new Money('100.50', Currency::UAH);

        $this->assertEquals('100.50', $money->getAmount());
        $this->assertEquals(Currency::UAH, $money->getCurrency());
    }

    public function testMoneyCreationWithZeroAmount(): void
    {
        $money = new Money('0.00', Currency::USD);

        $this->assertEquals('0.00', $money->getAmount());
    }

    public function testNegativeAmountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        new Money('-10.00', Currency::UAH);
    }

    public function testMoneyAddition(): void
    {
        $money1 = new Money('100.50', Currency::UAH);
        $money2 = new Money('50.25', Currency::UAH);

        $result = $money1->add($money2);

        $this->assertEquals('150.75', $result->getAmount());
        $this->assertEquals(Currency::UAH, $result->getCurrency());
    }

    public function testMoneySubtraction(): void
    {
        $money1 = new Money('100.00', Currency::UAH);
        $money2 = new Money('30.25', Currency::UAH);

        $result = $money1->subtract($money2);

        $this->assertEquals('69.75', $result->getAmount());
        $this->assertEquals(Currency::UAH, $result->getCurrency());
    }

    public function testAdditionWithDifferentCurrenciesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $money1 = new Money('100.00', Currency::UAH);
        $money2 = new Money('50.00', Currency::USD);

        $money1->add($money2);
    }

    public function testSubtractionWithDifferentCurrenciesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $money1 = new Money('100.00', Currency::UAH);
        $money2 = new Money('50.00', Currency::USD);

        $money1->subtract($money2);
    }

    public function testSubtractionResultingInNegativeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Result cannot be negative');

        $money1 = new Money('50.00', Currency::UAH);
        $money2 = new Money('100.00', Currency::UAH);

        $money1->subtract($money2);
    }

    public function testMoneyEquality(): void
    {
        $money1 = new Money('100.50', Currency::UAH);
        $money2 = new Money('100.50', Currency::UAH);

        $this->assertTrue($money1->equals($money2));
    }

    public function testMoneyInequalityDifferentAmounts(): void
    {
        $money1 = new Money('100.50', Currency::UAH);
        $money2 = new Money('100.00', Currency::UAH);

        $this->assertFalse($money1->equals($money2));
    }

    public function testMoneyInequalityDifferentCurrencies(): void
    {
        $money1 = new Money('100.00', Currency::UAH);
        $money2 = new Money('100.00', Currency::USD);

        $this->assertFalse($money1->equals($money2));
    }

    public function testMoneyIsImmutable(): void
    {
        $original = new Money('100.00', Currency::UAH);
        $added = new Money('50.00', Currency::UAH);

        $result = $original->add($added);

        // Original should not change
        $this->assertEquals('100.00', $original->getAmount());
        // Result should be a new instance
        $this->assertEquals('150.00', $result->getAmount());
        $this->assertNotSame($original, $result);
    }
}
