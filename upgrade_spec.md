# Random Password Generator v2 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the library up to date after 10 years: generate passwords securely, support PHP 8.2–8.6, upgrade PHPUnit to a patched release, replace Travis CI with GitHub Actions, add more tests, and fix the docs. This ships as **v2.0.0** because it includes breaking changes.

**Architecture:** There is still one `final` class, `Eftakhairul\RandomPasswordGenerator`. It gets its randomness from PHP's built-in `\Random\Randomizer`, which uses the `Secure` (CSPRNG) engine by default. Tests can pass in a seeded engine so their results are deterministic. The generator draws exactly N digits and M special characters, fills the rest of the length with letters, then shuffles the whole string with the same randomizer, so no character type sits in a fixed position.

**Tech Stack:** PHP ^8.2, Composer 2, PHPUnit ^11.5.50 || ^12.5.22, PHPStan ^2.1, GitHub Actions (`actions/checkout@v7`, `shivammathur/setup-php@v2`).

**Spec:** This file. The audit findings below are the requirements. Each finding ID (S1…, C1…, D1…, T1…) is linked to the task that fixes it.

---

## Audit Findings (the requirements)

The audit ran on 2026-11-26 using local PHP 8.6.0 and checked php.net and Packagist.

### Security: `src/RandomPasswordGenerator.php`

| ID | Finding | Evidence | Fixed in |
|----|---------|----------|----------|
| S1 | Uses `mt_rand()`, which is not cryptographically secure. Anyone who knows the seed can reproduce the output. | `mt_srand(1234)` called twice produced `ANEZERIQvunemiqi` both times | Task 2 |
| S2 | The layout is fixed: uppercase letters alternating vowel/consonant, then lowercase letters, then special characters, then digits. The default password has about 27.1 bits of entropy, compared with 47.6 bits for 8 uniform characters from 62. | `APOTdure UVABfumi AVAVbobe AQAGwuwa AKAXnico` | Task 2 |
| S3 | Only 5 special characters (`@#$%^`), and digits and symbols are always appended at the end. | Lines 20, 59–72 | Tasks 2, 3 |
| S4 | The options can produce a password with no letters at all, and nothing signals it. | `useNumbers(4)->useSpecialChars(5)->generatePassword(9)` gave `^%^%#8908` | Task 3 (now explicit and documented) |
| S5 | If the length is too short, `generatePassword()` permanently switches off numbers and special characters on that instance. | After `useNumbers(10)`, calling `gen(8)` and then `gen(20)` gave `ELABAYAGUGiayifacemu` with no digits | Task 3 |
| S6 | A length of 0 or less returns `''` silently. | `gen(0)` and `gen(-5)` both returned `''` | Task 2 |

### Correctness and PHP 8

| ID | Finding | Fixed in |
|----|---------|----------|
| C1 | `example.php` crashes with `Class "RandomPasswordGenerator" not found` (namespace missing). | Task 4 |
| C2 | No type declarations, so `generatePassword("abc")` throws `TypeError: Unsupported operand types`. | Task 2 |
| C3 | The `$flag` state carries over between calls, so the same instance gives different layouts each time. | Task 2 |
| C4 | The docblocks declare the return type as `\RandomPasswordGenerator` (the namespace is missing). | Task 2 |

### Dependencies and PHP versions

| ID | Finding | Fixed in |
|----|---------|----------|
| D1 | `"php": ">=5.5.0"` in composer.json and "PHP >= 5.3" in the README. The supported PHP versions are 8.2, 8.3, 8.4, 8.5 and 8.6. PHP 8.2 security support ends 2026-12-31. | Tasks 1, 5 |
| D2 | `phpunit/phpunit: ^4.8\|^5.5\|^6.5`. None of these lines installs on PHP 8 (4.8.36 needs >=5.3.3, 5.7.27 needs ^5.6\|\|^7.0, 6.5.14 needs ^7.0), so `composer install` fails on every supported PHP. | Task 1 |
| D3 | The PHPUnit range allows versions affected by **CVE-2017-9841** (critical RCE, 4.8.19–4.8.27 and 5.0.10–5.6.2) and **CVE-2026-24765** (high, unsafe deserialization, <8.5.52, <9.6.33, <10.5.62, <11.5.50, <12.5.8). Newer advisories also affect 12.5.21 and 13.1.5 (CVE-2026-41570) and 13.4.0–13.4.3 (CVE-2026-119193). | Task 1 (floor `^11.5.50 \|\| ^12.5.22`) |
| D4 | The library has no runtime dependencies, so people who install it are not exposed through dependencies. | — |

