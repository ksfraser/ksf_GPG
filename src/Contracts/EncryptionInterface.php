<?php
declare(strict_types=1);

namespace ksfraser\GPG\Contracts;

use ksfraser\GPG\Entity\EncryptedFile;
use ksfraser\GPG\Exception\EncryptionFailedException;

/**
 * Encryption Interface
 * 
 * Provides password-based symmetric encryption.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-006 Password-Based Encryption
 * 
 * @since 1.0.0
 */
interface EncryptionInterface
{
    /**
     * Encrypt a file with password.
     *
     * @param string $filePath Path to file to encrypt
     * @param string $password Password for encryption
     * @return EncryptedFile Encrypted file entity
     * @throws EncryptionFailedException If encryption fails
     *
     * @since 1.0.0
     */
    public function encrypt(string $filePath, string $password): EncryptedFile;

    /**
     * Decrypt a file with password.
     *
     * @param string $filePath Path to encrypted file
     * @param string $password Password for decryption
     * @return string Path to decrypted file
     * @throws EncryptionFailedException If decryption fails
     *
     * @since 1.0.0
     */
    public function decrypt(string $filePath, string $password): string;

    /**
     * Generate a secure random password.
     *
     * @param int $length Password length (default: 32)
     * @return string Generated password
     *
     * @since 1.0.0
     */
    public function generatePassword(int $length = 32): string;
}
