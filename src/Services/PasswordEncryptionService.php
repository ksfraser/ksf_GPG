<?php
declare(strict_types=1);

namespace Ksf\GPG\Services;

use Ksf\GPG\Contracts\EncryptionInterface;
use Ksf\GPG\Entity\EncryptedFile;
use Ksf\GPG\Exception\EncryptionFailedException;

/**
 * Password Encryption Service
 * 
 * Provides password-based symmetric encryption.
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: FR-GPG-006 Password-Based Encryption
 * 
 * @since 1.0.0
 */
class PasswordEncryptionService implements EncryptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function encrypt(string $filePath, string $password): EncryptedFile
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
            throw new EncryptionFailedException(
                "Password-based encryption failed: " . implode("\n", $output)
            );
        }

        $encryptedFile = new EncryptedFile($filePath);
        $encryptedFile->setEncryptedPath($encryptedPath);
        $encryptedFile->setPasswordProtected(true);
        
        return $encryptedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(string $filePath, string $password): string
    {
        if (!file_exists($filePath)) {
            throw new EncryptionFailedException("File not found: {$filePath}");
        }

        // Determine output path (remove .gpg extension if present)
        $outputPath = preg_replace('/\.gpg$/', '', $filePath);
        
        $command = sprintf(
            'gpg --batch --yes --decrypt --passphrase %s --output %s %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($outputPath),
            escapeshellarg($filePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new EncryptionFailedException(
                "Password-based decryption failed: " . implode("\n", $output)
            );
        }

        return $outputPath;
    }

    /**
     * {@inheritdoc}
     */
    public function generatePassword(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
