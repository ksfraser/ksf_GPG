<?php
declare(strict_types=1);

namespace ksfraser\GPG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\Services\GPGService;
use ksfraser\GPG\Contracts\KeyManagerInterface;
use ksfraser\GPG\Contracts\GnuPGAdapterInterface;
use ksfraser\GPG\Contracts\ContactResolverInterface;
use ksfraser\GPG\Contracts\SigningKeyResolverInterface;
use ksfraser\GPG\Entity\GPGKey;
use ksfraser\GPG\ValueObject\KeyId;
use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\ValueObject\EmailAddress;
use ksfraser\GPG\Hook\GPGHookRequest;
use ksfraser\GPG\Hook\GPGTarget;
use ksfraser\GPG\Exception\KeyNotFoundException;

class GPGServiceTest extends TestCase
{
    private GPGService $service;
    private KeyManagerInterface $keyManager;
    private GnuPGAdapterInterface $adapter;

    protected function setUp(): void
    {
        $this->keyManager = $this->createMock(KeyManagerInterface::class);
        $this->adapter = $this->createMock(GnuPGAdapterInterface::class);
        
        $this->service = new GPGService(
            $this->keyManager,
            $this->adapter
        );
    }

    public function testHasKeyForEmailReturnsTrue(): void
    {
        $key = $this->createMock(GPGKey::class);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->assertTrue($this->service->hasKeyForEmail('test@example.com'));
    }

    public function testHasKeyForEmailReturnsFalse(): void
    {
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->assertFalse($this->service->hasKeyForEmail('test@example.com'));
    }

    public function testGetKeyByEmailReturnsKey(): void
    {
        $key = $this->createMock(GPGKey::class);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->assertSame($key, $this->service->getKeyByEmail('test@example.com'));
    }

    public function testGetKeyByEmailReturnsNull(): void
    {
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->assertNull($this->service->getKeyByEmail('test@example.com'));
    }

    public function testEncryptForContactThrowsWhenKeyNotFound(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->expectException(KeyNotFoundException::class);
        $this->service->encryptForContact($tempFile, 'test@example.com');
        
        unlink($tempFile);
    }

    public function testSignFileThrowsWhenKeyNotFound(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->expectException(KeyNotFoundException::class);
        $this->service->signFile($tempFile, 'test@example.com');
        
        unlink($tempFile);
    }

    public function testSignFileDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $signedPath = $tempFile . '.sig';
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('signFile')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($signedPath);
        
        $result = $this->service->signFile($tempFile, 'test@example.com');
        
        $this->assertSame($signedPath, $result->getSignedPath());
        $this->assertTrue($result->isSigned());
        $this->assertSame('test@example.com', $result->getRecipientEmail());
        
