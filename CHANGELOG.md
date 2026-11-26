# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

### Security
- Passwords are now generated with a cryptographically secure random number generator (`Random\Engine\Secure`) instead of `mt_rand()`, whose output could be reproduced from its seed.
- Characters are drawn uniformly and shuffled, replacing the fixed uppercase/lowercase vowel–consonant pattern that cut the default password's entropy to about 27 bits.
- Digits and special characters are no longer always appended at the end, and the special character set is larger.
- A password that is too short for the requested digits or special characters now throws an exception, instead of permanently disabling those options on the instance.

### Changed
1. Requires PHP 8.2+.
2. The default length changes from **8 to 16**.
3. The output is fully shuffled random letters, not the "pronounceable" vowel/consonant pattern.
4. The special character set grows from `@#$%^` to `!@#$%^&*()-_=+[]{}<>?`.
5. Invalid input throws `\InvalidArgumentException` instead of silently returning `''` or dropping options.
6. The class is `final`.
- Continuous integration now runs on GitHub Actions (PHP 8.2–8.6).

### Removed
- The legacy, defunct CI configuration and its README badge.
- The pronounceable vowel/consonant pattern.

### Fixed
- `example.php` no longer fails with `Class "RandomPasswordGenerator" not found`.
- No state is carried over between calls on the same instance.

## [1.0.0]
- Initial release.
