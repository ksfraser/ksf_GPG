<?php
declare(strict_types=1);

namespace ksfraser\GPG\Services;

use ksfraser\GPG\Contracts\KeyManagerInterface;
use ksfraser\GPG\Contracts\KeyRepositoryInterface;
use ksfraser\GPG\Contracts\GnuPGAdapterInterface;
use ksfraser\GPG\Adapter\GnuPGAdapterFactory;
use ksfraser\GPG\Entity\GPGKey;
use ksfraser\GPG\Entity\KeyPair;
use ksfraser\GPG\ValueObject\EmailAddress;
use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\ValueObject\KeyId;
use ksfraser\GPG\Exception\GPGException;
use ksfraser\GPG\Exception\KeyNotFoundException;

/**
 * Key Manager Service
 * 
 * Manages GPG key lifecycle: generate, import, export, delete.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-002 Key Management
 * 
 * @since 1.0.0
 */
class KeyManagerService implements KeyManagerInterface
{
    /**
     * @var KeyRepositoryInterface
     */
    private KeyRepositoryInterface $repository;

    /**
     * @var GnuPGAdapterInterface
     */
    private GnuPGAdapterInterface $adapter;

    /**
     * Constructor
     *
     * @param KeyRepositoryInterface $repository
     * @param GnuPGAdapterInterface|null $adapter If null, auto-detects best available
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyRepositoryInterface $repository,
        ?GnuPGAdapterInterface $adapter = null
    ) {
        $this->repository = $repository;
        $this->adapter = $adapter ?? GnuPGAdapterFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey(string $email, string $passphrase): KeyPair
    {
        $emailObj = new EmailAddress($email);
        
        $result = $this->adapter->generateKey($email, $passphrase);
        
        // Create entities
        $keyIdObj = new KeyId($result['key_id']);
        $fingerprintObj = new Fingerprint($result['fingerprint']);
        
        $gpgKey = new GPGKey(
            $keyIdObj,
            $fingerprintObj,
            $emailObj,
            $result['public_key']
        );
        
        $gpgKey->setEncryptedPrivateKey($result['private_key']);
        
        // Save to repository
        $this->repository->save($gpgKey);
        
        return new KeyPair($gpgKey, $result['private_key'], $passphrase);
    }

    /**
     * {@inheritdoc}
     */
    public function importPublicKey(string $keyContent): GPGKey
    {
        $result = $this->adapter->importKey($keyContent);
        
        // Export public key
        $publicKey = $this->adapter->exportPublicKey($result['key_id']);
        
        // Create entities
        $keyIdObj = new KeyId($result['key_id']);
        $fingerprintObj = new Fingerprint($result['fingerprint']);
        $emailObj = new EmailAddress($result['email']);
        
        $gpgKey = new GPGKey(
            $keyIdObj,
            $fingerprintObj,
            $emailObj,
            $publicKey
        );
        
        // Save to repository
        $this->repository->save($gpgKey);
        
        return $gpgKey;
    }

    /**
     * {@inheritdoc}
     */
    public function importPrivateKey(string $keyContent, string $passphrase): GPGKey
    {
        $result = $this->adapter->importKey($keyContent);
        
        // Export keys
        $publicKey = $this->adapter->exportPublicKey($result['key_id']);
        
        // For private key, we need to use the adapter
        // The adapter's importKey should have imported it
        $privateKey = $keyContent; // Original content is the private key
        
        // Create entities
        $keyIdObj = new KeyId($result['key_id']);
        $fingerprintObj = new Fingerprint($result['fingerprint']);
        $emailObj = new EmailAddress($result['email']);
        
        $gpgKey = new GPGKey(
            $keyIdObj,
            $fingerprintObj,
            $emailObj,
            $publicKey
        );
        
        $gpgKey->setEncryptedPrivateKey($privateKey);
        
        // Save to repository
        $this->repository->save($gpgKey);
        
        return $gpgKey;
    }

    /**
     * {@inheritdoc}
     */
    public function exportPublicKey(string $keyId): string
    {
        return $this->adapter->exportPublicKey($keyId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFingerprint(string $keyId): string
    {
        $keys = $this->adapter->listKeys();
        
        foreach ($keys as $key) {
            if ($key['key_id'] === $keyId) {
                return $key['fingerprint'];
            }
        }
        
        throw new KeyNotFoundException($keyId);
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(): array
    {
        $keys = $this->adapter->listKeys();
        $result = [];
        
        foreach ($keys as $keyData) {
            try {
                $keyIdObj = new KeyId($keyData['key_id']);
                $fingerprintObj = new Fingerprint($keyData['fingerprint']);
                $emailObj = new EmailAddress($keyData['email']);
                
                // Try to get from repository first
                $gpgKey = $this->repository->findById($keyData['key_id']);
                
                if ($gpgKey === null) {
                    // Create a basic key entity
                    $gpgKey = new GPGKey(
                        $keyIdObj,
                        $fingerprintObj,
                        $emailObj,
                        '' // Public key not loaded from keyring
                    );
                }
                
                $result[] = $gpgKey;
            } catch (\Exception $e) {
                // Skip invalid keys
                continue;
            }
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyById(string $keyId): GPGKey
    {
        $key = $this->repository->findById($keyId);
        
        if ($key === null) {
            throw new KeyNotFoundException($keyId);
        }
        
        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyByEmail(string $email): ?GPGKey
    {
        $keys = $this->repository->findByEmail($email);
        
        return !empty($keys) ? $keys[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteKey(string $keyId, string $passphrase): bool
    {
        $this->adapter->deleteKey($keyId, $passphrase);
        
        return $this->repository->delete($keyId);
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
}
