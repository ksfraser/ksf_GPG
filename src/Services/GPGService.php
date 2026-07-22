<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\GPGServiceInterface;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Contracts\ContactResolverInterface;
use Ksf\GPG\Contracts\SigningKeyResolverInterface;
use Ksf\GPG\Adapter\GnuPGAdapterFactory;
use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\Hook\GPGHookRequest;
use Ksf\GPG\Hook\GPGHookResponse;
use Ksf\GPG\Hook\GPGTarget;
use Ksf\GPG\Hook\GPGTargetResult;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\KeyNotFoundException;
use Ksf\GPG\Exception\SigningFailedException;
use Ksf\GPG\Exception\EncryptionFailedException;

/**
 * GPG Service
 * 
 * Main entry point for GPG operations.
 * Provides signing, encryption, and key management.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-001 GPG Service Implementation
 * 
 * @since 1.0.0
 */
class GPGService implements GPGServiceInterface
{
    /**
     * @var KeyManagerInterface
     */
    private KeyManagerInterface $keyManager;

    /**
     * @var GnuPGAdapterInterface
     */
    private GnuPGAdapterInterface $adapter;

    /**
     * Constructor
     *
     * @param KeyManagerInterface $keyManager
     * @param GnuPGAdapterInterface|null $adapter If null, auto-detects best available
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        ?GnuPGAdapterInterface $adapter = null
    ) {
        $this->keyManager = $keyManager;
        $this->adapter = $adapter ?? GnuPGAdapterFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function signFile(string $filePath, string $email): EncryptedFile
    {
        if (!file_exists($filePath)) {
            throw new SigningFailedException("File not found: {$filePath}");
        }

        $key = $this->keyManager->getKeyByEmail($email);
        
        if ($key === null) {
            throw new KeyNotFoundException($email);
        }

        $signedPath = $this->adapter->signFile(
            $filePath,
            $key->getFingerprint()->getValue()
        );

        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setSignedPath($signedPath);
        $encryptedFile->setRecipientEmail($email);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptForContact(string $filePath, string $email): EncryptedFile
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }

        $key = $this->keyManager->getKeyByEmail($email);
        
        if ($key === null) {
            throw new KeyNotFoundException($email);
        }

        $encryptedPath = $this->adapter->encryptForRecipient(
            $filePath,
            $key->getFingerprint()->getValue()
        );

        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setEncryptedPath($encryptedPath);
        $encryptedFile->setRecipientEmail($email);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function signAndEncrypt(string $filePath, string $email): EncryptedFile
    {
        // First encrypt, then sign the encrypted file
        $encryptedFile = $this->encryptForContact($filePath, $email);
        $encryptedPath = $encryptedFile->getEncryptedPath();
        
        // Sign the encrypted file
        $signedFile = $this->signFile($encryptedPath, $email);
        
        // Update the original encrypted file with signed path
        $encryptedFile->setSignedPath($signedFile->getSignedPath());
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPassword(string $filePath, string $password): EncryptedFile
    {
        $encryptedPath = $this->adapter->encryptWithPassword($filePath, $password);
        
        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setEncryptedPath($encryptedPath);
        $encryptedFile->setPasswordProtected(true);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPassword(string $filePath, string $password): string
    {
        return $this->adapter->decryptWithPassword($filePath, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey(string $email, string $passphrase): KeyPair
    {
        return $this->keyManager->generateKey($email, $passphrase);
    }

    /**
     * {@inheritdoc}
     */
    public function hasKeyForEmail(string $email): bool
    {
        return $this->keyManager->getKeyByEmail($email) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyByEmail(string $email): ?GPGKey
    {
        return $this->keyManager->getKeyByEmail($email);
    }

    /**
     * {@inheritdoc}
     */
    public function getContactEmail(string $contactType, int $contactId): ?string
    {
        // This would be implemented by the platform adapter (FA)
        // For now, return null
        return null;
    }

    /**
     * Get the underlying GnuPG adapter.
     *
     * @return GnuPGAdapterInterface
     *
     * @since 1.0.0
     */
    public function getAdapter(): GnuPGAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Process a hook request with full DTO support.
     *
     * Orchestrates contact resolution, key lookup, signing, encryption,
     * and per-target result collection. Supports partial failure:
     * encrypts to found keys + warnings for missing keys.
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
    ): GPGHookResponse {
        $response = new GPGHookResponse();
        $filePath = $request->getFilePath();

        if (!file_exists($filePath)) {
            $response->setSuccess(false);
            $response->addWarning("File not found: {$filePath}");
            return $response;
        }

        $operation = $request->getOperation();
        $fingerprints = [];
        $allResults = [];

        // Resolve each target
        foreach ($request->getTargets() as $target) {
            $targetResult = new GPGTargetResult($target);
            $allResults[] = $targetResult;

            // Resolve email if not provided
            if ($target->getEmail() === null && $contactResolver !== null) {
                $email = $contactResolver->resolveEmail(
                    $target->getContactType(),
                    $target->getContactId()
                );
                if ($email !== null) {
                    $target->setEmail($email);
                }
            }

            // Look up key
            if ($target->getFingerprint() !== null) {
                $targetResult->setKeyFound(true);
                $fingerprints[] = $target->getFingerprint();
            } elseif ($target->getEmail() !== null) {
                $key = $this->keyManager->getKeyByEmail($target->getEmail());
                if ($key !== null) {
                    $targetResult->setKeyFound(true);
                    $target->setFingerprint($key->getFingerprint()->getValue());
                    $fingerprints[] = $key->getFingerprint()->getValue();
                } else {
                    $targetResult->setKeyFound(false);
                    $targetResult->addWarning(
                        "No GPG key found for {$target->getEmail()}"
                    );
                }
            } else {
                $targetResult->setKeyFound(false);
                $targetResult->addWarning(
                    "Cannot resolve email for {$target->getContactType()}:{$target->getContactId()}"
                );
            }
        }

        // Execute operation based on type
        switch ($operation) {
            case GPGHookRequest::OPERATION_SIGN:
                $this->processSignOperation($request, $fingerprints, $allResults, $response, $signingKeyResolver);
                break;

            case GPGHookRequest::OPERATION_ENCRYPT:
                $this->processEncryptOperation($request, $fingerprints, $allResults, $response);
                break;

            case GPGHookRequest::OPERATION_SIGN_ENCRYPT:
                $this->processSignEncryptOperation($request, $fingerprints, $allResults, $response, $signingKeyResolver);
                break;

            case GPGHookRequest::OPERATION_PASSWORD_ENCRYPT:
                $this->processPasswordEncryptOperation($request, $allResults, $response);
                break;

            default:
                $response->setSuccess(false);
                $response->addWarning("Unknown operation: {$operation}");
                break;
        }

        // Add all target results to response
        foreach ($allResults as $result) {
            $response->addResult($result);
        }

        // Set overall success = all targets succeeded
        $allSucceeded = true;
        foreach ($allResults as $result) {
            if (!$result->isSuccess()) {
                $allSucceeded = false;
                break;
            }
        }
        $response->setSuccess($allSucceeded);

        return $response;
    }

