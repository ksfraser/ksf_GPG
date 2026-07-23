<?php
declare(strict_types=1);

namespace ksfraser\GPG\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\Exception\InvalidFingerprintException;

class FingerprintTest extends TestCase
{
    public function testValidFingerprint(): void
    {
        $fp = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $this->assertSame('1234567890ABCDEF1234567890ABCDEF12345678', $fp->getValue());
    }

    public function testFingerprintWithSpaces(): void
    {
        $fp = new Fingerprint('1234 5678 90AB CDEF 1234 5678 90AB CDEF 1234 5678');
        $this->assertSame('1234567890ABCDEF1234567890ABCDEF12345678', $fp->getValue());
    }

    public function testFingerprintWithColons(): void
    {
        $fp = new Fingerprint('12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78');
        $this->assertSame('1234567890ABCDEF1234567890ABCDEF12345678', $fp->getValue());
    }

    public function testFingerprintWithLowercase(): void
    {
        $fp = new Fingerprint('1234567890abcdef1234567890abcdef12345678');
        $this->assertSame('1234567890ABCDEF1234567890ABCDEF12345678', $fp->getValue());
    }

    public function testInvalidFingerprintTooShort(): void
    {
        $this->expectException(InvalidFingerprintException::class);
        new Fingerprint('1234567890');
    }

    public function testInvalidFingerprintInvalidChars(): void
    {
        $this->expectException(InvalidFingerprintException::class);
        new Fingerprint('GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG');
    }

    public function testGetFormatted(): void
    {
        $fp = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $this->assertSame('1234 5678 90AB CDEF 1234 5678 90AB CDEF 1234 5678', $fp->getFormatted());
    }

    public function testGetShortId(): void
    {
        $fp = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $this->assertSame('12345678', $fp->getShortId());
    }

    public function testEquals(): void
    {
        $fp1 = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $fp2 = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $fp3 = new Fingerprint('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');

        $this->assertTrue($fp1->equals($fp2));
        $this->assertFalse($fp1->equals($fp3));
    }

    public function testToString(): void
    {
        $fp = new Fingerprint('1234567890ABCDEF1234567890ABCDEF12345678');
        $this->assertSame('1234567890ABCDEF1234567890ABCDEF12345678', (string) $fp);
    }
}