        unlink($tempFile);
    }

    public function testEncryptForContactDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('encryptForRecipient')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($encryptedPath);
        
        $result = $this->service->encryptForContact($tempFile, 'test@example.com');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertSame('test@example.com', $result->getRecipientEmail());
        
        unlink($tempFile);
    }

    public function testSignAndEncryptDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        $signedPath = $encryptedPath . '.sig';
        
        // Create the encrypted file so signFile's file_exists check passes
        file_put_contents($encryptedPath, 'encrypted content');
        
        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);
        
        $this->keyManager->expects($this->exactly(2))
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);
        
        $this->adapter->expects($this->once())
            ->method('encryptForRecipient')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($encryptedPath);
        
        $this->adapter->expects($this->once())
            ->method('signFile')
            ->with($encryptedPath, $fingerprint->getValue())
            ->willReturn($signedPath);
        
        $result = $this->service->signAndEncrypt($tempFile, 'test@example.com');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertSame($signedPath, $result->getSignedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertTrue($result->isSigned());
        
        unlink($tempFile);
        unlink($encryptedPath);
    }

    public function testEncryptWithPasswordDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';
        
        $this->adapter->expects($this->once())
            ->method('encryptWithPassword')
            ->with($tempFile, 'secret-password')
            ->willReturn($encryptedPath);
        
        $result = $this->service->encryptWithPassword($tempFile, 'secret-password');
        
        $this->assertSame($encryptedPath, $result->getEncryptedPath());
        $this->assertTrue($result->isEncrypted());
        $this->assertTrue($result->isPasswordProtected());
        
        unlink($tempFile);
    }

    public function testDecryptWithPasswordDelegatesToAdapter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        $encryptedFile = $tempFile . '.gpg';
        file_put_contents($encryptedFile, 'encrypted content');
        
        $this->adapter->expects($this->once())
            ->method('decryptWithPassword')
            ->with($encryptedFile, 'secret-password')
            ->willReturn($tempFile);
        
        $result = $this->service->decryptWithPassword($encryptedFile, 'secret-password');
        
        $this->assertSame($tempFile, $result);
        
        unlink($encryptedFile);
    }

    // =========================================================================
    // processHookRequest tests
    // =========================================================================

    public function testProcessHookRequestFileNotFound(): void
    {
        $request = new GPGHookRequest('/nonexistent/file.txt', GPGHookRequest::OPERATION_SIGN);
        $request->addTarget(new GPGTarget('customer', 1, 'test@example.com'));

        $response = $this->service->processHookRequest($request);

        $this->assertFalse($response->isSuccess());
        $this->assertContains('File not found: /nonexistent/file.txt', $response->getWarnings());
    }

    public function testProcessHookRequestSignSuccess(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $signedPath = $tempFile . '.sig';

        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);

        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('test@example.com')
            ->willReturn($key);

        $this->adapter->expects($this->once())
            ->method('signFile')
            ->with($tempFile, $fingerprint->getValue())
            ->willReturn($signedPath);

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_SIGN);
        $request->addTarget(new GPGTarget('customer', 1, 'test@example.com'));

        $response = $this->service->processHookRequest($request);

        $this->assertTrue($response->isSuccess());
        $this->assertCount(1, $response->getResults());
        $this->assertTrue($response->getResults()[0]->isSuccess());
        $this->assertSame($signedPath, $response->getFirstOutputPath());

        unlink($tempFile);
    }

    public function testProcessHookRequestEncryptMultiRecipient(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';

        $fp1 = new Fingerprint(str_repeat('AA', 20));
        $fp2 = new Fingerprint(str_repeat('BB', 20));
        $key1 = $this->createMock(GPGKey::class);
        $key1->method('getFingerprint')->willReturn($fp1);
        $key2 = $this->createMock(GPGKey::class);
        $key2->method('getFingerprint')->willReturn($fp2);

        $this->keyManager->expects($this->exactly(2))
            ->method('getKeyByEmail')
            ->willReturnMap([
                ['alice@example.com', $key1],
                ['bob@example.com', $key2],
            ]);

        $this->adapter->expects($this->once())
            ->method('encryptForRecipients')
            ->with($tempFile, [$fp1->getValue(), $fp2->getValue()])
            ->willReturn($encryptedPath);

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_ENCRYPT);
        $request->addTarget(new GPGTarget('customer', 1, 'alice@example.com'));
        $request->addTarget(new GPGTarget('customer', 2, 'bob@example.com'));

        $response = $this->service->processHookRequest($request);

        $this->assertTrue($response->isSuccess());
        $this->assertCount(2, $response->getResults());
        $this->assertSame(2, $response->getSuccessCount());
        $this->assertSame($encryptedPath, $response->getFirstOutputPath());

        unlink($tempFile);
    }

    public function testProcessHookRequestEncryptPartialFailure(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';

        $fp1 = new Fingerprint(str_repeat('AA', 20));
        $key1 = $this->createMock(GPGKey::class);
        $key1->method('getFingerprint')->willReturn($fp1);

        // alice has key, bob does not
        $this->keyManager->expects($this->exactly(2))
            ->method('getKeyByEmail')
            ->willReturnMap([
                ['alice@example.com', $key1],
                ['bob@example.com', null],
            ]);

        $this->adapter->expects($this->once())
            ->method('encryptForRecipients')
            ->with($tempFile, [$fp1->getValue()])
            ->willReturn($encryptedPath);

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_ENCRYPT);
        $request->addTarget(new GPGTarget('customer', 1, 'alice@example.com'));
        $request->addTarget(new GPGTarget('customer', 2, 'bob@example.com'));

        $response = $this->service->processHookRequest($request);

        // Overall success is true — we produced an encrypted file
        // bob gets a warning but still gets the output path
        $this->assertTrue($response->isSuccess());
        $this->assertCount(2, $response->getResults());
        // alice succeeded with key
        $this->assertTrue($response->getResults()[0]->isSuccess());
        $this->assertTrue($response->getResults()[0]->isKeyFound());
        // bob had no key but still gets the encrypted file + warning
        $this->assertTrue($response->getResults()[1]->isSuccess());
        $this->assertFalse($response->getResults()[1]->isKeyFound());
        $this->assertNotEmpty($response->getResults()[1]->getWarnings());
        $this->assertSame($encryptedPath, $response->getResults()[1]->getOutputPath());

        unlink($tempFile);
    }

    public function testProcessHookRequestPasswordEncrypt(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $encryptedPath = $tempFile . '.gpg';

        $this->adapter->expects($this->once())
            ->method('encryptWithPassword')
            ->with($tempFile, 'my-password')
            ->willReturn($encryptedPath);

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_PASSWORD_ENCRYPT);
        $request->setPassword('my-password');
        $request->addTarget(new GPGTarget('customer', 1));

        $response = $this->service->processHookRequest($request);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->getResults()[0]->isUsedPasswordFallback());
        $this->assertSame($encryptedPath, $response->getFirstOutputPath());

        unlink($tempFile);
    }

    public function testProcessHookRequestUnknownOperation(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');

        $request = new GPGHookRequest($tempFile, 'unknown_op');
        $request->addTarget(new GPGTarget('customer', 1, 'test@example.com'));

        $response = $this->service->processHookRequest($request);

        $this->assertFalse($response->isSuccess());
        $this->assertContains('Unknown operation: unknown_op', $response->getWarnings());

        unlink($tempFile);
    }

    public function testProcessHookRequestWithContactResolver(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');
        $signedPath = $tempFile . '.sig';

        $fingerprint = new Fingerprint(str_repeat('AB', 20));
        $key = $this->createMock(GPGKey::class);
        $key->method('getFingerprint')->willReturn($fingerprint);

        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->with('resolved@example.com')
            ->willReturn($key);

        $this->adapter->expects($this->once())
            ->method('signFile')
            ->willReturn($signedPath);

        $contactResolver = $this->createMock(ContactResolverInterface::class);
        $contactResolver->expects($this->once())
            ->method('resolveEmail')
            ->with('customer', 42)
            ->willReturn('resolved@example.com');

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_SIGN);
        $request->addTarget(new GPGTarget('customer', 42));

        $response = $this->service->processHookRequest($request, $contactResolver);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('resolved@example.com', $response->getResults()[0]->getTarget()->getEmail());

        unlink($tempFile);
    }

    public function testProcessHookRequestNoEmailNoKey(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_SIGN);
        $request->addTarget(new GPGTarget('customer', 99));

        $response = $this->service->processHookRequest($request);

        // Sign with no key = failure (can't sign without a key)
        $this->assertFalse($response->isSuccess());
        $this->assertNotEmpty($response->getResults()[0]->getWarnings());

        unlink($tempFile);
    }

    public function testProcessHookRequestEncryptNoKeysReturnsOriginal(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_test_');
        file_put_contents($tempFile, 'test content');

        // No keys anywhere — encrypt should return original file
        $this->keyManager->expects($this->once())
            ->method('getKeyByEmail')
            ->willReturn(null);

        $request = new GPGHookRequest($tempFile, GPGHookRequest::OPERATION_ENCRYPT);
        $request->addTarget(new GPGTarget('customer', 1, 'nokey@example.com'));

        $response = $this->service->processHookRequest($request);

        $this->assertTrue($response->isSuccess());
        $this->assertSame($tempFile, $response->getFirstOutputPath());
        $this->assertNotEmpty($response->getWarnings());

        unlink($tempFile);
    }
}
