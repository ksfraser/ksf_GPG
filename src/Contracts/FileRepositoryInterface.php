<?php
declare(strict_types=1);

namespace ksfraser\GPG\Contracts;

use ksfraser\GPG\Entity\EncryptedFile;

/**
 * File Repository Interface
 * 
 * Data access abstraction for encrypted files.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-010 Repository Interfaces
 * 
 * @since 1.0.0
 */
interface FileRepositoryInterface
{
    /**
     * Find a file by ID.
     *
     * @param int $id File ID
     * @return EncryptedFile|null File if found
     *
     * @since 1.0.0
     */
    public function findById(int $id): ?EncryptedFile;

    /**
     * Find files by contact.
     *
     * @param string $contactType Contact type
     * @param int $contactId Contact ID
     * @return EncryptedFile[] Array of files
     *
     * @since 1.0.0
     */
    public function findByContact(string $contactType, int $contactId): array;

    /**
     * Find a file by original path.
     *
     * @param string $originalPath Original file path
     * @return EncryptedFile|null File if found
     *
     * @since 1.0.0
     */
    public function findByOriginalPath(string $originalPath): ?EncryptedFile;

    /**
     * Save a file record.
     *
     * @param EncryptedFile $file File entity
     * @return bool True if saved
     *
     * @since 1.0.0
     */
    public function save(EncryptedFile $file): bool;

    /**
     * Delete a file record.
     *
     * @param int $id File ID
     * @return bool True if deleted
     *
     * @since 1.0.0
     */
    public function delete(int $id): bool;

    /**
     * Find files needing backup.
     *
     * @return EncryptedFile[] Array of files not yet backed up
     *
     * @since 1.0.0
     */
    public function findPendingBackup(): array;

    /**
     * Mark file as backed up.
     *
     * @param int $id File ID
     * @return bool True if marked
     *
     * @since 1.0.0
     */
    public function markBackedUp(int $id): bool;
}