### Tooling, tests and metadata

| ID | Finding | Fixed in |
|----|---------|----------|
| T1 | Travis CI's free `.org` service shut down in 2021. `.travis.yml` and the README badge are dead. | Tasks 1, 4, 5 |
| T2 | `phpunit.xml.dist` uses `<filter><whitelist>`, which was removed in PHPUnit 10. | Task 1 |
| T3 | `autoload-dev` maps the tests into the same `Eftakhairul\` namespace as the library code. | Task 1 |
| T4 | There are only 3 tests, and they never check randomness, character distribution, invalid input or state carried over between calls. | Tasks 2, 3 |
| T5 | composer.json has a `"version"` field (Composer advises against it), a stale `branch-alias` (`1.0.x-dev`) and a misspelled author name (`Eftakahirul`). | Task 1 |
| T6 | There is no static analysis, no `.gitattributes` (so tests ship in the dist archive) and no CHANGELOG. | Tasks 1, 4, 5 |

---

## Global Constraints

- PHP floor is `^8.2`. CI must pass on 8.2, 8.3, 8.4, 8.5 and 8.6.
- PHPUnit constraint is exactly `^11.5.50 || ^12.5.22`. Composer picks 11 on PHP 8.2 and 12 on PHP 8.3+. Test code must use only APIs that exist in both, which means PHP attributes (`#[DataProvider]`), static data providers, and no annotations.
- No runtime dependencies. All randomness comes from `\Random\Randomizer`. `rand`, `mt_rand`, `srand`, `mt_srand`, `array_rand`, `str_shuffle` and `shuffle` are banned in `src/`. CI enforces this.
- Every PHP file starts with `declare(strict_types=1);`.
- Package name stays `eftakhairul/random-password-generator`, namespace stays `Eftakhairul\`, class name stays `RandomPasswordGenerator`, and the method names `generatePassword`, `useNumbers` and `useSpecialChars` stay the same.
- Do all work on branch `upgrade/v2`. Release as `v2.0.0`.
- Commit messages follow Conventional Commits and end with the attribution trailer `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- After this upgrade, nothing in the repo may mention Travis except this file. Check with `git grep -il travis -- ':!upgrade_spec.md'`, which must print nothing.

## Breaking Changes (v1 → v2)

These go in the CHANGELOG (Task 5):

1. Requires PHP 8.2+.
2. The default length changes from **8 to 16**.
3. The output is fully shuffled random letters, not the "pronounceable" vowel/consonant pattern. The pronounceable mode is deliberately **not** kept, because the pattern is what reduces the entropy (S2).
4. The special character set grows from `@#$%^` to `!@#$%^&*()-_=+[]{}<>?`.
5. Invalid input throws `\InvalidArgumentException` instead of silently returning `''` or dropping options.
6. The class is `final`.

## Review Focus

Five ways a user is most likely to get hurt, each pinned to a test:

