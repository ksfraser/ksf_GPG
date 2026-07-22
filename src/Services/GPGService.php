<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\GPGServiceInterface;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Adapter\GnuPGAdapterFactory;
use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\KeyNotFoundException;
use Ksf\GPG\Exception\SigningFailedException;
use Ksf\GPG\Exception\EncryptionFailedException;

/**
 * GPG Service
 * 
 * Main entry point for GPG operations.
 * Provides signing, encryption, and key management.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-001 GPG Service Implementation
 * 
 * @since 1.0.0
 */
class GPGService implements GPGServiceInterface
{
    /**
     * @var KeyManagerInterface
     */
    private KeyManagerInterface $keyManager;

    /**
     * @var GnuPGAdapterInterface
     */
    private GnuPGAdapterInterface $adapter;

    /**
     * Constructor
     *
     * @param KeyManagerInterface $keyManager
     * @param GnuPGAdapterInterface|null $adapter If null, auto-detects best available
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        ?GnuPGAdapterInterface $adapter = null
    ) {
        $this->keyManager = $keyManager;
        $this->adapter = $adapter ?? GnuPGAdapterFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function signFile(string $filePath, string $email): EncryptedFile
    {
        if (!file_exists($filePath)) {
            throw new SigningFailedException("File not found: {$filePath}");
        }

        $key = $this->keyManager->getKeyByEmail($email);
        
        if ($key === null) {
            throw new KeyNotFoundException($email);
        }

        $signedPath = $this->adapter->signFile(
            $filePath,
            $key->getFingerprint()->getValue()
        );

        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setSignedPath($signedPath);
        $encryptedFile->setRecipientEmail($email);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptForContact(string $filePath, string $email): EncryptedFile
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }

        $key = $this->keyManager->getKeyByEmail($email);
        
        if ($key === null) {
            throw new KeyNotFoundException($email);
        }

        $encryptedPath = $this->adapter->encryptForRecipient(
            $filePath,
            $key->getFingerprint()->getValue()
        );

        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setEncryptedPath($encryptedPath);
        $encryptedFile->setRecipientEmail($email);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function signAndEncrypt(string $filePath, string $email): EncryptedFile
    {
        // First encrypt, then sign the encrypted file
        $encryptedFile = $this->encryptForContact($filePath, $email);
        $encryptedPath = $encryptedFile->getEncryptedPath();
        
        // Sign the encrypted file
        $signedFile = $this->signFile($encryptedPath, $email);
        
        // Update the original encrypted file with signed path
        $encryptedFile->setSignedPath($signedFile->getSignedPath());
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPassword(string $filePath, string $password): EncryptedFile
    {
        $encryptedPath = $this->adapter->encryptWithPassword($filePath, $password);
        
        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setEncryptedPath($encryptedPath);
        $encryptedFile->setPasswordProtected(true);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPassword(string $filePath, string $password): string
    {
        return $this->adapter->decryptWithPassword($filePath, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey(string $email, string $passphrase): KeyPair
    {
        return $this->keyManager->generateKey($email, $passphrase);
    }

    /**
     * {@inheritdoc}
     */
    public function hasKeyForEmail(string $email): bool
    {
        return $this->keyManager->getKeyByEmail($email) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyByEmail(string $email): ?GPGKey
    {
        return $this->keyManager->getKeyByEmail($email);
    }

    /**
     * {@inheritdoc}
     */
    public function getContactEmail(string $contactType, int $contactId): ?string
    {
        // This would be implemented by the platform adapter (FA)
        // For now, return null
        return null;
    }

    /**
     * Get the underlying GnuPG adapter.
     *
     * @return GnuPGAdapterInterface
     *
     * @since 1.0.0
     */
    public function getAdapter(): GnuPGAdapterInterface
    {
        return $this->adapter;
    }
}
