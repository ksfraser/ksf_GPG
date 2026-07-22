<?php
declare(strict_types=1);

namespace Ksf\GPG\Contracts;

use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Exception\EncryptionFailedException;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\KeyNotFoundException;
use Ksf\GPG\Exception\SigningFailedException;

/**
 * GPG Service Interface
 * 
 * Main entry point for GPG operations.
 * Provides signing, encryption, and key management.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-001 GPG Service Interface
 * 
 * @since 1.0.0
 */
interface GPGServiceInterface
{
    /**
     * Sign a file with GPG.
     *
     * @param string $filePath Path to file to sign
     * @param string $email Recipient email
     * @return EncryptedFile Signed file entity
     * @throws KeyNotFoundException If no key found for email
     * @throws SigningFailedException If signing fails
     *
     * @since 1.0.0
     */
    public function signFile(string $filePath, string $email): EncryptedFile;

    /**
     * Encrypt a file for a contact.
     *
     * @param string $filePath Path to file to encrypt
     * @param string $email Recipient email
     * @return EncryptedFile Encrypted file entity
     * @throws KeyNotFoundException If no key found for email
     * @throws EncryptionFailedException If encryption fails
     *
     * @since 1.0.0
     */
    public function encryptForContact(string $filePath, string $email): EncryptedFile;

    /**
     * Sign and encrypt a file.
     *
     * @param string $filePath Path to file
     * @param string $email Recipient email
     * @return EncryptedFile Signed and encrypted file entity
     * @throws KeyNotFoundException If no key found for email
     * @throws GPGException If operation fails
     *
     * @since 1.0.0
     */
    public function signAndEncrypt(string $filePath, string $email): EncryptedFile;

    /**
     * Encrypt a file with password (symmetric encryption).
     *
     * @param string $filePath Path to file to encrypt
     * @param string $password Password for encryption
     * @return EncryptedFile Encrypted file entity
     * @throws EncryptionFailedException If encryption fails
     *
     * @since 1.0.0
     */
    public function encryptWithPassword(string $filePath, string $password): EncryptedFile;

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
    public function decryptWithPassword(string $filePath, string $password): string;

    /**
     * Generate a new GPG key pair.
     *
     * @param string $email Email address for the key
     * @param string $passphrase Passphrase for the private key
     * @return KeyPair Generated key pair
     * @throws GPGException If key generation fails
     *
     * @since 1.0.0
     */
    public function generateKey(string $email, string $passphrase): KeyPair;

    /**
     * Check if we have a key for an email.
     *
     * @param string $email Email to check
     * @return bool True if key exists
     *
     * @since 1.0.0
     */
    public function hasKeyForEmail(string $email): bool;

    /**
     * Get a key by email.
     *
     * @param string $email Email to lookup
     * @return GPGKey|null Key if found, null otherwise
     *
     * @since 1.0.0
     */
    public function getKeyByEmail(string $email): ?GPGKey;

    /**
     * Get contact email from contact type and ID.
     *
     * @param string $contactType Type of contact (customer, employee, supplier)
     * @param int $contactId Contact ID
     * @return string|null Email address if found
     *
     * @since 1.0.0
     */
    public function getContactEmail(string $contactType, int $contactId): ?string;
}
