# AGENTS.md - ksf_GPG

> **DO NOT MODIFY THIS FILE.** Create `AGENTS.local.md` for project-specific overrides.

## Core Philosophy

This project follows enterprise-grade software engineering principles. Every decision should align with: **SOLID**, **DRY**, **SRP**, **DI**, and **TDD**.

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

## Coding Standards

### PHP Compatibility
- **Target**: PHP 7.3 (FA 2.4.19) — no PHP 8+ features
- Use `declare(strict_types=1);` at top of all PHP files
- Avoid PHP 8+ features until we drop PHP 7.3 support

### Naming Conventions
- **Interfaces**: `InterfaceNameInterface` (e.g., `GPGServiceInterface`)
- **Abstract classes**: `AbstractClassName` (e.g., `AbstractKeyManager`)
- **Services**: `ServiceNameService` (e.g., `KeyManagerService`)
- **Value Objects**: `ValueObjectName` (e.g., `Fingerprint`, `KeyId`)
- **Exceptions**: `ExceptionNameException` (e.g., `KeyNotFoundException`)
- **Events**: `EventNameEvent` (e.g., `KeyGeneratedEvent`)

### Documentation
Every class/method MUST have:
```php
/**
 * Short description
 * 
 * Long description with business context
 * 
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: Requirements analysis, Solution evaluation
 */
```

### DocBlock Standards
```php
/**
 * Create a new GPG key pair.
 *
 * @param string $email Email address for the key
 * @param string $passphrase Passphrase for the private key
 * @return KeyPair The generated key pair
 * @throws GPGException If key generation fails
 * @throws EncryptionFailedException If passphrase encryption fails
 *
 * @since 1.0.0
 * @see KeyManagerService::importKey()
 */
public function generateKey(string $email, string $passphrase): KeyPair
```

**Required tags**: `@param`, `@return`, `@throws`, `@since`
**Optional tags**: `@see`, `@link`, `@deprecated`

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

## Core Services

### GPGService

Main entry point for GPG operations:

```php
use ksfraser\GPG\Services\GPGService;

$gpg = new GPGService();

// Sign a file
$signed = $gpg->signFile($filePath, $email);

// Encrypt for recipient
$encrypted = $gpg->encryptForContact($filePath, $email);

// Sign and encrypt
$protected = $gpg->signAndEncrypt($filePath, $email);

// Password-based encryption (no key required)
$encrypted = $gpg->encryptWithPassword($filePath, $password);

// Generate new key
$key = $gpg->generateKey($email, $passphrase);

// Publish to keyserver
$gpg->publishToKeyserver($keyId);
```

### KeyManagerService

Key lifecycle management:

```php
use ksfraser\GPG\Services\KeyManagerService;

$keyManager = new KeyManagerService();

// Generate new key pair
$keyPair = $keyManager->generateKey('user@example.com', 'passphrase');

// Import existing key
$key = $keyManager->importKey($keyContent, $passphrase);

// Export public key
$publicKey = $keyManager->exportPublicKey($keyId);

// Get key fingerprint
$fingerprint = $keyManager->getFingerprint($keyId);

// List all keys
$keys = $keyManager->listKeys();

// Delete key
$keyManager->deleteKey($keyId, $passphrase);
```

### KeyserverService

Keyserver operations:

```php
use ksfraser\GPG\Services\KeyserverService;

$keyserver = new KeyserverService();

// Publish key to keyserver
$keyserver->publish($keyId);

// Search for keys by email
$keys = $keyserver->search('user@example.com');

// Import key from keyserver
$key = $keyserver->import($keyId);
```

### PasswordEncryptionService

Symmetric encryption without keys:

```php
use ksfraser\GPG\Services\PasswordEncryptionService;

$encryption = new PasswordEncryptionService();

// Encrypt with password
$encrypted = $encryption->encrypt($filePath, $password);

// Decrypt with password
$decrypted = $encryption->decrypt($encryptedPath, $password);

// Generate secure password
$password = $encryption->generatePassword(32);
```

---

## Entity Design

### GPGKey

