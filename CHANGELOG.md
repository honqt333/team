# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project documentation

### Changed
- (add changes here)

### Fixed
- (add fixes here)

### Security
- (add security notes here)

---

## [1.x.x] - 2026-05-25

### Added
- Parts invoice document type for direct sales (no work order)
- PrintEngine integration with QR code for thermal template

### Fixed
- Merged services and parts arrays in parts_invoice print view
- Restored missing print engine integration
- Database totals inheritance
- Payment receipt print layouts

### Changed
- Customized parts_invoice print layout and labels
- Removed general invoice totals block from payments view
- Added details (notes) column to payments print layouts

---

## [1.x.x] - 2026-05-24

### Added
- Landing page functionality

---

## [1.x.x] - 2026-05-XX (Add date here)

### Added
- (describe feature)

### Changed
- (describe change)

### Deprecated
- (describe deprecation)

### Removed
- (describe removal)

### Fixed
- (describe fix)

### Security
- (describe security fix)

---

## Version History

### v0.1.x (Early Development)
- Initial Laravel setup
- Multi-tenancy implementation
- Customer and Vehicle management
- Work Orders module
- Quotes and Approvals
- Invoicing system
- Inventory management
- HR module
- Employee portal

---

## How to add entries

Copy this template:

```markdown
### [Version] - YYYY-MM-DD

### Added
- New feature description

### Changed
- Change description

### Deprecated
- Feature deprecation notice

### Removed
- Removed feature description

### Fixed
- Bug fix description

### Security
- Security fix description
```

---

*Generate changelog entries after each release using: `git log --oneline --pretty=format:"%h %s" v0.1.0..HEAD`*