1. **A failed call must not change the instance's settings** (this is the S5 regression). After `useNumbers(10)->generatePassword(8)` throws, `generatePassword(20)` must still contain exactly 10 digits. Test: `testFailedGenerationDoesNotChangeOptions` (Task 3).
2. **Length boundaries 1, 1024 and 1025, plus digit+symbol counts equal to the length.** These must give exact lengths, or a clear exception at 1025. Tests: `testReturnsRequestedLength`, `testRejectsLengthAboveMaximum` (Task 2) and `testOptionsMayFillEntirePassword` (Task 3).
3. **Regex metacharacters in `SPECIAL_CHARS`** (`^ $ [ ] ( ) { } ? * + -`). Tests that count special characters must use `preg_quote(..., '/')` inside a character class, otherwise the counts come out wrong. Test: `testUseSpecialCharsAddsExactCount` (Task 3).
4. **Reusing an instance must not carry state over.** Calling the same instance 50 times must give the same composition every time and outputs that are pairwise distinct. Test: `testRepeatedCallsHaveIdenticalComposition` (Task 3).
5. **Lowest dependency versions on PHP 8.2** (PHPUnit 11.5.50). Behavior must not depend on newer PHPUnit. This is covered by the `lowest` matrix entry in the CI job (Task 4).

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `composer.json` | Modify | Constraints, autoload, scripts, metadata |
| `phpunit.xml.dist` | Rewrite | PHPUnit 11/12 configuration |
| `phpstan.neon.dist` | Create | Static analysis at level max |
| `.travis.yml` | **Delete** | — |
| `.github/workflows/ci.yml` | Create | GitHub Actions CI |
| `.gitattributes` | Create | Keep dev files out of the dist archive |
| `.gitignore` | Modify | Add `.phpunit.cache/` |
| `src/RandomPasswordGenerator.php` | Rewrite | The generator |
| `tests/RandomPasswordGeneratorTest.php` | Rewrite | All unit tests (`Eftakhairul\Tests` namespace) |
| `example.php` | Rewrite | Working CLI example |
| `README.md` | Rewrite | Requirements, install, usage, security notes, CI badge |
| `CHANGELOG.md` | Create | v2.0.0 notes and breaking changes |

---

### Task 0: Prerequisites

- [ ] **Step 1: Install Composer locally.** It is not installed on the dev machine.

Run: `brew install composer && composer --version`
Expected: `Composer version 2.x`

- [ ] **Step 2: Create the branch**

Run: `git checkout -b upgrade/v2`

---

### Task 1: Modern toolchain (Composer, PHPUnit, Travis removal)

Fixes D1, D2, D3, T1 (the config file), T2, T3, T5, and T6 (`.gitattributes`). The **existing v1 tests must pass unchanged** on PHP 8.6 under PHPUnit 12. That proves the toolchain works before any library code changes.

**Files:**
- Modify: `composer.json`, `.gitignore`, `tests/RandomPasswordGeneratorTest.php` (namespace and strict_types only)
- Rewrite: `phpunit.xml.dist`
- Create: `phpstan.neon.dist`, `.gitattributes`
- Delete: `.travis.yml`

**Interfaces:**
- Produces: `composer test` (runs PHPUnit) and `composer analyse` (runs PHPStan), which Task 4's CI uses. The test namespace is `Eftakhairul\Tests\`, mapped to `tests/`.

- [ ] **Step 1: Delete Travis**

Run: `git rm .travis.yml`

- [ ] **Step 2: Replace `composer.json` with:**

```json
{
    "name": "eftakhairul/random-password-generator",
    "description": "A tiny library to generate cryptographically secure random passwords",
    "keywords": ["password", "generator", "random", "security"],
    "homepage": "https://github.com/eftakhairul/random-password-generator",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Eftakhairul Islam",
            "email": "eftakhairul@gmail.com",
            "homepage": "http://eftakhairul.com"
        }
    ],
    "support": {
        "issues": "https://github.com/eftakhairul/random-password-generator/issues",
        "source": "https://github.com/eftakhairul/random-password-generator"
    },
    "require": {
        "php": "^8.2"
    },
    "require-dev": {
        "phpstan/phpstan": "^2.1",
        "phpstan/phpstan-phpunit": "^2.0",
        "phpunit/phpunit": "^11.5.50 || ^12.5.22"
    },
    "autoload": {
        "psr-4": { "Eftakhairul\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "Eftakhairul\\Tests\\": "tests/" }
    },
    "scripts": {
        "test": "phpunit",
        "analyse": "phpstan analyse --memory-limit=512M"
    },
    "config": {
        "sort-packages": true
    },
    "extra": {
        "branch-alias": {
            "dev-master": "2.x-dev"
        }
    },
    "minimum-stability": "stable"
}
```

(The `"version"` field is removed on purpose. Git tags set the version.)

- [ ] **Step 3: Replace `phpunit.xml.dist` with:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         cacheDirectory=".phpunit.cache"
         colors="true"
         executionOrder="random"
         failOnRisky="true"
         failOnWarning="true"
         failOnDeprecation="true"
         failOnNotice="true"
         beStrictAboutOutputDuringTests="true">
    <testsuites>
        <testsuite name="Random Password Generator">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 4: Create `phpstan.neon.dist`:**

