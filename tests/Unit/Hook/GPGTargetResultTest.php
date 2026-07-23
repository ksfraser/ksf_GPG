<?php
declare(strict_types=1);

namespace ksfraser\GPG\Tests\Unit\Hook;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\Hook\GPGTarget;
use ksfraser\GPG\Hook\GPGTargetResult;

class GPGTargetResultTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $target = new GPGTarget('customer', 42, 'test@example.com');
        $result = new GPGTargetResult($target);

        $this->assertSame($target, $result->getTarget());
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->getOutputPath());
        $this->assertFalse($result->isKeyFound());
        $this->assertFalse($result->isUsedPasswordFallback());
        $this->assertEmpty($result->getWarnings());
        $this->assertNull($result->getError());
    }

    public function testFluentSetters(): void
    {
        $target = new GPGTarget('customer', 42);
        $result = new GPGTargetResult($target);

        $result
            ->setSuccess(true)
            ->setOutputPath('/tmp/test.txt.gpg')
            ->setKeyFound(true)
            ->setUsedPasswordFallback(true);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('/tmp/test.txt.gpg', $result->getOutputPath());
        $this->assertTrue($result->isKeyFound());
        $this->assertTrue($result->isUsedPasswordFallback());
    }

    public function testAddWarning(): void
    {
        $target = new GPGTarget('customer', 42);
        $result = new GPGTargetResult($target);

        $result->addWarning('Key not found')->addWarning('Fallback to password');

        $this->assertCount(2, $result->getWarnings());
        $this->assertContains('Key not found', $result->getWarnings());
        $this->assertContains('Fallback to password', $result->getWarnings());
    }

    public function testSetErrorSetsSuccessFalse(): void
    {
        $target = new GPGTarget('customer', 42);
        $result = new GPGTargetResult($target);

        $result->setSuccess(true);
        $this->assertTrue($result->isSuccess());

        $result->setError('Something went wrong');
        $this->assertFalse($result->isSuccess());
        $this->assertSame('Something went wrong', $result->getError());
    }
}
