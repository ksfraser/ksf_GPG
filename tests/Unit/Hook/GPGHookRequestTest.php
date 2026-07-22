<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Hook;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Hook\GPGHookRequest;
use Ksf\GPG\Hook\GPGTarget;

class GPGHookRequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new GPGHookRequest('/tmp/test.txt', GPGHookRequest::OPERATION_SIGN);

        $this->assertSame('/tmp/test.txt', $request->getFilePath());
        $this->assertSame('sign', $request->getOperation());
        $this->assertEmpty($request->getTargets());
        $this->assertNull($request->getSenderContactType());
        $this->assertNull($request->getSenderContactId());
        $this->assertNull($request->getPassword());
        $this->assertTrue($request->isAsciiArmor());
    }

    public function testAddTarget(): void
    {
        $request = new GPGHookRequest('/tmp/test.txt', GPGHookRequest::OPERATION_ENCRYPT);
        $target1 = new GPGTarget('customer', 1);
        $target2 = new GPGTarget('supplier', 2);

        $result = $request->addTarget($target1)->addTarget($target2);

        $this->assertSame($request, $result);
        $this->assertCount(2, $request->getTargets());
        $this->assertSame($target1, $request->getTargets()[0]);
        $this->assertSame($target2, $request->getTargets()[1]);
    }

    public function testFluentSetters(): void
    {
        $request = new GPGHookRequest('/tmp/test.txt', GPGHookRequest::OPERATION_ENCRYPT);

        $result = $request
            ->setSenderContactType('employee')
            ->setSenderContactId(5)
            ->setPassword('secret')
            ->setAsciiArmor(false);

        $this->assertSame($request, $result);
        $this->assertSame('employee', $request->getSenderContactType());
        $this->assertSame(5, $request->getSenderContactId());
        $this->assertSame('secret', $request->getPassword());
        $this->assertFalse($request->isAsciiArmor());
    }

    public function testOperationConstants(): void
    {
        $this->assertSame('sign', GPGHookRequest::OPERATION_SIGN);
        $this->assertSame('encrypt', GPGHookRequest::OPERATION_ENCRYPT);
        $this->assertSame('sign_encrypt', GPGHookRequest::OPERATION_SIGN_ENCRYPT);
        $this->assertSame('password_encrypt', GPGHookRequest::OPERATION_PASSWORD_ENCRYPT);
    }
}
