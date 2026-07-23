<?php
declare(strict_types=1);

namespace ksfraser\GPG\Contracts;

/**
 * Contact Resolver Interface
 *
 * Resolves contact_type + contact_id to an email address.
 * Platform adapters (FA, WordPress) implement this to provide
 * contact lookup for their specific schema.
 *
 * @since 1.0.0
 */
interface ContactResolverInterface
{
    /**
     * Resolve a contact to their email address.
     *
     * @param string $contactType Contact type (customer, supplier, employee, user)
     * @param int    $contactId   Contact ID
     * @return string|null Email address or null if not found
     *
     * @since 1.0.0
     */
    public function resolveEmail(string $contactType, int $contactId): ?string;
}