    /**
     * Process sign operation for all targets.
     *
     * @param GPGHookRequest $request
     * @param string[] $fingerprints Resolved fingerprints
     * @param GPGTargetResult[] $allResults
     * @param GPGHookResponse $response
     * @param SigningKeyResolverInterface|null $signingKeyResolver
     *
     * @since 1.1.0
     */
    private function processSignOperation(
        GPGHookRequest $request,
        array $fingerprints,
        array $allResults,
        GPGHookResponse $response,
        ?SigningKeyResolverInterface $signingKeyResolver = null
    ): void {
        // For signing, we need the sender's key
        $signingFingerprint = null;

        if ($signingKeyResolver !== null && $request->getSenderContactType() !== null) {
            $signingFingerprint = $signingKeyResolver->resolveSigningKey(
                $request->getSenderContactType(),
                $request->getSenderContactId()
            );
        }

        // Fallback: use first resolved fingerprint from targets
        if ($signingFingerprint === null && !empty($fingerprints)) {
            $signingFingerprint = $fingerprints[0];
        }

        if ($signingFingerprint === null) {
            $response->setSuccess(false);
            $response->addWarning('No signing key available');
            foreach ($allResults as $result) {
                $result->setError('No signing key available');
            }
            return;
        }

        try {
            $signedPath = $this->adapter->signFile(
                $request->getFilePath(),
                $signingFingerprint
            );

            foreach ($allResults as $result) {
                $result->setSuccess(true);
                $result->setOutputPath($signedPath);
            }
        } catch (\Exception $e) {
            foreach ($allResults as $result) {
                $result->setError('Signing failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Process encrypt operation for all targets.
     *
     * Uses multi-recipient encryption: single file encrypted to ALL found keys.
     * Targets without keys get warnings (partial failure).
     *
     * @param GPGHookRequest $request
     * @param string[] $fingerprints Resolved fingerprints
     * @param GPGTargetResult[] $allResults
     * @param GPGHookResponse $response
     *
     * @since 1.1.0
     */
    private function processEncryptOperation(
        GPGHookRequest $request,
        array $fingerprints,
        array $allResults,
        GPGHookResponse $response
    ): void {
        if (empty($fingerprints)) {
            // No keys found for any target — fallback to password if available
            $password = $request->getPassword();
            if ($password !== null) {
                $this->processPasswordEncryptOperation($request, $allResults, $response);
                return;
            }

            $response->setSuccess(false);
            $response->addWarning('No GPG keys found for any recipient');
            foreach ($allResults as $result) {
                if (!$result->isKeyFound()) {
                    $result->setError('No GPG key found');
                }
            }
            return;
        }

        try {
            $encryptedPath = $this->adapter->encryptForRecipients(
                $request->getFilePath(),
                $fingerprints
            );

            foreach ($allResults as $result) {
                if ($result->isKeyFound()) {
                    $result->setSuccess(true);
                    $result->setOutputPath($encryptedPath);
                } else {
                    $result->addWarning('File encrypted but not to this recipient (no key)');
                }
            }
        } catch (\Exception $e) {
            foreach ($allResults as $result) {
                $result->setError('Encryption failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Process sign + encrypt operation for all targets.
     *
     * @param GPGHookRequest $request
     * @param string[] $fingerprints Resolved fingerprints
     * @param GPGTargetResult[] $allResults
     * @param GPGHookResponse $response
     * @param SigningKeyResolverInterface|null $signingKeyResolver
     *
     * @since 1.1.0
     */
    private function processSignEncryptOperation(
        GPGHookRequest $request,
        array $fingerprints,
        array $allResults,
        GPGHookResponse $response,
        ?SigningKeyResolverInterface $signingKeyResolver = null
    ): void {
        // First encrypt
        $this->processEncryptOperation($request, $fingerprints, $allResults, $response);

        if (!$response->isSuccess()) {
            return;
        }

        // Then sign the encrypted file
        $encryptedPath = null;
        foreach ($allResults as $result) {
            if ($result->isSuccess() && $result->getOutputPath() !== null) {
                $encryptedPath = $result->getOutputPath();
                break;
            }
        }

        if ($encryptedPath === null) {
            return;
        }

        $signingFingerprint = null;
        if ($signingKeyResolver !== null && $request->getSenderContactType() !== null) {
            $signingFingerprint = $signingKeyResolver->resolveSigningKey(
                $request->getSenderContactType(),
                $request->getSenderContactId()
            );
        }

        if ($signingFingerprint === null && !empty($fingerprints)) {
            $signingFingerprint = $fingerprints[0];
        }

        if ($signingFingerprint === null) {
            $response->addWarning('Encryption succeeded but signing skipped (no signing key)');
            return;
        }

        try {
            $signedPath = $this->adapter->signFile($encryptedPath, $signingFingerprint);
            foreach ($allResults as $result) {
                if ($result->isSuccess()) {
                    $result->setOutputPath($signedPath);
                }
            }
        } catch (\Exception $e) {
            $response->addWarning('Encryption succeeded but signing failed: ' . $e->getMessage());
        }
    }

    /**
     * Process password-based encryption for all targets.
     *
     * @param GPGHookRequest $request
     * @param GPGTargetResult[] $allResults
     * @param GPGHookResponse $response
     *
     * @since 1.1.0
     */
    private function processPasswordEncryptOperation(
        GPGHookRequest $request,
        array $allResults,
        GPGHookResponse $response
    ): void {
        $password = $request->getPassword();

        if ($password === null) {
            $response->setSuccess(false);
            $response->addWarning('No password provided for password encryption');
            foreach ($allResults as $result) {
                $result->setError('No password provided');
            }
            return;
        }

        try {
            $encryptedPath = $this->adapter->encryptWithPassword(
                $request->getFilePath(),
                $password
            );

            foreach ($allResults as $result) {
                $result->setSuccess(true);
                $result->setOutputPath($encryptedPath);
                $result->setUsedPasswordFallback(true);
            }
        } catch (\Exception $e) {
            foreach ($allResults as $result) {
                $result->setError('Password encryption failed: ' . $e->getMessage());
            }
        }
    }
}
