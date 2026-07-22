<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\ValueObject\KeyId;
use Ksf\GPG\Exception\InvalidKeyIdException;

class KeyIdTest extends TestCase
{
    public function testValidShortKeyId(): void
    {
        $kid = new KeyId('12345678');
        $this->assertSame('12345678', $kid->getValue());
        $this->assertTrue($kid->isShort());
        $this->assertFalse($kid->isLong());
    }

    public function testValidLongKeyId(): void
    {
        $kid = new KeyId('1234567890ABCDEF');
        $this->assertSame('1234567890ABCDEF', $kid->getValue());
        $this->assertFalse($kid->isShort());
        $this->assertTrue($kid->isLong());
    }

    public function testKeyIdWith0xPrefix(): void
    {
        $kid = new KeyId('0x12345678');
        $this->assertSame('12345678', $kid->getValue());
    }

    public function testKeyIdWithSpaces(): void
    {
        $kid = new KeyId('1234 5678');
        $this->assertSame('12345678', $kid->getValue());
    }

    public function testKeyIdWithColons(): void
    {
        $kid = new KeyId('12:34:56:78');
        $this->assertSame('12345678', $kid->getValue());
    }

    public function testKeyIdLowercase(): void
    {
        $kid = new KeyId('abcdef12');
        $this->assertSame('ABCDEF12', $kid->getValue());
    }

    public function testInvalidKeyIdTooShort(): void
    {
        $this->expectException(InvalidKeyIdException::class);
        new KeyId('1234');
    }

    public function testInvalidKeyIdInvalidChars(): void
    {
        $this->expectException(InvalidKeyIdException::class);
        new KeyId('GGGGGGGG');
    }

    public function testGetShortId(): void
    {
        $kid = new KeyId('1234567890ABCDEF');
        $this->assertSame('90ABCDEF', $kid->getShortId());
    }

    public function testEquals(): void
    {
        $kid1 = new KeyId('12345678');
        $kid2 = new KeyId('12345678');
        $kid3 = new KeyId('87654321');

        $this->assertTrue($kid1->equals($kid2));
        $this->assertFalse($kid1->equals($kid3));
    }

    public function testToString(): void
    {
        $kid = new KeyId('12345678');
        $this->assertSame('12345678', (string) $kid);
    }
}
