<?php
declare(strict_types=1);

namespace ksfraser\GPG\Hook;

/**
 * Result DTO for a single target within a GPG hook operation.
 *
 * Tracks per-recipient outcome: success/failure, output path, key status, warnings.
 *
 * @since 1.0.0
 */
class GPGTargetResult
{
    /** @var GPGTarget The original target */
    private $target;

    /** @var bool Whether the operation succeeded for this target */
    private $success = false;

    /** @var string|null Path to the output file (encrypted/signed) */
    private $outputPath;

    /** @var bool Whether a GPG key was found for this target */
    private $keyFound = false;

    /** @var bool Whether password encryption was used as fallback */
    private $usedPasswordFallback = false;

    /** @var string[] Warning messages */
    private $warnings = [];

    /** @var string|null Error message if failed */
    private $error;

    /**
     * @param GPGTarget $target The target this result is for
     *
     * @since 1.0.0
     */
    public function __construct(GPGTarget $target)
    {
        $this->target = $target;
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

    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    public function setOutputPath(string $path): self
    {
        $this->outputPath = $path;
        return $this;
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
