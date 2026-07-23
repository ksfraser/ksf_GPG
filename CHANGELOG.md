# Changelog

All notable changes to ksf_GPG will be documented in this file.

## [1.2.0] - 2026-07-23

### Added
- Multi-recipient encryption via `GnuPGAdapterInterface::encryptWithRecipients()`
- `processHookRequest()` orchestration in GPGService
- Contact resolution interfaces (`ContactResolverInterface`, `SigningKeyResolverInterface`)
- Hook DTOs (`GPGHookRequest`, `GPGHookResponse`, `GPGTarget`, `GPGTargetResult`)
- `GPGTargetResult` carries three paths (original, encrypted, signed)
- `GPGHookResponse` provides bulk accessors (`getEncryptedPaths()`, `getSignedPaths()`)
- Team key support in `0_ksf_gpg_team_keys` table

### Changed
- Refactored all services to use `GnuPGAdapterInterface` via `GnuPGAdapterFactory`
- Updated CryptGpgAdapter symmetric encryption to use exec fallback
- Updated hooks to use DTOs and `processHookRequest()`

## [1.1.0] - 2026-07-22

### Added
- `GPGResponseProcessorTrait` for FA module integration
- Portal/ESS key registration pages
- Team key management support

### Fixed
- Namespace consistency across all files

## [1.0.0] - 2026-07-21

### Added
- Initial release
- GPGService with signing, encryption, and key management
- KeyManagerService for key lifecycle operations
- PasswordEncryptionService for symmetric encryption
- KeyserverService for keyserver operations
- GnuPG adapter layer with CryptGpg and CLI adapters
- Value objects (Fingerprint, KeyId, EmailAddress)
- Exception hierarchy
- Unit test suite (93 tests, 247 assertions)
