<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\KeyserverInterface;
use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Exception\KeyserverException;

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
     * Constructor
     *
     * @param KeyManagerInterface $keyManager
     * @param string $keyserverUrl
     *
     * @since 1.0.0
     */
    public function __construct(
        KeyManagerInterface $keyManager,
        string $keyserverUrl = 'hkps://keys.openpgp.org'
    ) {
        $this->keyManager = $keyManager;
        $this->keyserverUrl = $keyserverUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $keyId): bool
    {
        $command = sprintf(
            'gpg --keyserver %s --send-keys %s 2>&1',
            escapeshellarg($this->keyserverUrl),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new KeyserverException(
                "Failed to publish key to keyserver: " . implode("\n", $output)
            );
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $email): array
    {
        $command = sprintf(
            'gpg --keyserver %s --search-keys %s 2>&1',
            escapeshellarg($this->keyserverUrl),
            escapeshellarg($email)
        );
        
        exec($command, $output, $returnCode);
        
        // Keyserver returns 0 even if no keys found
        $keys = [];
        
        foreach ($output as $line) {
            // Parse key info from output
            if (preg_match('/pub\s+([0-9A-F]+)/', $line, $matches)) {
                $keyId = $matches[1];
                
                // Try to get key from local keyring
                try {
                    $key = $this->keyManager->getKeyById($keyId);
                    $keys[] = $key;
                } catch (\Exception $e) {
                    // Key not in local keyring, skip
                }
            }
        }
        
        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function import(string $keyId): GPGKey
    {
        $command = sprintf(
            'gpg --keyserver %s --recv-keys %s 2>&1',
            escapeshellarg($this->keyserverUrl),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new KeyserverException(
                "Failed to import key from keyserver: " . implode("\n", $output)
            );
        }
        
        // Get the imported key info
        $command = sprintf(
            'gpg --batch --list-keys --with-colons %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $keyOutput, $returnCode);
        
        $fingerprint = null;
        $email = null;
        
        foreach ($keyOutput as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === 'uid') {
                $email = $parts[9];
            }
            if ($parts[0] === 'fpr') {
                $fingerprint = $parts[9];
            }
        }
        
        // Export public key
        $publicKey = $this->keyManager->exportPublicKey($keyId);
        
        // Create entities
        $keyIdObj = new \Ksf\GPG\ValueObject\KeyId($keyId);
        $fingerprintObj = new \Ksf\GPG\ValueObject\Fingerprint($fingerprint);
        $emailObj = new \Ksf\GPG\ValueObject\EmailAddress($email);
        
        $gpgKey = new GPGKey(
            $keyIdObj,
            $fingerprintObj,
            $emailObj,
            $publicKey
        );
        
        return $gpgKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyserverUrl(): string
    {
        return $this->keyserverUrl;
    }
}
