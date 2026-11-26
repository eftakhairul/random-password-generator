<?php

declare(strict_types=1);

/**
 * Unit tests for the Random Password Generator library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eftakhairul\Tests;

use Eftakhairul\RandomPasswordGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

final class RandomPasswordGeneratorTest extends TestCase
{
    private static function seeded(int $seed): RandomPasswordGenerator
    {
        return new RandomPasswordGenerator(new Randomizer(new Xoshiro256StarStar($seed)));
    }

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
        for ($i = 0; $i < 1000; $i++) {
            $seen[$g->generatePassword(32)] = true;
        }
        self::assertCount(1000, $seen);
    }

    public function testEveryLetterIsReachable(): void
    {
        // Regression for S2: v1 drew upper/lower case from fixed halves in a vowel/consonant pattern.
        $all = '';
        $g = self::seeded(7);
        for ($i = 0; $i < 200; $i++) {
            $all .= $g->generatePassword(32);
        }
        // count_chars mode 3 returns unique bytes in byte order: A-Z then a-z.
        self::assertSame(
            RandomPasswordGenerator::UPPERCASE . RandomPasswordGenerator::LOWERCASE,
            count_chars($all, 3),
        );
    }

    public function testFirstHalfIsNotAlwaysUppercase(): void
    {
        // Regression for S2's fixed upper-then-lower layout.
        $g = self::seeded(11);
        $lowerFirst = 0;
        for ($i = 0; $i < 100; $i++) {
            $lowerFirst += ctype_lower($g->generatePassword(8)[0]) ? 1 : 0;
        }
        self::assertGreaterThan(0, $lowerFirst);
    }

    private static function countSpecials(string $s): int
    {
        return (int) preg_match_all('/[' . preg_quote(RandomPasswordGenerator::SPECIAL_CHARS, '/') . ']/', $s);
    }

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
        // S5 regression: v1 silently and permanently disabled numbers here.
        $g = (new RandomPasswordGenerator())->useNumbers(10);
        try {
            $g->generatePassword(8);
            self::fail('Expected exception');
        } catch (\InvalidArgumentException) {
            // expected
        }
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
        for ($i = 0; $i < 300; $i++) {
            $all .= $g->generatePassword(8);
        }
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
}