```neon
includes:
    - vendor/phpstan/phpstan-phpunit/extension.neon
parameters:
    level: max
    paths:
        - src
        - tests
```

- [ ] **Step 5: Create `.gitattributes`:**

```
* text=auto eol=lf
/.github            export-ignore
/tests              export-ignore
/.gitattributes     export-ignore
/.gitignore         export-ignore
/phpunit.xml.dist   export-ignore
/phpstan.neon.dist  export-ignore
/upgrade_spec.md    export-ignore
/CONTRIBUTING.md    export-ignore
```

- [ ] **Step 6: Add `.phpunit.cache/` to `.gitignore`.**

- [ ] **Step 7: In the existing test file, add `declare(strict_types=1);`, change `namespace Eftakhairul;` to `namespace Eftakhairul\Tests;`, and add `use Eftakhairul\RandomPasswordGenerator;`.** Do not change any assertions yet.

- [ ] **Step 8: Verify**

Run: `composer validate --strict && composer update && composer audit && composer test`
Expected:
- `./composer.json is valid`
- `No security vulnerability advisories found`
- `OK (3 tests, 3 assertions)`

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "build: require PHP 8.2+, upgrade PHPUnit to patched release, drop Travis CI"
```

---

### Task 2: Secure core generator

Fixes S1, S2, S6, C2, C3 and C4. Rewrite the class from scratch. Nothing from v1's private state survives.

**Files:**
- Rewrite: `src/RandomPasswordGenerator.php`
- Rewrite: `tests/RandomPasswordGeneratorTest.php` (replace the 3 v1 tests; their checks are covered again in Task 3)

**Interfaces:**
- Produces (Task 3 and the docs use these exact names):

```php
namespace Eftakhairul;

final class RandomPasswordGenerator
{
    public const DEFAULT_LENGTH = 16;
    public const MAX_LENGTH     = 1024;
    public const LOWERCASE      = 'abcdefghijklmnopqrstuvwxyz';
    public const UPPERCASE      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const DIGITS         = '0123456789';
    public const SPECIAL_CHARS  = '!@#$%^&*()-_=+[]{}<>?';

    public function __construct(?\Random\Randomizer $randomizer = null);   // default: new Randomizer(new \Random\Engine\Secure())
    public function generatePassword(int $length = self::DEFAULT_LENGTH): string;  // throws \InvalidArgumentException
}
```

- Test helper used by Tasks 2 and 3, a private method in the test class: `private static function seeded(int $seed): RandomPasswordGenerator` returns `new RandomPasswordGenerator(new Randomizer(new Xoshiro256StarStar($seed)))`.

- [ ] **Step 1: Write the failing tests** in `Eftakhairul\Tests\RandomPasswordGeneratorTest`:

```php
public function testDefaultLengthIsSixteen(): void
{
    self::assertSame(16, strlen((new RandomPasswordGenerator())->generatePassword()));
}

/** @return iterable<string, array{int}> */
public static function validLengths(): iterable
{
    yield 'minimum' => [1];
    yield 'v1 default' => [8];
    yield 'long' => [64];
    yield 'maximum' => [1024];
}

#[DataProvider('validLengths')]
public function testReturnsRequestedLength(int $length): void
{
    self::assertSame($length, strlen((new RandomPasswordGenerator())->generatePassword($length)));
}

public function testDefaultPasswordContainsOnlyLetters(): void
{
    self::assertMatchesRegularExpression('/^[A-Za-z]{16}$/', (new RandomPasswordGenerator())->generatePassword());
}

/** @return iterable<string, array{int}> */
public static function invalidLengths(): iterable
{
    yield 'zero' => [0];
    yield 'negative' => [-5];
}

#[DataProvider('invalidLengths')]
public function testRejectsLengthBelowOne(int $length): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Password length must be between 1 and 1024, got {$length}.");
    (new RandomPasswordGenerator())->generatePassword($length);
}

public function testRejectsLengthAboveMaximum(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Password length must be between 1 and 1024, got 1025.');
    (new RandomPasswordGenerator())->generatePassword(1025);
}

