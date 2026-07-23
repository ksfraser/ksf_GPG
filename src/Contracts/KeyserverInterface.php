<?php
declare(strict_types=1);

namespace ksfraser\GPG\Contracts;

use ksfraser\GPG\Entity\GPGKey;
use ksfraser\GPG\Exception\KeyserverException;

/**
 * Keyserver Interface
 * 
 * Provides keyserver operations: publish, search, import.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-003 Keyserver Integration
 * 
 * @since 1.0.0
 */
interface KeyserverInterface
{
    /**
     * Publish a public key to the keyserver.
     *
     * @param string $keyId Key ID to publish
     * @return bool True if published
     * @throws KeyserverException If publish fails
     *
     * @since 1.0.0
     */
    public function publish(string $keyId): bool;

    /**
     * Search for keys by email.
     *
     * @param string $email Email to search
     * @return GPGKey[] Array of matching keys
     * @throws KeyserverException If search fails
     *
     * @since 1.0.0
     */
    public function search(string $email): array;

    /**
     * Import a key from the keyserver.
     *
     * @param string $keyId Key ID to import
     * @return GPGKey Imported key
     * @throws KeyserverException If import fails
     *
     * @since 1.0.0
     */
    public function import(string $keyId): GPGKey;

    /**
     * Get the keyserver URL.
     *
     * @return string Keyserver URL
     *
     * @since 1.0.0
     */
    public function getKeyserverUrl(): string;
}