```php
namespace ksfraser\GPG\Entity;

use ksfraser\GPG\ValueObject\Fingerprint;
use ksfraser\GPG\ValueObject\KeyId;
use ksfraser\GPG\ValueObject\EmailAddress;

class GPGKey
{
    private KeyId $keyId;
    private Fingerprint $fingerprint;
    private EmailAddress $email;
    private string $publicKey;
    private ?string $encryptedPrivateKey = null;
    private bool $isPublished = false;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $modifiedAt;

    public function __construct(
        KeyId $keyId,
        Fingerprint $fingerprint,
        EmailAddress $email,
        string $publicKey
    ) {
        $this->keyId = $keyId;
        $this->fingerprint = $fingerprint;
        $this->email = $email;
        $this->publicKey = $publicKey;
        $this->createdAt = new \DateTimeImmutable();
        $this->modifiedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getKeyId(): KeyId { return $this->keyId; }
    public function getFingerprint(): Fingerprint { return $this->fingerprint; }
    public function getEmail(): EmailAddress { return $this->email; }
    public function getPublicKey(): string { return $this->publicKey; }
    public function getEncryptedPrivateKey(): ?string { return $this->encryptedPrivateKey; }
    public function isPublished(): bool { return $this->isPublished; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getModifiedAt(): \DateTimeImmutable { return $this->modifiedAt; }

    // Mutators
    public function setEncryptedPrivateKey(string $key): self
    {
        $this->encryptedPrivateKey = $key;
        $this->modifiedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markAsPublished(): self
    {
        $this->isPublished = true;
        $this->modifiedAt = new \DateTimeImmutable();
        return $this;
    }
}
```

### EncryptedFile

```php
namespace ksfraser\GPG\Entity;

class EncryptedFile
{
    private string $originalPath;
    private ?string $encryptedPath = null;
    private ?string $signedPath = null;
    private bool $isSigned = false;
    private bool $isEncrypted = false;
    private bool $isPasswordProtected = false;
    private ?string $recipientEmail = null;
    private \DateTimeImmutable $createdAt;

    public function __construct(string $originalPath)
    {
        $this->originalPath = $originalPath;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters
    public function getOriginalPath(): string { return $this->originalPath; }
    public function getEncryptedPath(): ?string { return $this->encryptedPath; }
    public function getSignedPath(): ?string { return $this->signedPath; }
    public function isSigned(): bool { return $this->isSigned; }
    public function isEncrypted(): bool { return $this->isEncrypted; }
    public function isPasswordProtected(): bool { return $this->isPasswordProtected; }
    public function getRecipientEmail(): ?string { return $this->recipientEmail; }

    // Mutators
    public function setEncryptedPath(string $path): self
    {
        $this->encryptedPath = $path;
        $this->isEncrypted = true;
        return $this;
    }

    public function setSignedPath(string $path): self
    {
        $this->signedPath = $path;
        $this->isSigned = true;
        return $this;
    }

    public function setPasswordProtected(bool $protected): self
    {
        $this->isPasswordProtected = $protected;
        return $this;
    }

    public function setRecipientEmail(string $email): self
    {
        $this->recipientEmail = $email;
        return $this;
    }
}
```

---

## Exception Handling

### Hierarchy

```
\Exception (or \RuntimeException)
└── ksfraser\GPG\Exception\GPGException (base)
    └── ksfraser\GPG\Exception\KeyNotFoundException
    └── ksfraser\GPG\Exception\KeyserverException
    └── ksfraser\GPG\Exception\EncryptionFailedException
    └── ksfraser\GPG\Exception\SigningFailedException
```

### Usage

```php
use ksfraser\GPG\Exception\KeyNotFoundException;
use ksfraser\GPG\Exception\EncryptionFailedException;

try {
    $key = $keyManager->getKeyByEmail($email);
    if ($key === null) {
        throw new KeyNotFoundException("No GPG key found for: {$email}");
    }
    
    $encrypted = $gpg->encryptForContact($filePath, $email);
} catch (KeyNotFoundException $e) {
    // Handle missing key
    $logger->warning($e->getMessage());
} catch (EncryptionFailedException $e) {
    // Handle encryption failure
    $logger->error($e->getMessage());
    throw $e;
}
```

---

## Testing Standards

### TDD Workflow
1. **RED**: Write failing test
2. **GREEN**: Write minimal code to pass
3. **REFACTOR**: Improve while keeping tests green

### Coverage Requirements
- **Target**: 100% code coverage
- **Skipped tests = failed tests** (treat as incomplete)
- All new code requires tests

