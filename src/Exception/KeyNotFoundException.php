<?php
declare(strict_types=1);

namespace ksfraser\GPG\Exception;

/**
 * Key Not Found Exception
 * 
 * Thrown when a requested GPG key is not found.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-011 Exception Hierarchy
 * 
 * @since 1.0.0
 */
class KeyNotFoundException extends GPGException
{
    /**
     * Constructor
     *
     * @param string $identifier Key ID, fingerprint, or email
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @since 1.0.0
     */
    public function __construct(
        string $identifier,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(
            "GPG key not found: {$identifier}",
            $code,
            $previous
        );
    }
}
