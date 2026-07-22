<?php
declare(strict_types=1);

namespace Ksf\GPG\Adapter;

use Ksf\GPG\Contracts\GnuPGAdapterInterface;
use Ksf\GPG\Exception\GPGException;
use Ksf\GPG\Exception\EncryptionFailedException;
use Ksf\GPG\Exception\SigningFailedException;
use Ksf\GPG\Exception\KeyNotFoundException;

/**
 * CLI GPG Adapter
 * 
 * Fallback adapter using exec() to call gpg binary directly.
 * Used when pear/crypt_gpg is not available.
 * 
 * @since 1.0.0
 */
class CliGpgAdapter implements GnuPGAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function isAvailable(): bool
    {
        exec('which gpg 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
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
        $tempDir = sys_get_temp_dir();
        $keyFile = tempnam($tempDir, 'gpg_key_');
        
        $batchContent = <<<EOF
%no-protection
Key-Type: {$keyType}
Key-Length: {$keyLength}
Subkey-Type: {$keyType}
Subkey-Length: {$keyLength}
Name-Real: {$email}
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
            throw new GPGException('Key generation failed: ' . implode("\n", $output));
        }
        
        // Get key info
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
            throw new GPGException('Failed to get generated key info');
        }
        
        // Export keys
        $publicKey = $this->exportPublicKey($keyId);
        $privateKey = $this->exportPrivateKey($keyId, $passphrase);
        
        return [
            'key_id' => $keyId,
            'fingerprint' => $fingerprint,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function signFile(string $filePath, string $fingerprint): string
    {
        if (!file_exists($filePath)) {
            throw new SigningFailedException("File not found: {$filePath}");
        }
        
        $signedPath = $filePath . '.sig';
        
        $command = sprintf(
            'gpg --batch --yes --armor --detach-sign --local-user %s --output %s %s 2>&1',
            escapeshellarg($fingerprint),
            escapeshellarg($signedPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new SigningFailedException('GPG signing failed: ' . implode("\n", $output));
        }
        
        return $signedPath;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptForRecipient(string $filePath, string $fingerprint): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
        $encryptedPath = $filePath . '.gpg';
        
        $command = sprintf(
            'gpg --batch --yes --encrypt --recipient %s --output %s %s 2>&1',
            escapeshellarg($fingerprint),
            escapeshellarg($encryptedPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new EncryptionFailedException('GPG encryption failed: ' . implode("\n", $output));
        }
        
        return $encryptedPath;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptWithPassword(string $filePath, string $password): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
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
    }

    /**
     * {@inheritdoc}
     */
    public function decryptWithPassword(string $filePath, string $password): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }
        
        $outputPath = preg_replace('/\.gpg$/', '', $filePath);
        
        $command = sprintf(
            'gpg --batch --yes --decrypt --passphrase %s --output %s %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($outputPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new EncryptionFailedException('Password decryption failed: ' . implode("\n", $output));
        }
        
        return $outputPath;
    }

    /**
     * {@inheritdoc}
     */
    public function importKey(string $keyContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gpg_import_');
        file_put_contents($tempFile, $keyContent);
        
        $command = sprintf(
            'gpg --batch --import %s 2>&1',
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            throw new GPGException('Key import failed: ' . implode("\n", $output));
        }
        
        $keyId = null;
        foreach ($output as $line) {
            if (preg_match('/key ([0-9A-F]+):/', $line, $matches)) {
                $keyId = $matches[1];
            }
        }
        
        if ($keyId === null) {
            throw new GPGException('Failed to get imported key ID');
        }
        
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
        
        return [
            'key_id' => $keyId,
            'fingerprint' => $fingerprint,
            'email' => $email,
        ];
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
    public function deleteKey(string $keyId, string $passphrase): bool
    {
        $command = sprintf(
            'gpg --batch --yes --passphrase %s --delete-secret-keys %s 2>&1',
            escapeshellarg($passphrase),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new GPGException('Failed to delete secret key: ' . implode("\n", $output));
        }
        
        $command = sprintf(
            'gpg --batch --yes --delete-keys %s 2>&1',
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new GPGException('Failed to delete public key: ' . implode("\n", $output));
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function publishToKeyserver(string $keyId, string $keyserverUrl): bool
    {
        $command = sprintf(
            'gpg --keyserver %s --send-keys %s 2>&1',
            escapeshellarg($keyserverUrl),
            escapeshellarg($keyId)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new GPGException('Keyserver publish failed: ' . implode("\n", $output));
        }
        
        return true;
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

        $encryptedPath = $filePath . '.gpg';

        $recipientArgs = '';
        foreach ($fingerprints as $fingerprint) {
            $recipientArgs .= ' --recipient ' . escapeshellarg($fingerprint);
        }

        $command = sprintf(
            'gpg --batch --yes --encrypt%s --output %s %s 2>&1',
            $recipientArgs,
            escapeshellarg($encryptedPath),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new EncryptionFailedException('Multi-recipient GPG encryption failed: ' . implode("\n", $output));
        }

        return $encryptedPath;
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
        $current = null;
        
        foreach ($output as $line) {
            $parts = explode(':', $line);
            
            if ($parts[0] === 'pub') {
                $current = [
                    'key_id' => $parts[4],
                    'fingerprint' => '',
                    'email' => '',
                    'created' => date('Y-m-d H:i:s', (int)$parts[5]),
                ];
            }
            
            if ($current !== null && $parts[0] === 'fpr') {
                $current['fingerprint'] = $parts[9];
            }
            
            if ($current !== null && $parts[0] === 'uid') {
                $current['email'] = $parts[9];
                $keys[] = $current;
                $current = null;
            }
        }
        
        return $keys;
    }
}
