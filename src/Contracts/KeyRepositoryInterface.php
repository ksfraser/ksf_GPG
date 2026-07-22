<?php
declare(strict_types=1);

namespace Ksf\GPG\Contracts;

use Ksf\GPG\Entity\GPGKey;

/**
 * Key Repository Interface
 * 
 * Data access abstraction for GPG keys.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-010 Repository Interfaces
 * 
 * @since 1.0.0
 */
interface KeyRepositoryInterface
{
    /**
     * Find a key by ID.
     *
     * @param string $keyId Key ID
     * @return GPGKey|null Key if found
     *
     * @since 1.0.0
     */
    public function findById(string $keyId): ?GPGKey;

    /**
     * Find a key by fingerprint.
     *
     * @param string $fingerprint Key fingerprint
     * @return GPGKey|null Key if found
     *
     * @since 1.0.0
     */
    public function findByFingerprint(string $fingerprint): ?GPGKey;

    /**
     * Find keys by email.
     *
     * @param string $email Email address
     * @return GPGKey[] Array of keys
     *
     * @since 1.0.0
     */
    public function findByEmail(string $email): array;

    /**
     * Find keys by contact.
     *
     * @param string $contactType Contact type (customer, employee, supplier)
     * @param int $contactId Contact ID
     * @return GPGKey[] Array of keys
     *
     * @since 1.0.0
     */
    public function findByContact(string $contactType, int $contactId): array;

    /**
     * Save a key.
     *
     * @param GPGKey $key Key entity
     * @return bool True if saved
     *
     * @since 1.0.0
     */
    public function save(GPGKey $key): bool;

    /**
     * Delete a key.
     *
     * @param string $keyId Key ID
     * @return bool True if deleted
     *
     * @since 1.0.0
     */
    public function delete(string $keyId): bool;

    /**
     * Check if key exists.
     *
     * @param string $keyId Key ID
     * @return bool True if exists
     *
     * @since 1.0.0
     */
    public function exists(string $keyId): bool;
}
