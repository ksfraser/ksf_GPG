<?php
declare(strict_types=1);

namespace Ksf\GPG\Exception;

/**
 * Invalid Email Exception
 * 
 * Thrown when an email address format is invalid.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-011 Exception Hierarchy
 * 
 * @since 1.0.0
 */
class InvalidEmailException extends GPGException
{
    /**
     * Constructor
     *
     * @param string $email
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @since 1.0.0
     */
    public function __construct(
        string $email,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct(
            "Invalid email address format: {$email}",
            $code,
            $previous
        );
    }
}
