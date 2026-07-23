<?php
declare(strict_types=1);

namespace ksfraser\GPG\Tests\Unit\Hook;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\Hook\GPGTarget;

class GPGTargetTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $target = new GPGTarget('customer', 42);

        $this->assertSame('customer', $target->getContactType());
        $this->assertSame(42, $target->getContactId());
        $this->assertNull($target->getEmail());
        $this->assertNull($target->getFingerprint());
        $this->assertFalse($target->isFallbackToPassword());
    }

    public function testConstructorWithOptionalParams(): void
    {
        $target = new GPGTarget('supplier', 7, 'test@example.com', 'ABCD1234', true);

        $this->assertSame('supplier', $target->getContactType());
        $this->assertSame(7, $target->getContactId());
        $this->assertSame('test@example.com', $target->getEmail());
        $this->assertSame('ABCD1234', $target->getFingerprint());
        $this->assertTrue($target->isFallbackToPassword());
    }

    public function testFluentSetters(): void
    {
        $target = new GPGTarget('employee', 1);

        $result = $target
            ->setEmail('emp@corp.com')
            ->setFingerprint('FP123')
            ->setFallbackToPassword(true);

        $this->assertSame($target, $result);
        $this->assertSame('emp@corp.com', $target->getEmail());
        $this->assertSame('FP123', $target->getFingerprint());
        $this->assertTrue($target->isFallbackToPassword());
    }
}
