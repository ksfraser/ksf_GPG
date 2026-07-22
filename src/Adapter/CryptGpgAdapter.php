<?php
declare(strict_types=1);

namespace Ksf\GPG\Adapter;

use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\EncryptionFailedException;
use Ksf\GPG\Exception\SigningFailedException;
use Ksf\GPG\Exception\KeyNotFoundException;

/**
 * Crypt_GPG Adapter
 * 
 * Uses pear/crypt_gpg (OOP wrapper around gpg binary).
 * 
 * @since 1.0.0
 */
class CryptGpgAdapter implements GnuPGAdapterInterface
{
    /**
     * @var \Crypt_GPG|null
     */
    private ?\Crypt_GPG $gpg = null;

    /**
     * {@inheritdoc}
     */
    public static function isAvailable(): bool
    {
        return class_exists(\Crypt_GPG::class);
    }

    /**
     * Get or create Crypt_GPG instance.
     *
     * @return \Crypt_GPG
     *
     * @since 1.0.0
     */
    private function getGpg(): \Crypt_GPG
    {
        if ($this->gpg === null) {
            $this->gpg = new \Crypt_GPG();
        }
        return $this->gpg;
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey(
        string $email,
        string $passphrase,
        string $keyType = 'RSA',
        int $keyLength = 4096
    ): array {
        try {
            $gpg = $this->getGpg();
            
            $params = [
                'email' => $email,
                'passphrase' => $passphrase,
                'key-type' => $keyType,
                'key-length' => $keyLength,
                'subkey-type' => $keyType,
                'subkey-length' => $keyLength,
                'name-real' => $email,
                'name-email' => $email,
                'expire-date' => '0',
            ];
            
            $key = $gpg->generateKey($params);
            
            if ($key === null) {
                throw new GPGException('Key generation failed');
            }
            
            $fingerprint = $key->getPrimaryKey()->getFingerprint();
            $keyId = $key->getPrimaryKey()->getId();
            
            // Export keys
            $gpg->addEncryptKey($fingerprint);
            $gpg->addSignKey($fingerprint, $passphrase);
            
            $publicKey = $gpg->exportPublicKey($fingerprint);
            $privateKey = $gpg->exportPrivateKey($fingerprint, $passphrase);
            
            return [
                'key_id' => $keyId,
                'fingerprint' => $fingerprint,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
            ];
        } catch (\Exception $e) {
            throw new GPGException('Key generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function signFile(string $filePath, string $fingerprint): string
    {
        if (!file_exists($filePath)) {
            throw new SigningFailedException("File not found: {$filePath}");
        }
        
        try {
            $gpg = $this->getGpg();
            $gpg->addSignKey($fingerprint);
            
            $signedPath = $filePath . '.sig';
            $signed = $gpg->signFile($filePath, \Crypt_GPG::SIGNATURE_MODE_DETACHED);
            
            file_put_contents($signedPath, $signed);
            
            return $signedPath;
        } catch (\Exception $e) {
            throw new SigningFailedException('Signing failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function encryptForRecipient(string $filePath, string $fingerprint): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
        try {
            $gpg = $this->getGpg();
            $gpg->addEncryptKey($fingerprint);
            
            $encryptedPath = $filePath . '.gpg';
            $encrypted = $gpg->encryptFile($filePath);
            
            file_put_contents($encryptedPath, $encrypted);
            
            return $encryptedPath;
        } catch (\Exception $e) {
            throw new EncryptionFailedException('Encryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPassword(string $filePath, string $password): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
        try {
            $encryptedPath = $filePath . '.gpg';
            
            $command = sprintf(
                'gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase %s --output %s %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($encryptedPath),
                escapeshellarg($filePath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new EncryptionFailedException('Password encryption failed: ' . implode("\n", $output));
            }
            
            return $encryptedPath;
        } catch (EncryptionFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EncryptionFailedException('Password encryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPassword(string $filePath, string $password): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
        try {
            $decryptedPath = preg_replace('/\.gpg$/', '', $filePath);
            
            $command = sprintf(
                'gpg --batch --yes --decrypt --passphrase %s --output %s %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($decryptedPath),
                escapeshellarg($filePath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new EncryptionFailedException('Password decryption failed: ' . implode("\n", $output));
            }
            
            return $decryptedPath;
        } catch (EncryptionFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EncryptionFailedException('Password decryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function importKey(string $keyContent): array
    {
        try {
            $gpg = $this->getGpg();
            $result = $gpg->importKey($keyContent);
            
            if ($result === null) {
                throw new GPGException('Key import failed');
            }
            
            $fingerprint = $result->getFingerprint();
            $keyId = $result->getId();
            $email = $result->getUserId();
            
            return [
                'key_id' => $keyId,
                'fingerprint' => $fingerprint,
                'email' => $email,
            ];
        } catch (\Exception $e) {
            throw new GPGException('Key import failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportPublicKey(string $keyId): string
    {
        try {
            $gpg = $this->getGpg();
            return $gpg->exportPublicKey($keyId);
        } catch (\Exception $e) {
            throw new KeyNotFoundException($keyId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteKey(string $keyId, string $passphrase): bool
    {
        try {
            $gpg = $this->getGpg();
            $gpg->deleteKey($keyId);
            return true;
        } catch (\Exception $e) {
            throw new GPGException('Key deletion failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function publishToKeyserver(string $keyId, string $keyserverUrl): bool
    {
        try {
            $gpg = $this->getGpg();
            $gpg->keyserver = $keyserverUrl;
            $gpg->sendKey($keyId);
            return true;
        } catch (\Exception $e) {
            throw new GPGException('Keyserver publish failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function encryptForRecipients(string $filePath, array $fingerprints): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }

        if (empty($fingerprints)) {
            throw new EncryptionFailedException('No recipient fingerprints provided');
        }

        try {
            $gpg = $this->getGpg();

            foreach ($fingerprints as $fingerprint) {
                $gpg->addEncryptKey($fingerprint);
            }

            $encryptedPath = $filePath . '.gpg';
            $encrypted = $gpg->encryptFile($filePath);

            file_put_contents($encryptedPath, $encrypted);

            return $encryptedPath;
        } catch (\Exception $e) {
            throw new EncryptionFailedException('Multi-recipient encryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(): array
    {
        try {
            $gpg = $this->getGpg();
            $keys = $gpg->listKeys();
            
            $result = [];
            foreach ($keys as $key) {
                $result[] = [
                    'key_id' => $key->getPrimaryKey()->getId(),
                    'fingerprint' => $key->getPrimaryKey()->getFingerprint(),
                    'email' => $key->getUserId(),
                    'created' => $key->getCreationDate()->format('Y-m-d H:i:s'),
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
}
