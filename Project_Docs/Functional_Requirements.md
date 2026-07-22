# Functional Requirements - ksf_GPG

## Document Information
- **Module**: ksf_GPG
- **Version**: 1.0.0
- **Date**: 2026-07-22
- **Status**: Draft
- **Platform**: Framework-agnostic (PHP 7.3)
- **Dependencies**: PHP GnuPG extension

---

## Overview

ksf_GPG is the core business logic library for GPG operations. It provides key management, signing, encryption, and keyserver operations. This library is framework-agnostic and can be used by FA, WordPress, or any other platform adapter.

---

## FR-001 GPG Service Interface
**Satisfies**: BR-001

- FR-001.1 The system shall provide a `GPGServiceInterface` for all GPG operations.
- FR-001.2 The interface shall define methods for: sign, encrypt, decrypt, generate, import, export.
- FR-001.3 The interface shall be framework-agnostic.
- FR-001.4 The interface shall support dependency injection.

---

## FR-002 Key Management
**Satisfies**: BR-002

- FR-002.1 The system shall provide a `KeyManagerService` for key lifecycle.
- FR-002.2 The system shall generate RSA 4096-bit key pairs.
- FR-002.3 The system shall import keys from files or strings.
- FR-002.4 The system shall export public keys (never private keys without passphrase).
- FR-002.5 The system shall list all keys in the keyring.
- FR-002.6 The system shall delete keys with passphrase verification.
- FR-002.7 The system shall get key fingerprint and ID.

---

## FR-003 Key Entity
**Satisfies**: BR-003

- FR-003.1 The system shall provide a `GPGKey` entity class.
- FR-003.2 The entity shall store: keyId, fingerprint, email, publicKey, encryptedPrivateKey.
- FR-003.3 The entity shall track creation and modification timestamps.
- FR-003.4 The entity shall support published status tracking.

---

## FR-004 Value Objects
**Satisfies**: BR-004

- FR-004.1 The system shall provide a `Fingerprint` value object.
- FR-004.2 The system shall provide a `KeyId` value object.
- FR-004.3 The system shall provide an `EmailAddress` value object.
- FR-004.4 Value objects shall be immutable.
- FR-004.5 Value objects shall validate format on construction.

---

## FR-005 File Signing
**Satisfies**: BR-005

- FR-005.1 The system shall sign files using GPG detached signatures.
- FR-005.2 The system shall create `.sig` files.
- FR-005.3 The system shall return an `EncryptedFile` entity with paths.
- FR-005.4 The system shall throw `SigningFailedException` on failure.

---

## FR-006 File Encryption
**Satisfies**: BR-006

- FR-006.1 The system shall encrypt files for specific recipients.
- FR-006.2 The system shall support multiple recipients.
- FR-006.3 The system shall create `.gpg` encrypted files.
- FR-006.4 The system shall return an `EncryptedFile` entity with paths.
- FR-006.5 The system shall throw `EncryptionFailedException` on failure.

---

## FR-007 Password-Based Encryption
**Satisfies**: BR-007

- FR-007.1 The system shall provide a `PasswordEncryptionService`.
- FR-007.2 The service shall encrypt files using AES-256 symmetric cipher.
- FR-007.3 The service shall decrypt files with the same password.
- FR-007.4 The service shall generate secure random passwords.
- FR-007.5 Password encryption shall not require GPG keys.

---

## FR-008 Keyserver Integration
**Satisfies**: BR-008

- FR-008.1 The system shall provide a `KeyserverInterface`.
- FR-008.2 The interface shall publish public keys to keyservers.
- FR-008.3 The interface shall search keyservers by email.
- FR-008.4 The interface shall import keys from keyservers.
- FR-008.5 The interface shall handle keyserver timeouts.

---

## FR-009 EncryptedFile Entity
**Satisfies**: BR-009

- FR-009.1 The system shall provide an `EncryptedFile` entity.
- FR-009.2 The entity shall track: originalPath, encryptedPath, signedPath.
- FR-009.3 The entity shall track: isSigned, isEncrypted, isPasswordProtected.
- FR-009.4 The entity shall track recipientEmail.
- FR-009.5 The entity shall support fluent mutators.

---

## FR-010 Repository Interfaces
**Satisfies**: BR-010

- FR-010.1 The system shall provide a `KeyRepositoryInterface`.
- FR-010.2 The system shall provide a `FileRepositoryInterface`.
- FR-010.3 Interfaces shall be framework-agnostic.
- FR-010.4 Implementations shall be provided by platform adapters (FA, WP).

---

## FR-011 Exception Hierarchy
**Satisfies**: BR-011

- FR-011.1 The system shall provide a base `GPGException` class.
- FR-011.2 The system shall provide `KeyNotFoundException`.
- FR-011.3 The system shall provide `KeyserverException`.
- FR-011.4 The system shall provide `EncryptionFailedException`.
- FR-011.5 The system shall provide `SigningFailedException`.
- FR-011.6 All exceptions shall extend `GPGException`.

---

## FR-012 Domain Events
**Satisfies**: BR-012

- FR-012.1 The system shall provide `KeyGeneratedEvent`.
- FR-012.2 The system shall provide `FileSignedEvent`.
- FR-012.3 The system shall provide `FileEncryptedEvent`.
- FR-012.4 Events shall carry relevant context data.

---

## FR-013 Backup Support
**Satisfies**: BR-013

- FR-013.1 The system shall support rclone for Google Drive backup.
- FR-013.2 The system shall provide backup/restore methods.
- FR-013.3 The system shall log backup operations.

---

## FR-014 Dependencies
**Satisfies**: BR-014

- FR-014.1 The library shall declare `ext-gnupg` as required.
- FR-014.2 The library shall declare `ksfraser/exceptions` as required.
- FR-014.3 The library shall declare `ksfraser/traits` as required.
- FR-014.4 The library shall be PSR-4 autoloaded.