public function testSameSeedGivesSameOutputAndDifferentSeedsDiffer(): void
{
    self::assertSame(self::seeded(42)->generatePassword(32), self::seeded(42)->generatePassword(32));
    self::assertNotSame(self::seeded(42)->generatePassword(32), self::seeded(43)->generatePassword(32));
}

public function testDefaultInstancesAreUnpredictable(): void
{
    // Secure engine: 1,000 32-char passwords must all be distinct (collision odds ≈ 2^-170).
    $g = new RandomPasswordGenerator();
    $seen = [];
    for ($i = 0; $i < 1000; $i++) { $seen[$g->generatePassword(32)] = true; }
    self::assertCount(1000, $seen);
}

public function testEveryLetterIsReachable(): void
{
    // Regression for S2: v1 could never put a vowel next to a vowel, and drew upper/lower from fixed halves.
    $all = '';
    $g = self::seeded(7);
    for ($i = 0; $i < 200; $i++) { $all .= $g->generatePassword(32); }
    $used = count_chars($all, 3);
    self::assertSame(RandomPasswordGenerator::UPPERCASE . RandomPasswordGenerator::LOWERCASE, $used);
}

public function testFirstHalfIsNotAlwaysUppercase(): void
{
    // Regression for S2's fixed upper-then-lower layout.
    $g = self::seeded(11);
    $lowerFirst = 0;
    for ($i = 0; $i < 100; $i++) { $lowerFirst += ctype_lower($g->generatePassword(8)[0]) ? 1 : 0; }
    self::assertGreaterThan(0, $lowerFirst);
}
```

Note: `count_chars($s, 3)` returns the unique bytes in byte order, which is A–Z followed by a–z. That is why the expected string is `UPPERCASE . LOWERCASE`.

- [ ] **Step 2: Run the tests and confirm they fail**

Run: `composer test`
Expected: FAIL. The constructor does not accept a `Randomizer`, the constants are undefined, and `generatePassword(0)` returns `''` with no exception.

- [ ] **Step 3: Implement `src/RandomPasswordGenerator.php`** with the interface above. Requirements:
  - Start the file with `declare(strict_types=1);`. Give every property, parameter and return a type. Docblocks must be accurate: `@throws \InvalidArgumentException` and no stray `@return \RandomPasswordGenerator`.
  - Store the randomizer in a `private readonly \Random\Randomizer $randomizer` property.
  - Validate the length first, and throw using the exact message from the tests.
  - Pick each character as `$set[$this->randomizer->getInt(0, strlen($set) - 1)]`, where `$set = self::UPPERCASE . self::LOWERCASE`.
  - Return `$this->randomizer->shuffleBytes($chars)`. Shuffling is pointless for letters alone, but Task 3 needs it; put it in now so the code path is final.
  - No mutable state apart from what Task 3 adds. Do not use `getBytesFromString()`, because it requires PHP 8.3.

- [ ] **Step 4: Run the tests and confirm they pass**

Run: `composer test && composer analyse`
Expected: `OK (13 tests, …)` and `[OK] No errors`.

- [ ] **Step 5: Commit**

```bash
git add src tests
git commit -m "feat!: generate passwords with a CSPRNG and uniform shuffled characters"
```

---

### Task 3: Digit and special-character options

Fixes S3, S4 and S5, and adds the remaining T4 tests.

**Files:**
- Modify: `src/RandomPasswordGenerator.php`
- Modify: `tests/RandomPasswordGeneratorTest.php`

**Interfaces:**
- Consumes: everything Task 2 produced.
- Produces:

```php
public function useNumbers(int $count = 2): self;       // exactly $count digits; 0 disables; <0 throws
public function useSpecialChars(int $count = 1): self;  // exactly $count from SPECIAL_CHARS; 0 disables; <0 throws
```

Exception messages (exact text):
- `"Number count must be zero or greater, got {$count}."`
- `"Special character count must be zero or greater, got {$count}."`
- `"Digit and special character counts ({$required}) exceed password length ({$length})."`

- [ ] **Step 1: Write the failing tests.** Add a helper: `private static function countSpecials(string $s): int` returns `preg_match_all('/[' . preg_quote(RandomPasswordGenerator::SPECIAL_CHARS, '/') . ']/', $s)`.

```php
public function testUseNumbersReturnsSameInstance(): void
{
    $g = new RandomPasswordGenerator();
    self::assertSame($g, $g->useNumbers());
    self::assertSame($g, $g->useSpecialChars());
}

