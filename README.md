# Random Password Generator Library

[![CI](https://github.com/eftakhairul/random-password-generator/actions/workflows/ci.yml/badge.svg)](https://github.com/eftakhairul/random-password-generator/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/eftakhairul/random-password-generator.svg)](https://packagist.org/packages/eftakhairul/random-password-generator)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/eftakhairul/random-password-generator/master/LICENSE)

A tiny PHP library that generates random passwords using a cryptographically secure random number generator (CSPRNG).

Requirements
------------
* PHP 8.2 or newer
* No runtime dependencies

Installation
------------
```bash
composer require eftakhairul/random-password-generator
```

Usage
-----
```php
use Eftakhairul\RandomPasswordGenerator;

// 16 random letters (default).
echo (new RandomPasswordGenerator())->generatePassword();             // e.g. qMgdfXmtJyDcgcwc

// 12 characters, exactly 2 of them digits.
echo (new RandomPasswordGenerator())->useNumbers()->generatePassword(12);   // e.g. FxA1SSjisiZ8

// 20 characters: 4 digits, 2 special characters, 14 letters, all shuffled.
echo (new RandomPasswordGenerator())
    ->useNumbers(4)
    ->useSpecialChars(2)
    ->generatePassword(20);                                           // e.g. 4n2xMWcrmnSPT0a[^3wW
```

| Method | Default | Description |
|--------|---------|-------------|
| `generatePassword(int $length = 16): string` | 16 | Returns a password of exactly `$length` characters (1–1024). |
| `useNumbers(int $count = 2): self` | 2 | Every password contains exactly `$count` digits. `0` disables digits. |
| `useSpecialChars(int $count = 1): self` | 1 | Every password contains exactly `$count` special characters. `0` disables them. |

Character sets (also available as class constants):

| Constant | Characters |
|----------|------------|
| `UPPERCASE` / `LOWERCASE` | `A–Z` / `a–z` |
| `DIGITS` | `0–9` |
| `SPECIAL_CHARS` | `!@#$%^&*()-_=+[]{}<>?` |

The remaining positions are filled with letters, and the whole password is shuffled, so digits and special characters can appear anywhere.

Errors throw `\InvalidArgumentException`:
* the length is below 1 or above 1024;
* a count passed to `useNumbers()` or `useSpecialChars()` is negative;
* the digit and special-character counts together exceed the requested length.

A failed call never changes the generator's settings.

Security
--------
By default, the generator uses PHP's `Random\Engine\Secure` engine, which is a CSPRNG. You can pass your own `Random\Randomizer` to the constructor **for testing only**, for example a seeded `Xoshiro256StarStar` that gives repeatable output. **Never use a seeded engine in production**: anyone who knows the seed can reproduce every password.

Upgrading from 1.x
------------------
Version 2.0 contains breaking changes, including a new default length, a new character layout and exceptions on invalid input. See [CHANGELOG.md](CHANGELOG.md).

Development
-----------
```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan (level max)
```

License
-------
MIT

Contributing
-----------
[Here](https://github.com/eftakhairul/random-password-generator/blob/master/CONTRIBUTING.md)

Author
-----------
[Eftakhairul Islam] - eftakhairul [at] gmail [dot] com

[Eftakhairul Islam]:http://eftakhairul.com/

Contributors
-----------
[Matthew Baggett]

[Matthew Baggett]:https://github.com/matthewbaggett
