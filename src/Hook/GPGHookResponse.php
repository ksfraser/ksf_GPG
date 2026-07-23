<?php
declare(strict_types=1);

namespace ksfraser\GPG\Hook;

/**
 * Response DTO for GPG hook operations.
 *
 * Contains overall operation status and per-target results.
 * Calling modules inspect this to determine what happened and
 * which files to attach/send.
 *
 * @since 1.0.0
 */
class GPGHookResponse
{
    /** @var bool Overall success */
    private $success = false;

    /** @var GPGTargetResult[] Per-target results */
    private $results = [];

    /** @var string[] Global warning messages */
    private $warnings = [];

    /**
     * Check if the operation was successful.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return self
     *
     * @since 1.0.0
     */
    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param GPGTargetResult $result
     * @return self
     *
     * @since 1.0.0
     */
    public function addResult(GPGTargetResult $result): self
    {
        $this->results[] = $result;
        return $this;
    }

    /**
     * @return GPGTargetResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get the number of successful targets.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function getSuccessCount(): int
    {
        $count = 0;
        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getFailureCount(): int
    {
        return count($this->results) - $this->getSuccessCount();
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

    /**
     * Get all encrypted file paths from successful results.
     *
     * @return string[] Paths to encrypted files
     *
     * @since 1.1.0
     */
    public function getEncryptedPaths(): array
    {
        $paths = [];
        foreach ($this->results as $result) {
            if ($result->isSuccess() && $result->getEncryptedPath() !== null) {
                $paths[] = $result->getEncryptedPath();
            }
        }
        return array_unique($paths);
    }

    /**
     * Get all signed file paths from successful results.
     *
     * @return string[] Paths to signature files (.sig)
     *
     * @since 1.1.0
     */
    public function getSignedPaths(): array
    {
        $paths = [];
        foreach ($this->results as $result) {
            if ($result->isSuccess() && $result->getSignedPath() !== null) {
                $paths[] = $result->getSignedPath();
            }
        }
        return array_unique($paths);
    }

    /**
     * Get all original file paths.
     *
     * @return string[]
     *
     * @since 1.1.0
     */
    public function getOriginalPaths(): array
    {
        $paths = [];
        foreach ($this->results as $result) {
            $paths[] = $result->getOriginalPath();
        }
        return array_unique($paths);
    }

    /**
     * Get the "best" output path (first successful result).
     *
     * Priority: signed > encrypted > original.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getFirstOutputPath(): ?string
    {
        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                return $result->getOutputPath();
            }
        }
        return null;
    }
}
