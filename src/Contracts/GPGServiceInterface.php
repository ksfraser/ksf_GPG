<?php
declare(strict_types=1);

namespace Ksf\GPG\Contracts;

use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Hook\GPGHookRequest;
use Ksf\GPG\Hook\GPGHookResponse;

/**
 * GPG Service Interface
 *
 * Main entry point for GPG operations.
 *
 * @since 1.0.0
 */
interface GPGServiceInterface
{
    public function signFile(string $filePath, string $email): EncryptedFile;
    public function encryptForContact(string $filePath, string $email): EncryptedFile;
    public function signAndEncrypt(string $filePath, string $email): EncryptedFile;
    public function encryptWithPassword(string $filePath, string $password): EncryptedFile;
    public function decryptWithPassword(string $filePath, string $password): string;
    public function generateKey(string $email, string $passphrase): KeyPair;
    public function hasKeyForEmail(string $email): bool;
    public function getKeyByEmail(string $email): ?GPGKey;
    public function getContactEmail(string $contactType, int $contactId): ?string;

    /**
     * Process a hook request with full DTO support.
     *
     * Orchestrates contact resolution, key lookup, signing, encryption,
     * and per-target result collection.
     *
     * @param GPGHookRequest $request
     * @param ContactResolverInterface|null $contactResolver
     * @param SigningKeyResolverInterface|null $signingKeyResolver
     * @return GPGHookResponse
     *
     * @since 1.1.0
     */
    public function processHookRequest(
        GPGHookRequest $request,
        ?ContactResolverInterface $contactResolver = null,
        ?SigningKeyResolverInterface $signingKeyResolver = null
    ): GPGHookResponse;
}