### Test Structure
```php
namespace ksfraser\GPG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use ksfraser\GPG\Services\GPGService;
use ksfraser\GPG\Exception\KeyNotFoundException;

class GPGServiceTest extends TestCase
{
    public function testSignFileSuccess(): void
    {
        // Arrange
        $service = new GPGService();
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, 'Test content');
        
        // Act
        $result = $service->signFile($filePath, 'test@example.com');
        
        // Assert
        $this->assertFileExists($result->getSignedPath());
        
        // Cleanup
        unlink($filePath);
    }

    public function testSignFileThrowsExceptionForMissingKey(): void
    {
        // Arrange
        $service = new GPGService();
        $filePath = '/tmp/test.txt';
        file_put_contents($filePath, 'Test content');
        
        // Act & Assert
        $this->expectException(KeyNotFoundException::class);
        $service->signFile($filePath, 'nonexistent@example.com');
        
        // Cleanup
        unlink($filePath);
    }
}
```

---

## Design Patterns

### Strategy Pattern
- Different encryption algorithms (RSA, DSA, EdDSA)
- Different keyserver implementations
- Different storage backends

### Factory Pattern
- Key creation from various inputs (file, string, keyserver)
- Entity creation from database results

### Repository Pattern
- Data access abstraction
- Interface-based design
- Testable without database

### Event Pattern
- Domain events for key generation, file signing, file encryption
- Decoupled notification system

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

---

## .gitignore

```
/vendor/
/composer.lock
.phpunit.cache/
.phpunit.result.cache
.idea/
.vscode/
*.log
```

**Never track vendor/ or composer.lock** — each developer runs `composer install`.

---

## Documentation Requirements

### Code Documentation
- All classes, methods, and complex logic require PHPDoc
- Include `@UML` reference for architecture diagrams
- Include `@BABOK` reference for requirements alignment

### Project Documents (`doc/ProjectDocuments/`)
```
doc/ProjectDocuments/
├── ProjectDcs/
│   ├── Architecture.md
│   ├── Functional Requirements.md
│   ├── Test Plan.md
│   └── UAT Plan.md
├── BABOK/
├── UML/
└── RTM/
```

### UML Generation
- Use `phpuml` or equivalent for class diagrams
- Document complex functions with sequence diagrams
- Update diagrams when architecture changes

---

## SOLID Principles Checklist

| Principle | Description | Checklist |
|-----------|-------------|-----------|
| **S**ingle Responsibility | One class, one purpose | Class has one reason to change |
| **O**pen/Closed | Open for extension, closed for modification | Use interfaces and abstraction |
| **L**iskov Substitution | Subtypes substitutable for base types | Child classes honor parent contracts |
| **I**nterface Segregation | Small, focused interfaces | Don't force unused methods |
| **D**ependency Inversion | Depend on abstractions | Inject dependencies via constructor |

---

## Code Review Checklist

- [ ] All new code has tests (100% coverage target)
- [ ] PHPDoc complete with `@param`, `@return`, `@throws`, `@since`
- [ ] No hardcoded values (use constants/config)
- [ ] No duplicate code (extract to shared library)
- [ ] Dependencies injected, not instantiated
- [ ] Exception handling for all external calls
- [ ] `.gitignore` excludes vendor/ and composer.lock
- [ ] Interfaces used for service contracts
- [ ] Value objects are immutable

---

## Git & Version Control

### Branch Naming
- `main` / `master` - Production-ready code
- `feature/*` - New features
- `fix/*` - Bug fixes
- `refactor/*` - Code refactoring

### Commit Messages
```
type(scope): description

feat(gpg): add password-based encryption
fix(keymanager): handle invalid key format
refactor(exception): simplify hierarchy
docs(readme): update installation steps
```

---

## Version Tagging

Follow Semantic Versioning (SemVer): `MAJOR.MINOR.PATCH`
- **MAJOR**: Incompatible API changes
- **MINOR**: New functionality (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

```bash
git tag -a v1.0.0 -m "Initial release with GPG functionality"
git push origin v1.0.0
```

---

## Local Overrides

Create `AGENTS.local.md` for project-specific overrides:

```markdown
# AGENTS.local.md
# Project-specific overrides for ksf_GPG

[Your overrides here]
```

**Note**: Core principles (SOLID, DRY, TDD) cannot be overridden.

---

## Development Workflow

All development is done in this repo. Do **not** edit files in production directly.

### Workflow Steps
1. **Develop** in this repo (feature branches preferred)
2. **Test**: `./vendor/bin/phpunit`
3. **Lint**: `php -l` on modified PHP files (no syntax errors)
4. **Commit** and **Push** branch to GitHub
5. **Merge** to `master` when ready
6. **Push** `master` to GitHub