/** @return iterable<string, array{int}> */
public static function counts(): iterable
{
    yield 'zero' => [0];
    yield 'one' => [1];
    yield 'v1 default' => [2];
    yield 'five' => [5];
}

#[DataProvider('counts')]
public function testUseNumbersAddsExactCount(int $count): void
{
    $p = (new RandomPasswordGenerator())->useNumbers($count)->generatePassword(16);
    self::assertSame(16, strlen($p));
    self::assertSame($count, preg_match_all('/\d/', $p));
    self::assertSame(16 - $count, preg_match_all('/[A-Za-z]/', $p));
}

#[DataProvider('counts')]
public function testUseSpecialCharsAddsExactCount(int $count): void
{
    $p = (new RandomPasswordGenerator())->useSpecialChars($count)->generatePassword(16);
    self::assertSame(16, strlen($p));
    self::assertSame($count, self::countSpecials($p));
    self::assertSame(16 - $count, preg_match_all('/[A-Za-z]/', $p));
}

public function testOptionsMayFillEntirePassword(): void
{
    // S4: now allowed explicitly, with an exact composition.
    $p = (new RandomPasswordGenerator())->useNumbers(4)->useSpecialChars(5)->generatePassword(9);
    self::assertSame(4, preg_match_all('/\d/', $p));
    self::assertSame(5, self::countSpecials($p));
}

public function testThrowsWhenRequiredCharactersExceedLength(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Digit and special character counts (9) exceed password length (8).');
    (new RandomPasswordGenerator())->useNumbers(4)->useSpecialChars(5)->generatePassword(8);
}

public function testFailedGenerationDoesNotChangeOptions(): void
{
    // S5 regression.
    $g = (new RandomPasswordGenerator())->useNumbers(10);
    try { $g->generatePassword(8); self::fail('Expected exception'); } catch (\InvalidArgumentException) {}
    self::assertSame(10, preg_match_all('/\d/', $g->generatePassword(20)));
}

public function testRejectsNegativeNumberCount(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Number count must be zero or greater, got -1.');
    (new RandomPasswordGenerator())->useNumbers(-1);
}

public function testRejectsNegativeSpecialCharCount(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Special character count must be zero or greater, got -1.');
    (new RandomPasswordGenerator())->useSpecialChars(-1);
}

public function testDigitsAndSpecialsAreNotPinnedToTheEnd(): void
{
    // S3 regression: v1 always appended symbols then digits.
    $g = self::seeded(3)->useNumbers(2)->useSpecialChars(1);
    $digitFirst = $letterLast = 0;
    for ($i = 0; $i < 200; $i++) {
        $p = $g->generatePassword(12);
        $digitFirst += ctype_digit($p[0]) ? 1 : 0;
        $letterLast += ctype_alpha($p[11]) ? 1 : 0;
    }
    self::assertGreaterThan(0, $digitFirst);
    self::assertGreaterThan(0, $letterLast);
}

public function testEveryDigitAndSpecialCharIsReachable(): void
{
    $g = self::seeded(5)->useNumbers(4)->useSpecialChars(4);
    $all = '';
    for ($i = 0; $i < 300; $i++) { $all .= $g->generatePassword(8); }
    $used = count_chars($all, 3);
    foreach (str_split(RandomPasswordGenerator::DIGITS . RandomPasswordGenerator::SPECIAL_CHARS) as $c) {
        self::assertStringContainsString($c, $used);
    }
}

