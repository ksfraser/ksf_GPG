<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\EncryptionInterface;
use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Adapter\GnuPGAdapterFactory;
use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Exception\EncryptionFailedException;

/**
 * Password Encryption Service
 * 
 * Provides password-based symmetric encryption.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-006 Password-Based Encryption
 * 
 * @since 1.0.0
 */
class PasswordEncryptionService implements EncryptionInterface
{
    /**
     * @var GnuPGAdapterInterface
     */
    private GnuPGAdapterInterface $adapter;

    /**
     * Constructor
     *
     * @param GnuPGAdapterInterface|null $adapter If null, auto-detects best available
     *
     * @since 1.0.0
     */
    public function __construct(?GnuPGAdapterInterface $adapter = null)
    {
        $this->adapter = $adapter ?? GnuPGAdapterFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $filePath, string $password): EncryptedFile
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
    public function decrypt(string $filePath, string $password): string
    {
        return $this->adapter->decryptWithPassword($filePath, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePassword(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
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
