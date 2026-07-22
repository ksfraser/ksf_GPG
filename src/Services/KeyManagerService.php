<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\KeyManagerInterface;
use Ksf\GPG\Contracts\KeyRepositoryInterface;
use Ksf\GPG\Entity\GPGKey;
use Ksf\GPG\Entity\KeyPair;
use Ksf\GPG\ValueObject\EmailAddress;
use Ksf\GPG\ValueObject\Fingerprint;
use Ksf\GPG\ValueObject\KeyId;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\KeyNotFoundException;
use Ksf\GPG\Exception\EncryptionFailedException;

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
     * Constructor
     *
     * @param KeyRepositoryInterface $repository
     *
     * @since 1.0.0
     */
    public function __construct(KeyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey(string $email, string $passphrase): KeyPair
    {
        $emailObj = new EmailAddress($email);
        
        // Generate GPG key pair using command line
        $tempDir = sys_get_temp_dir();
        $keyFile = tempnam($tempDir, 'gpg_key_');
        
        $batchContent = <<<EOF
%no-protection
Key-Type: RSA
Key-Length: 4096
Subkey-Type: RSA
Subkey-Length: 4096
Name-Real: {$emailObj->getLocalPart()}
Name-Email: {$email}
Expire-Date: 0
%commit
EOF;
        
        file_put_contents($keyFile, $batchContent);
        
        $command = sprintf(
            'gpg --batch --gen-key %s 2>&1',
            escapeshellarg($keyFile)
        );
        
        exec($command, $output, $returnCode);
        
        unlink($keyFile);
        
        if ($returnCode !== 0) {
            throw new GPGException(
                "Key generation failed: " . implode("\n", $output)
            );
        }
        
        // Get the generated key info
        $command = sprintf(
            'gpg --batch --list-keys --with-colons %s 2>&1',
            escapeshellarg($email)
        );
        
        exec($command, $output, $returnCode);
        
        $keyId = null;
        $fingerprint = null;
        
        foreach ($output as $line) {
            $parts = explode(':', $line);
            if ($parts[0] === 'pub') {
                $keyId = $parts[4];
            }
            if ($parts[0] === 'fpr') {
                $fingerprint = $parts[9];
            }
        }
        
        if ($keyId === null || $fingerprint === null) {
            throw new GPGException("Failed to get generated key info");
        }
        
        // Export public key
        $publicKey = $this->exportPublicKey($keyId);
        
        // Export private key (encrypted with passphrase)
        $privateKey = $this->exportPrivateKey($keyId, $passphrase);
        
        // Create entities
        $keyIdObj = new KeyId($keyId);
        $fingerprintObj = new Fingerprint($fingerprint);
        
        $gpgKey = new GPGKey(
            $keyIdObj,
            $fingerprintObj,
            $emailObj,
            $publicKey
        );
        
        $gpgKey->setEncryptedPrivateKey($privateKey);
        
        // Save to repository
        $this->repository->save($gpgKey);
        
        return new KeyPair($gpgKey, $privateKey, $passphrase);
    }

    /**
     * {@inheritdoc}
     */
    public function importPublicKey(string $keyContent): GPGKey
    {
        // Import key to GPG keyring
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_import_');
        file_put_contents($tempFile, $keyContent);
        
        $command = sprintf(
            'gpg --batch --import %s 2>&1',
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            throw new GPGException(
                "Key import failed: " . implode("\n", $output)
            );
        }
        
        // Get key info from output
        $keyId = null;
        $fingerprint = null;
        $email = null;
        
        foreach ($output as $line) {
            if (preg_match('/key ([0-9A-F]+):', $line, $matches)) {
                $keyId = $matches[1];
            }
        }
        
        if ($keyId === null) {
            throw new GPGException("Failed to get imported key ID");
        }
        
        // Get full key info
        $command = sprintf(
            'gpg --batch --list-keys --with-colons %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $keyOutput, $returnCode);
        
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
        $publicKey = $this->exportPublicKey($keyId);
        
        // Create entities
        $keyIdObj = new KeyId($keyId);
        $fingerprintObj = new Fingerprint($fingerprint);
        $emailObj = new EmailAddress($email);
        
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
        // Import key to GPG keyring
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_import_');
        file_put_contents($tempFile, $keyContent);
        
        $command = sprintf(
            'gpg --batch --import %s 2>&1',
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            throw new GPGException(
                "Key import failed: " . implode("\n", $output)
            );
        }
        
        // Get key info from output
        $keyId = null;
        
        foreach ($output as $line) {
            if (preg_match('/key ([0-9A-F]+):', $line, $matches)) {
                $keyId = $matches[1];
            }
        }
        
        if ($keyId === null) {
            throw new GPGException("Failed to get imported key ID");
        }
        
        // Get the full key info
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
        $publicKey = $this->exportPublicKey($keyId);
        
        // Export private key (encrypted with passphrase)
        $privateKey = $this->exportPrivateKey($keyId, $passphrase);
        
        // Create entities
        $keyIdObj = new KeyId($keyId);
        $fingerprintObj = new Fingerprint($fingerprint);
        $emailObj = new EmailAddress($email);
        
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
        $command = sprintf(
            'gpg --batch --armor --export %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new KeyNotFoundException($keyId);
        }
        
        return implode("\n", $output);
    }

    /**
     * Export private key (encrypted with passphrase)
     *
     * @param string $keyId
     * @param string $passphrase
     * @return string
     * @throws KeyNotFoundException
     *
     * @since 1.0.0
     */
    private function exportPrivateKey(string $keyId, string $passphrase): string
    {
        $command = sprintf(
            'gpg --batch --armor --export-secret-keys --passphrase %s %s 2>&1',
            escapeshellarg($passphrase),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new KeyNotFoundException($keyId);
        }
        
        return implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getFingerprint(string $keyId): string
    {
        $command = sprintf(
            'gpg --batch --fingerprint %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new KeyNotFoundException($keyId);
        }
        
        foreach ($output as $line) {
            if (strpos($line, 'Key fingerprint =') !== false) {
                $fingerprint = trim(str_replace('Key fingerprint =', '', $line));
                return str_replace(' ', '', $fingerprint);
            }
        }
        
        throw new KeyNotFoundException($keyId);
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(): array
    {
        $command = 'gpg --batch --list-keys --with-colons 2>&1';
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            return [];
        }
        
        $keys = [];
        $currentKey = null;
        
        foreach ($output as $line) {
            $parts = explode(':', $line);
            
            if ($parts[0] === 'pub') {
                $keyId = $parts[4];
                $currentKey = $this->repository->findById($keyId);
            }
            
            if ($currentKey !== null && $parts[0] === 'uid') {
                $email = $parts[9];
                $keys[] = $currentKey;
                $currentKey = null;
            }
        }
        
        return $keys;
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
        $command = sprintf(
            'gpg --batch --yes --passphrase %s --delete-secret-keys %s 2>&1',
            escapeshellarg($passphrase),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new GPGException(
                "Failed to delete secret key: " . implode("\n", $output)
            );
        }
        
        $command = sprintf(
            'gpg --batch --yes --delete-keys %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new GPGException(
                "Failed to delete public key: " . implode("\n", $output)
            );
        }
        
        return $this->repository->delete($keyId);
    }
}