public function testRepeatedCallsHaveIdenticalComposition(): void
{
    // C3 regression: no state carried over between calls.
    $g = (new RandomPasswordGenerator())->useNumbers(3)->useSpecialChars(2);
    $seen = [];
    for ($i = 0; $i < 50; $i++) {
        $p = $g->generatePassword(14);
        self::assertSame(3, preg_match_all('/\d/', $p));
        self::assertSame(2, self::countSpecials($p));
        $seen[$p] = true;
    }
    self::assertCount(50, $seen);
}
```

- [ ] **Step 2: Run the tests and confirm they fail**

Run: `composer test`
Expected: FAIL with `Call to undefined method …::useNumbers()`.

- [ ] **Step 3: Implement both setters and update `generatePassword()`**
  - Store the counts in `private int $numberCount = 0;` and `private int $specialCharCount = 0;`.
  - The setters validate their input and assign it. They never touch any other state.
  - `generatePassword()` reads the counts and never writes them (this is the S5 fix). Check the length first, then check that `numberCount + specialCharCount <= $length`.
  - Build the string from exactly `numberCount` characters of `DIGITS`, `specialCharCount` characters of `SPECIAL_CHARS`, and the rest from letters, all via `getInt`. Then call `shuffleBytes`.

- [ ] **Step 4: Run the tests and confirm they pass**

Run: `composer test && composer analyse`
Expected: `OK (30 tests, …)` and `[OK] No errors`.

- [ ] **Step 5: Commit**

```bash
git add src tests
git commit -m "feat!: exact digit/special counts, shuffled positions, validated options"
```

---

### Task 4: GitHub Actions CI and a working example

Fixes T1 (the CI replacement), T6 (static analysis in CI) and C1.

**Files:**
- Create: `.github/workflows/ci.yml`
- Rewrite: `example.php`

**Interfaces:**
- Consumes: `composer test` and `composer analyse` (Task 1), plus the public API from Tasks 2 and 3.
- Produces: a workflow named `CI` at `.github/workflows/ci.yml`. The README badge (Task 5) points to it.

- [ ] **Step 1: Rewrite `example.php`** as a CLI script. Requirements:
  - Start with `declare(strict_types=1);`.
  - Load the class with `require __DIR__ . '/vendor/autoload.php';` and `use Eftakhairul\RandomPasswordGenerator;`.
  - Print, one per line with `PHP_EOL`, the results of `generatePassword()`, `useNumbers()->generatePassword(12)` and `useNumbers(4)->useSpecialChars(2)->generatePassword(20)`, each on a **fresh** instance.
  - No `<br>` tags and no hard-coded sample output in comments.

Run: `php example.php`
Expected: three lines with lengths 16, 12 and 20. No errors.

- [ ] **Step 2: Create `.github/workflows/ci.yml`:**

```yaml
name: CI

on:
  push:
    branches: [master]
  pull_request:
  schedule:
    - cron: '0 6 * * 1'   # weekly: re-run audit to catch newly published advisories
  workflow_dispatch:

permissions:
  contents: read

concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  quality:
    name: Quality (validate, audit, banned functions, PHPStan)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v7

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
          tools: composer:v2

      - name: Validate composer.json
        run: composer validate --strict

      - name: Install dependencies
        run: composer update --no-interaction --no-progress --prefer-dist

      - name: Security audit
        run: composer audit

      - name: Forbid non-CSPRNG functions in src/
        run: |
          if grep -rnE '\b(mt_)?s?rand\(|\b(array_rand|str_shuffle|shuffle)\(' src/; then
            echo "::error::Insecure randomness function found in src/"
            exit 1
          fi

      - name: PHPStan
        run: composer analyse -- --no-progress --error-format=github

  tests:
    name: PHP ${{ matrix.php }} (${{ matrix.dependencies }} deps)
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.2', '8.3', '8.4', '8.5', '8.6']
        dependencies: [highest]
        include:
          - php: '8.2'
            dependencies: lowest
    steps:
      - uses: actions/checkout@v7

      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none
          tools: composer:v2
          ini-values: error_reporting=E_ALL, display_errors=On, zend.assertions=1

      - name: Install dependencies (highest)
        if: matrix.dependencies == 'highest'
        run: composer update --no-interaction --no-progress --prefer-dist

      - name: Install dependencies (lowest)
        if: matrix.dependencies == 'lowest'
        run: composer update --no-interaction --no-progress --prefer-dist --prefer-lowest --prefer-stable

      - name: Run tests
        run: composer test

      - name: Smoke-test example
        run: php example.php
