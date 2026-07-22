<?php
declare(strict_types=1);

namespace Ksf\GPG\Contracts;

use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\KeyNotFoundException;

/**
 * Key Manager Interface
 * 
 * Manages GPG key lifecycle: generate, import, export, delete.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-002 Key Management
 * 
 * @since 1.0.0
 */
interface KeyManagerInterface
{
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
     * Import a public key.
     *
     * @param string $keyContent Public key content
     * @return GPGKey Imported key
     * @throws GPGException If import fails
     *
     * @since 1.0.0
     */
    public function importPublicKey(string $keyContent): GPGKey;

    /**
     * Import a private key.
     *
     * @param string $keyContent Private key content
     * @param string $passphrase Passphrase for the key
     * @return GPGKey Imported key
     * @throws GPGException If import fails
     *
     * @since 1.0.0
     */
    public function importPrivateKey(string $keyContent, string $passphrase): GPGKey;

    /**
     * Export a public key.
     *
     * @param string $keyId Key ID
     * @return string Public key content
     * @throws KeyNotFoundException If key not found
     *
     * @since 1.0.0
     */
    public function exportPublicKey(string $keyId): string;

    /**
     * Get key fingerprint.
     *
     * @param string $keyId Key ID
     * @return string Fingerprint
     * @throws KeyNotFoundException If key not found
     *
     * @since 1.0.0
     */
    public function getFingerprint(string $keyId): string;

    /**
     * List all keys.
     *
     * @return GPGKey[] Array of keys
     *
     * @since 1.0.0
     */
    public function listKeys(): array;

    /**
     * Get a key by ID.
     *
     * @param string $keyId Key ID
     * @return GPGKey Key entity
     * @throws KeyNotFoundException If key not found
     *
     * @since 1.0.0
     */
    public function getKeyById(string $keyId): GPGKey;

    /**
     * Get a key by email.
     *
     * @param string $email Email address
     * @return GPGKey|null Key if found, null otherwise
     *
     * @since 1.0.0
     */
    public function getKeyByEmail(string $email): ?GPGKey;

    /**
     * Delete a key.
     *
     * @param string $keyId Key ID
     * @param string $passphrase Passphrase for the key
     * @return bool True if deleted
     * @throws GPGException If deletion fails
     *
     * @since 1.0.0
     */
    public function deleteKey(string $keyId, string $passphrase): bool;
}
