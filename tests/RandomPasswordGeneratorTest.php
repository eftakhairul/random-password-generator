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
}
