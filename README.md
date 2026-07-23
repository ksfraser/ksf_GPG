# ksf_GPG

GPG Business Logic Library for PHP 7.3+

## Overview

Framework-agnostic GPG operations library providing key management, signing, encryption, and keyserver operations. Can be used by FrontAccounting, WordPress, or any PHP application.

## Requirements

- PHP >= 7.3
- GnuPG (`gpg` command-line tool) or `pear/crypt_gpg` extension
- `ext-zip` (for archive operations)

## Installation

```bash
composer require ksfraser/ksf_gpg
```

Or add to your `composer.json`:

```json
{
    "require": {
        "ksfraser/ksf_gpg": "dev-main"
    }
}
```

## Features

- **Key generation** — RSA/DSA/EdDSA key pairs
- **File signing** — Detached signatures
- **Encryption** — Public key and password-based (AES-256)
- **Multi-recipient encryption** — Single file encrypted to multiple keys
- **Keyserver operations** — Publish, search, import from keyservers
- **Hook DTOs** — Typed request/response objects for module integration
- **Contact resolution** — Abstract interface for plugging into any contact system

## Architecture

```
src/
├── Contracts/          # Interfaces (GPGServiceInterface, KeyManagerInterface, etc.)
├── Adapter/            # GnuPG adapter layer (CryptGpg, CLI fallback)
├── Hook/               # DTOs for inter-module hook communication
├── Entity/             # Domain entities (GPGKey, KeyPair, EncryptedFile)
├── ValueObject/        # Immutable value objects (Fingerprint, KeyId)
├── Repository/         # Data access abstraction
├── Exception/          # Exception hierarchy
├── Event/              # Domain events
└── Services/           # Business logic (GPGService, KeyManagerService, etc.)
```

## Usage

### Basic Operations

```php
use ksfraser\GPG\Services\GPGService;

$gpg = new GPGService();

// Sign a file
$signed = $gpg->signFile($filePath, $contactEmail);

// Encrypt for recipient
$encrypted = $gpg->encryptForContact($filePath, $contactEmail);

// Sign and encrypt
$protected = $gpg->signAndEncrypt($filePath, $contactEmail);

// Password-based encryption (no key required)
$encrypted = $gpg->encryptWithPassword($filePath, $password);

// Generate new key
$key = $gpg->generateKey($email, $passphrase);

// Publish to keyserver
$gpg->publishToKeyserver($keyId);
```

### Multi-Recipient Encryption

```php
use ksfraser\GPG\Adapter\GnuPGAdapterFactory;

$adapter = GnuPGAdapterFactory::create();

// Encrypt a file to multiple recipients
$encrypted = $adapter->encryptWithRecipients(
    '/tmp/invoice.pdf',
    ['alice@example.com', 'bob@example.com']
);
```

### Hook-Based Integration

```php
use ksfraser\GPG\Hook\GPGHookRequest;
use ksfraser\GPG\Hook\GPGTarget;

// Create a request
$request = new GPGHookRequest('/tmp/file.pdf', GPGHookRequest::OPERATION_SIGN_ENCRYPT);
$request->addTarget(new GPGTarget('customer', 123, 'client@example.com'));

// Process via GPGService
$response = $service->processHookRequest($request, $contactResolver, $keyResolver);

// Inspect results
if ($response->isSuccess()) {
    $encryptedPaths = $response->getEncryptedPaths();
    $signedPaths = $response->getSignedPaths();
}
```

### GnuPG Adapter

```php
use ksfraser\GPG\Adapter\GnuPGAdapterFactory;

// Automatically selects best available adapter
$adapter = GnuPGAdapterFactory::create();

// Sign
$adapter->signFile('/tmp/doc.pdf', 'sender@example.com');

// Encrypt
$adapter->encryptFile('/tmp/doc.pdf', 'recipient@example.com');

// Password-based
$adapter->encryptWithPassword('/tmp/doc.pdf', 'secret');
$adapter->decryptWithPassword('/tmp/doc.pdf.gpg', 'secret');
```

## Key Storage Strategy

Keys are encrypted with a password before storage, allowing:
- Storage on any machine without key compromise
- No dependency on another key for decryption
- Multiple backup locations (DB, filesystem, Google Drive)

## Testing

```bash
composer install
./vendor/bin/phpunit
```

## License

Proprietary
