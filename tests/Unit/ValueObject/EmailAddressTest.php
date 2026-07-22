<?php
declare(strict_types=1);

namespace Ksf\GPG\Tests\Unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\Exception\InvalidEmailException;

class EmailAddressTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new EmailAddress('test@example.com');
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function testEmailTrimmed(): void
    {
        $email = new EmailAddress('  test@example.com  ');
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function testEmailLowercase(): void
    {
        $email = new EmailAddress('Test@Example.COM');
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function testInvalidEmailNoAt(): void
    {
        $this->expectException(InvalidEmailException::class);
        new EmailAddress('testexample.com');
    }

    public function testInvalidEmailNoDomain(): void
    {
        $this->expectException(InvalidEmailException::class);
        new EmailAddress('test@');
    }

    public function testGetLocalPart(): void
    {
        $email = new EmailAddress('test@example.com');
        $this->assertSame('test', $email->getLocalPart());
    }

    public function testGetDomain(): void
    {
        $email = new EmailAddress('test@example.com');
        $this->assertSame('example.com', $email->getDomain());
    }

    public function testEquals(): void
    {
        $email1 = new EmailAddress('test@example.com');
        $email2 = new EmailAddress('test@example.com');
        $email3 = new EmailAddress('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function testToString(): void
    {
        $email = new EmailAddress('test@example.com');
        $this->assertSame('test@example.com', (string) $email);
    }
}
