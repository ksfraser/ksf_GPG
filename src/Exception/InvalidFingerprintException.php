<?php
declare(strict_types=1);

namespace ksfraser\GPG\Exception;

/**
 * Invalid Fingerprint Exception
 * 
 * Thrown when a fingerprint format is invalid.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-011 Exception Hierarchy
 * 
 * @since 1.0.0
 */
class InvalidFingerprintException extends GPGException
{
    /**
     * Constructor
     *
     * @param string $fingerprint
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @since 1.0.0
     */
    public function __construct(
        string $fingerprint,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(
            "Invalid GPG fingerprint format: {$fingerprint}",
            $code,
            $previous
        );
    }
}