```

- [ ] **Step 3: Verify locally.** Run the same commands CI runs:

Run:

```bash
composer validate --strict && composer audit && ! grep -rnE '\b(mt_)?s?rand\(|\b(array_rand|str_shuffle|shuffle)\(' src/ && composer analyse && composer test && php example.php
```

Expected: everything succeeds. `grep` prints nothing.

To check the workflow syntax, optionally run `actionlint .github/workflows/ci.yml` (`brew install actionlint`). Expected: no output.

Run `composer update --prefer-lowest --prefer-stable && composer test`.
Expected: PASS. Afterwards run `composer update` to restore the highest versions.

- [ ] **Step 4: Commit**

```bash
git add .github example.php
git commit -m "ci: add GitHub Actions workflow for PHP 8.2-8.6; fix example.php"
```

- [ ] **Step 5: After pushing, confirm on GitHub that all 7 jobs pass** (1 quality job and 6 tests jobs). If `setup-php` cannot provide PHP 8.6 yet, add `continue-on-error: ${{ matrix.php == '8.6' }}` to the tests job and record that in the PR description. Do **not** drop 8.6 from the matrix.

---

### Task 5: Documentation and release notes

Fixes D1 (the README), T1 (the badge) and T6 (the CHANGELOG).

**Files:**
- Rewrite: `README.md`
- Create: `CHANGELOG.md`

- [ ] **Step 1: Rewrite `README.md`** with these sections, in this order:
  1. Title and badges. Remove the Travis badge. Add the CI badge `[![CI](https://github.com/eftakhairul/random-password-generator/actions/workflows/ci.yml/badge.svg)](https://github.com/eftakhairul/random-password-generator/actions/workflows/ci.yml)`. Keep the license badge. Add a Packagist version badge: `https://img.shields.io/packagist/v/eftakhairul/random-password-generator.svg`.
  2. One-line description, mentioning that it uses a CSPRNG.
  3. **Requirements:** PHP 8.2 or newer. No runtime dependencies.
  4. **Installation:** `composer require eftakhairul/random-password-generator`.
  5. **Usage:** the three calls from `example.php`, plus a table of the options (`generatePassword(int $length = 16)`, `useNumbers(int $count = 2)`, `useSpecialChars(int $count = 1)`), the character sets, and the exceptions they throw.
  6. **Security:** uses `Random\Engine\Secure` by default. Explain that a custom `Randomizer` can be passed in for testing only, and that a seeded engine must never be used in production.
  7. **Upgrading from 1.x:** link to `CHANGELOG.md`.
  8. **Development:** `composer install`, `composer test`, `composer analyse`.
  9. License, Contributing, Author, Contributors (keep the existing entries).

- [ ] **Step 2: Create `CHANGELOG.md`** in Keep a Changelog format. The `## [2.0.0] - YYYY-MM-DD` entry (use the release date) needs:
  - **Security:** S1, S2, S3 and S5, each in one plain sentence.
  - **Changed:** list items 1–6 from *Breaking Changes* above, verbatim, plus "Testing infrastructure moved from Travis CI to GitHub Actions."
  - **Removed:** `.travis.yml` and the pronounceable vowel/consonant pattern.
  - **Fixed:** C1 and C3.

  Add a short `## [1.0.0]` entry: "Initial release."

- [ ] **Step 3: Verify**

Run: `git grep -il travis -- ':!upgrade_spec.md'`
Expected: no output.

Run: `composer test && composer analyse && php example.php`
Expected: all pass.

- [ ] **Step 4: Commit**

```bash
git add README.md CHANGELOG.md
git commit -m "docs: rewrite README for v2 and add CHANGELOG"
```

---

### Task 6: Release (needs the maintainer's approval)

- [ ] **Step 1:** Push `upgrade/v2` and open a PR against `master`. In the PR body, link this spec and copy the Breaking Changes list.
- [ ] **Step 2:** Once CI is green and the PR is merged, tag the release: `git tag -a v2.0.0 -m "v2.0.0" && git push origin v2.0.0`. Packagist picks up the tag through its GitHub hook. If the hook was never set up, update the package manually on packagist.org.
- [ ] **Step 3:** Optional, for the maintainer to decide: publish a GitHub Security Advisory for v1.x that describes S1 and S2, so users who depend on 1.x are told to upgrade.

---

## Out of Scope

These are deliberately left out (YAGNI) and can be revisited later:

- A pronounceable or "memorable" password mode.
- A guarantee of at least one uppercase and one lowercase letter.
- Excluding ambiguous characters (`0/O`, `l/1/I`).
- Custom character sets supplied by the user.
- A passphrase (word list) generator.
