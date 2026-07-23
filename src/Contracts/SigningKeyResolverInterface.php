<?php
declare(strict_types=1);

namespace ksfraser\GPG\Contracts;

/**
 * Signing Key Resolver Interface
 *
 * Resolves the appropriate signing key for a sender.
 * Supports fallback hierarchy: user → team → department → company.
 *
 * @since 1.0.0
 */
interface SigningKeyResolverInterface
{
    /**
     * Resolve the signing key fingerprint for a sender.
     *
     * @param string $senderContactType Sender's contact type
     * @param int    $senderContactId   Sender's contact ID
     * @return string|null Fingerprint of the signing key, or null if none found
     *
     * @since 1.0.0
     */
    public function resolveSigningKey(string $senderContactType, int $senderContactId): ?string;

    /**
     * Resolve the signing key fingerprint for a user by email.
     *
     * @param string $email Sender's email address
     * @return string|null Fingerprint of the signing key, or null if none found
     *
     * @since 1.0.0
     */
    public function resolveSigningKeyByEmail(string $email): ?string;
}
