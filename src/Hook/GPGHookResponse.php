<?php
declare(strict_types=1);

namespace Ksf\GPG\Hook;

/**
 * Response DTO for GPG hook operations.
 *
 * Contains overall operation status and per-target results.
 * Calling modules inspect this to determine what happened.
 *
 * @since 1.0.0
 */
class GPGHookResponse
{
    /** @var bool Overall success (true only if ALL targets succeeded) */
    private $success = false;

    /** @var GPGTargetResult[] Per-target results */
    private $results = [];

    /** @var string[] Global warning messages */
    private $warnings = [];

    /**
     * Check if the operation was fully successful (all targets).
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
     * Set overall success status.
     *
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
     * Add a target result.
     *
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
     * Get the number of failed targets.
     *
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
     * Get the first output path from successful results.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getFirstOutputPath(): ?string
    {
        foreach ($this->results as $result) {
            if ($result->isSuccess() && $result->getOutputPath() !== null) {
                return $result->getOutputPath();
            }
        }
        return null;
    }
}
