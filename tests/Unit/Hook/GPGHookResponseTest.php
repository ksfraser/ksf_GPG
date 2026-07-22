<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\Hook;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\Hook\GPGTarget;
use Ksf\GPG\Hook\GPGTargetResult;
use Ksf\GPG\Hook\GPGHookResponse;

class GPGHookResponseTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $response = new GPGHookResponse();

        $this->assertFalse($response->isSuccess());
        $this->assertEmpty($response->getResults());
        $this->assertEmpty($response->getWarnings());
    }

    public function testFluentSetters(): void
    {
        $response = new GPGHookResponse();

        $result = $response->setSuccess(true)->addWarning('Test warning');

        $this->assertSame($response, $result);
        $this->assertTrue($response->isSuccess());
        $this->assertContains('Test warning', $response->getWarnings());
    }

    public function testAddResultAndGetResults(): void
    {
        $response = new GPGHookResponse();
        $target1 = new GPGTarget('customer', 1);
        $target2 = new GPGTarget('supplier', 2);

        $result1 = new GPGTargetResult($target1);
        $result1->setSuccess(true);
        $result2 = new GPGTargetResult($target2);
        $result2->setSuccess(false);

        $response->addResult($result1)->addResult($result2);

        $this->assertCount(2, $response->getResults());
    }

    public function testSuccessAndFailureCounts(): void
    {
        $response = new GPGHookResponse();

        $t1 = new GPGTarget('customer', 1);
        $t2 = new GPGTarget('customer', 2);
        $t3 = new GPGTarget('customer', 3);

        $r1 = new GPGTargetResult($t1);
        $r1->setSuccess(true);
        $r2 = new GPGTargetResult($t2);
        $r2->setSuccess(true);
        $r3 = new GPGTargetResult($t3);
        $r3->setSuccess(false);

        $response->addResult($r1)->addResult($r2)->addResult($r3);

        $this->assertSame(2, $response->getSuccessCount());
        $this->assertSame(1, $response->getFailureCount());
    }

    public function testGetFirstOutputPath(): void
    {
        $response = new GPGHookResponse();

        $this->assertNull($response->getFirstOutputPath());

        $target = new GPGTarget('customer', 1);
        $result = new GPGTargetResult($target);
        $result->setSuccess(true)->setOutputPath('/tmp/out1.gpg');
        $response->addResult($result);

        $this->assertSame('/tmp/out1.gpg', $response->getFirstOutputPath());
    }

    public function testGetFirstOutputPathSkipsFailed(): void
    {
        $response = new GPGHookResponse();

        $t1 = new GPGTarget('customer', 1);
        $r1 = new GPGTargetResult($t1);
        $r1->setSuccess(false);
        $response->addResult($r1);

        $t2 = new GPGTarget('customer', 2);
        $r2 = new GPGTargetResult($t2);
        $r2->setSuccess(true)->setOutputPath('/tmp/out2.gpg');
        $response->addResult($r2);

        $this->assertSame('/tmp/out2.gpg', $response->getFirstOutputPath());
    }
}
