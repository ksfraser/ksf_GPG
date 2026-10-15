# ksf_GPG

GPG Business Logic Library

## Overview

Core GPG operations library used by FrontAccounting and other applications.
Provides key management, signing, encryption, and keyserver operations.

## Architecture

```
ksf_GPG/
├── core/           # Core GPG operations
├── interfaces/     # Abstract interfaces
├── services/       # Service implementations
└── tests/          # Unit tests
```

## Features

- Key generation and management
- File signing and verification
- Encryption/decryption
- Password-based encryption (no key required)
- Keyserver publishing and lookup
- Secure key storage with password protection
- Backup management

## Key Storage Strategy

Keys are encrypted with a password before storage, allowing:
- Storage on any machine without key compromise
- No dependency on another key for decryption
- Multiple backup locations (DB, filesystem, Google Drive)

## Usage

```php
use KSF\GPG\Services\GPGService;

$gpg = new GPGService();

// Sign a file
$signed = $gpg->signFile($filePath, $contactEmail);

// Encrypt for recipient
$encrypted = $gpg->encryptForContact($filePath, $contactEmail);

// Sign and encrypt
$protected = $gpg->signAndEncrypt($filePath, $contactEmail);

// Generate new key
$key = $gpg->generateKey($email, $passphrase);

// Publish to keyserver
$gpg->publishToKeyserver($keyId);
```

## Technical Notes

### Password-Based Encryption

Yes, GPG supports symmetric encryption using only a password:
```bash
gpg --symmetric --cipher-algo AES256 file.txt
```
This creates a file encrypted with a passphrase, no keypair required.

### Ansible Vault Portability

Yes, Ansible vault files are portable across machines:
- The vault password decrypts the file
- No key files needed on the target machine
- Same encrypted file can be decrypted on any machine with Ansible

However, for our use case, we're using GPG symmetric encryption directly,
which is more portable and doesn't require Ansible.

## Backup Strategy

Files are backed up to Google Drive using rclone:
1. Encrypted files stored locally
2. rclone syncs to Google Drive
3. Backup includes both original and encrypted versions
