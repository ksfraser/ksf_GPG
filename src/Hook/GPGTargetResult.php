<?php
declare(strict_types=1);

namespace ksfraser\GPG\Hook;

/**
 * Result DTO for a single target within a GPG hook operation.
 *
 * Carries all file paths so the calling module can decide what to send:
 *   - originalPath: always set (the input file)
 *   - encryptedPath: set if encryption was performed
 *   - signedPath: set if signing was performed
 *
 * @since 1.1.0
 */
class GPGTargetResult
{
    /** @var GPGTarget The original target */
    private $target;

    /** @var bool Whether the operation succeeded for this target */
    private $success = false;

    /** @var string Path to the original input file */
    private $originalPath;

    /** @var string|null Path to the encrypted file */
    private $encryptedPath;

    /** @var string|null Path to the signature file (.sig) */
    private $signedPath;

    /** @var bool Whether a GPG key was found for this target */
    private $keyFound = false;

    /** @var bool Whether password encryption was used as fallback */
    private $usedPasswordFallback = false;

    /** @var string[] Warning messages */
    private $warnings = [];

    /** @var string|null Error message if failed */
    private $error;

    /**
     * @param GPGTarget $target      The target this result is for
     * @param string    $originalPath Path to the original input file
     *
     * @since 1.1.0
     */
    public function __construct(GPGTarget $target, string $originalPath = '')
    {
        $this->target = $target;
        $this->originalPath = $originalPath;
    }

    public function getTarget(): GPGTarget
    {
        return $this->target;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    /**
     * Get the original input file path (always available).
     *
     * @return string
     *
     * @since 1.1.0
     */
    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function setOriginalPath(string $path): self
    {
        $this->originalPath = $path;
        return $this;
    }

    /**
     * Get the encrypted file path (null if not encrypted).
     *
     * @return string|null
     *
     * @since 1.1.0
     */
    public function getEncryptedPath(): ?string
    {
        return $this->encryptedPath;
    }

    public function setEncryptedPath(string $path): self
    {
        $this->encryptedPath = $path;
        return $this;
    }

    /**
     * Get the signature file path (null if not signed).
     *
     * @return string|null
     *
     * @since 1.1.0
     */
    public function getSignedPath(): ?string
    {
        return $this->signedPath;
    }

    public function setSignedPath(string $path): self
    {
        $this->signedPath = $path;
        return $this;
    }

    /**
     * Get the "best" output path for the calling module.
     *
     * Priority: signed > encrypted > original.
     * This is the file the calling module should typically attach/send.
     *
     * @return string
     *
     * @since 1.1.0
     */
    public function getOutputPath(): string
    {
        if ($this->signedPath !== null) {
            return $this->signedPath;
        }
        if ($this->encryptedPath !== null) {
            return $this->encryptedPath;
        }
        return $this->originalPath;
    }

    public function isKeyFound(): bool
    {
        return $this->keyFound;
    }

    public function setKeyFound(bool $found): self
    {
        $this->keyFound = $found;
        return $this;
    }

    public function isUsedPasswordFallback(): bool
    {
        return $this->usedPasswordFallback;
    }

    public function setUsedPasswordFallback(bool $used): self
    {
        $this->usedPasswordFallback = $used;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(string $error): self
    {
        $this->error = $error;
        $this->success = false;
        return $this;
    }
}
