<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — ksf_GPG
> Repo-specific overrides for `ksfraser/ksf_gpg`. Core principles (SOLID, DRY, TDD)
> cannot be overridden.
---
## Architecture Overview
GPG business logic library providing key management, signing, encryption, and keyserver operations. Framework-agnostic core that can be used by FA, WordPress, or any other platform adapter.
---
## Repository Structure
```
ksf_GPG/
├── src/
│   └── ksfraser/
│       └── GPG/
│           ├── Contracts/
│           │   ├── GPGServiceInterface.php
│           │   ├── KeyManagerInterface.php
│           │   ├── KeyserverInterface.php
│           │   └── EncryptionInterface.php
│           ├── Services/
│           │   ├── GPGService.php
│           │   ├── KeyManagerService.php
│           │   ├── KeyserverService.php
│           │   └── PasswordEncryptionService.php
│           ├── Entity/
│           │   ├── GPGKey.php
│           │   ├── KeyPair.php
│           │   └── EncryptedFile.php
│           ├── ValueObject/
│           │   ├── Fingerprint.php
│           │   ├── KeyId.php
│           │   └── EmailAddress.php
│           ├── Repository/
│           │   ├── KeyRepositoryInterface.php
│           │   └── FileRepositoryInterface.php
│           ├── Exception/
│           │   ├── GPGException.php
│           │   ├── KeyNotFoundException.php
│           │   ├── KeyserverException.php
│           │   └── EncryptionFailedException.php
│           └── Event/
│               ├── KeyGeneratedEvent.php
│               ├── FileSignedEvent.php
│               └── FileEncryptedEvent.php
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── GPGServiceTest.php
│   │   │   ├── KeyManagerServiceTest.php
│   │   │   └── PasswordEncryptionServiceTest.php
│   │   └── Entity/
│   │       └── GPGKeyTest.php
│   └── Integration/
│       ├── KeyserverTest.php
│       └── FileEncryptionTest.php
├── doc/
│   └── ProjectDocuments/
│       ├── BABOK/
│       └── PMBOK/
├── composer.json
├── phpunit.xml
└── AGENTS.md
```
---
## Namespace Convention
```php
ksfraser\GPG\                              # Root namespace (non-FA library)
ksfraser\GPG\Contracts\                    # Interfaces
ksfraser\GPG\Services\                     # Business logic services
ksfraser\GPG\Entity\                       # Domain entities
ksfraser\GPG\ValueObject\                  # Immutable value objects
ksfraser\GPG\Repository\                   # Data access abstraction
ksfraser\GPG\Exception\                    # Module exceptions
ksfraser\GPG\Event\                        # Domain events
```
---
## Dependencies
### Required Libraries
```json
{
    "require": {
        "php": ">=7.3,<8.0",
        "ext-gnupg": "*",
        "ksfraser/exceptions": "^1.3",
        "ksfraser/traits": "^1.0"
    }
}
```
### Repositories
```json
{
    "repositories": [
        {"type": "vcs", "url": "https://github.com/ksfraser/Exceptions"},
        {"type": "vcs", "url": "https://github.com/ksfraser/Traits"}
    ]
}
```
---
## Key Storage Strategy
### Problem
Keys need to be stored securely but recoverable. Users have lost keys due to:
- Old computer failures
- Hard drive failures
- Lost key files
### Solution
1. **Password-encrypted private keys**: Encrypted with passphrase before storage
2. **Multiple storage locations**: Database, filesystem, Google Drive backup
3. **No key dependency**: Can decrypt with password alone, no other key needed
4. **Public key backup**: Always backup public keys (can be regenerated from keyserver)
### Storage Locations
| Location | Type | Purpose |
|----------|------|---------|
| Database | Encrypted | Primary storage, indexed |
| Filesystem | Encrypted | Local cache, quick access |
| Google Drive | Encrypted | Off-site backup |
| Keyserver | Public | Public key recovery |
---
## Backup Strategy (Google Drive)
Using rclone for backup:
```bash
# Setup rclone (one-time)
rclone config
# Backup encrypted files
rclone copy /path/to/encrypted/files remote:gpg-backup/
# Restore from backup
rclone copy remote:gpg-backup/ /path/to/restore/
```
### Backup Script
```php
class GPGBackupService
{
    private string $rcloneRemote;
    private string $localPath;
    public function __construct(string $rcloneRemote, string $localPath)
    {
        $this->rcloneRemote = $rcloneRemote;
        $this->localPath = $localPath;
    }
    public function backup(): bool
    {
        $cmd = sprintf(
            'rclone copy %s %s:%s/',
            escapeshellarg($this->localPath),
            escapeshellarg($this->rcloneRemote),
            'gpg-backup'
        );
        exec($cmd, $output, $returnCode);
        return $returnCode === 0;
    }
    public function restore(): bool
    {
        $cmd = sprintf(
            'rclone copy %s:%s/ %s',
            escapeshellarg($this->rcloneRemote),
            'gpg-backup',
            escapeshellarg($this->localPath)
        );
        exec($cmd, $output, $returnCode);
        return $returnCode === 0;
    }
}
```
