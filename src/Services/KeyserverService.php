<?php
declare(strict_types=1);

namespace ksfraser\GPG\Services;

use ksfraser\GPG\Contracts\KeyserverInterface;
use ksfraser\GPG\Contracts\KeyManagerInterface;
use ksfraser\GPG\Contracts\GnuPGAdapterInterface;
use ksfraser\GPG\Adapter\GnuPGAdapterFactory;
use ksfraser\GPG\Entity\GPGKey;
use ksfraser\GPG\ValueObject\EmailAddress;
use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\ValueObject\KeyId;
use ksfraser\GPG\Exception\KeyserverException;

/**
 * Keyserver Service
 * 
 * Provides keyserver operations: publish, search, import.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-003 Keyserver Integration
 * 
 * @since 1.0.0
 */
class KeyserverService implements KeyserverInterface
{
    /**
     * @var string
     */
    private string $keyserverUrl;

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
     * @param string $keyserverUrl
     * @param GnuPGAdapterInterface|null $adapter If null, auto-detects best available
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        string $keyserverUrl = 'hkps://keys.openpgp.org',
        ?GnuPGAdapterInterface $adapter = null
    ) {
        $this->keyManager = $keyManager;
        $this->keyserverUrl = $keyserverUrl;
        $this->adapter = $adapter ?? GnuPGAdapterFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $keyId): bool
    {
        return $this->adapter->publishToKeyserver($keyId, $this->keyserverUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $email): array
    {
        $keys = $this->adapter->listKeys();
        $result = [];
        
        foreach ($keys as $keyData) {
            if ($keyData['email'] === $email) {
                try {
                    $key = $this->keyManager->getKeyById($keyData['key_id']);
                    $result[] = $key;
                } catch (\Exception $e) {
                    // Key not in local repository, create basic entity
                    $keyIdObj = new KeyId($keyData['key_id']);
                    $fingerprintObj = new Fingerprint($keyData['fingerprint']);
                    $emailObj = new EmailAddress($keyData['email']);
                    
                    $gpgKey = new GPGKey(
                        $keyIdObj,
                        $fingerprintObj,
                        $emailObj,
                        ''
                    );
                    
                    $result[] = $gpgKey;
                }
            }
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function import(string $keyId): GPGKey
    {
        // Import from keyserver - this would need keyserver-specific implementation
        // For now, throw an exception as this requires network access
        throw new KeyserverException('Keyserver import not yet implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyserverUrl(): string
    {
        return $this->keyserverUrl;
    }
}
