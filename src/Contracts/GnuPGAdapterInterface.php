<?php
declare(strict_types=1);

namespace Ksf\GPG\Contracts;

/**
 * GnuPG Adapter Interface
 * 
 * Abstraction over GnuPG operations.
 * Implementations: CryptGpgAdapter (uses pear/crypt_gpg), CliGpgAdapter (uses exec fallback).
 * 
 * @since 1.0.0
 */
interface GnuPGAdapterInterface
{
    /**
     * Check if this adapter is available.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function isAvailable(): bool;

    /**
     * Generate a new GPG key pair.
     *
     * @param string $email
     * @param string $passphrase
     * @param string $keyType RSA|DSA|EdDSA
     * @param int $keyLength
     * @return array{key_id: string, fingerprint: string, public_key: string, private_key: string}
     *
     * @since 1.0.0
     */
    public function generateKey(
        string $email,
        string $passphrase,
        string $keyType = 'RSA',
        int $keyLength = 4096
    ): array;

    /**
     * Sign a file.
     *
     * @param string $filePath
     * @param string $fingerprint Sender's key fingerprint
     * @return string Path to signed file (.sig)
     *
     * @since 1.0.0
     */
    public function signFile(string $filePath, string $fingerprint): string;

    /**
     * Encrypt a file for a recipient.
     *
     * @param string $filePath
     * @param string $fingerprint Recipient's key fingerprint
     * @return string Path to encrypted file (.gpg)
     *
     * @since 1.0.0
     */
    public function encryptForRecipient(string $filePath, string $fingerprint): string;

    /**
     * Encrypt a file with password (symmetric).
     *
     * @param string $filePath
     * @param string $password
     * @return string Path to encrypted file (.gpg)
     *
     * @since 1.0.0
     */
    public function encryptWithPassword(string $filePath, string $password): string;

    /**
     * Decrypt a file with password.
     *
     * @param string $filePath
     * @param string $password
     * @return string Path to decrypted file
     *
     * @since 1.0.0
     */
    public function decryptWithPassword(string $filePath, string $password): string;

    /**
     * Import a key from ASCII-armored content.
     *
     * @param string $keyContent
     * @return array{key_id: string, fingerprint: string, email: string}
     *
     * @since 1.0.0
     */
    public function importKey(string $keyContent): array;

    /**
     * Export a public key.
     *
     * @param string $keyId
     * @return string ASCII-armored public key
     *
     * @since 1.0.0
     */
    public function exportPublicKey(string $keyId): string;

    /**
     * Delete a key from the keyring.
     *
     * @param string $keyId
     * @param string $passphrase
     * @return bool
     *
     * @since 1.0.0
     */
    public function deleteKey(string $keyId, string $passphrase): bool;

    /**
     * Publish a key to a keyserver.
     *
     * @param string $keyId
     * @param string $keyserverUrl
     * @return bool
     *
     * @since 1.0.0
     */
    public function publishToKeyserver(string $keyId, string $keyserverUrl): bool;

    /**
     * List all keys in the keyring.
     *
     * @return array<int, array{key_id: string, fingerprint: string, email: string, created: string}>
     *
     * @since 1.0.0
     */
    public function listKeys(): array;
}
