<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\GPGServiceInterface;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\KeyserverInterface;
use Ksf\GPG\Contracts\EncryptionInterface;
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
     * @var KeyserverInterface|null
     */
    private ?KeyserverInterface $keyserver;

    /**
     * @var EncryptionInterface
     */
    private EncryptionInterface $encryption;

    /**
     * Constructor
     *
     * @param KeyManagerInterface $keyManager
     * @param EncryptionInterface $encryption
     * @param KeyserverInterface|null $keyserver
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        EncryptionInterface $encryption,
        ?KeyserverInterface $keyserver = null
    ) {
        $this->keyManager = $keyManager;
        $this->encryption = $encryption;
        $this->keyserver = $keyserver;
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

        // Use GnuPG to create detached signature
        $signedPath = $filePath . '.sig';
        $fingerprint = $key->getFingerprint()->getValue();
        
        $command = sprintf(
            'gpg --batch --yes --armor --detach-sign --local-user %s --output %s %s 2>&1',
            escapeshellarg($fingerprint),
            escapeshellarg($signedPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new SigningFailedException(
                "GPG signing failed: " . implode("\n", $output)
            );
        }

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

        // Use GnuPG to encrypt for recipient
        $encryptedPath = $filePath . '.gpg';
        $fingerprint = $key->getFingerprint()->getValue();
        
        $command = sprintf(
            'gpg --batch --yes --encrypt --recipient %s --output %s %s 2>&1',
            escapeshellarg($fingerprint),
            escapeshellarg($encryptedPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new EncryptionFailedException(
                "GPG encryption failed: " . implode("\n", $output)
            );
        }

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
        return $this->encryption->encrypt($filePath, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPassword(string $filePath, string $password): string
    {
        return $this->encryption->decrypt($filePath, $password);
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
}
