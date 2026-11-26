<?php

declare(strict_types=1);

/**
 * Random Password Generator
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com>
 * @link   http://eftakhairul.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eftakhairul;

use Random\Engine\Secure;
use Random\Randomizer;

/**
 * Generates passwords using a cryptographically secure random number generator.
 */
final class RandomPasswordGenerator
{
    public const DEFAULT_LENGTH = 16;
    public const MAX_LENGTH     = 1024;
    public const LOWERCASE      = 'abcdefghijklmnopqrstuvwxyz';
    public const UPPERCASE      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const DIGITS         = '0123456789';
    public const SPECIAL_CHARS  = '!@#$%^&*()-_=+[]{}<>?';

    private readonly Randomizer $randomizer;

    private int $numberCount = 0;

    private int $specialCharCount = 0;

    /**
     * @param Randomizer|null $randomizer Source of randomness. Defaults to the
     *        CSPRNG-backed Secure engine. Pass a seeded engine only in tests.
     */
    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer(new Secure());
    }

    /**
     * Include exactly $count digits in every generated password. 0 disables digits.
     *
     * @throws \InvalidArgumentException If $count is negative.
     */
    public function useNumbers(int $count = 2): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException("Number count must be zero or greater, got {$count}.");
        }
        $this->numberCount = $count;

        return $this;
    }

    /**
     * Include exactly $count characters from SPECIAL_CHARS in every generated
     * password. 0 disables special characters.
     *
     * @throws \InvalidArgumentException If $count is negative.
     */
    public function useSpecialChars(int $count = 1): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException("Special character count must be zero or greater, got {$count}.");
        }
        $this->specialCharCount = $count;

        return $this;
    }

    /**
     * Generate a password of the given length. Digits and special characters
     * (if enabled) are mixed with letters at random positions.
     *
     * Configured options are only read here, never modified, so a failed call
     * leaves the instance unchanged.
     *
     * @throws \InvalidArgumentException If the length is outside 1..MAX_LENGTH,
     *         or the digit and special character counts exceed the length.
     */
    public function generatePassword(int $length = self::DEFAULT_LENGTH): string
    {
        if ($length < 1 || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Password length must be between 1 and %d, got %d.',
                self::MAX_LENGTH,
                $length,
            ));
        }

        $required = $this->numberCount + $this->specialCharCount;
        if ($required > $length) {
            throw new \InvalidArgumentException(sprintf(
                'Digit and special character counts (%d) exceed password length (%d).',
                $required,
                $length,
            ));
        }

        $chars = $this->pick(self::DIGITS, $this->numberCount)
            . $this->pick(self::SPECIAL_CHARS, $this->specialCharCount)
            . $this->pick(self::UPPERCASE . self::LOWERCASE, $length - $required);

        return $this->randomizer->shuffleBytes($chars);
    }

    /**
     * Pick $count characters uniformly at random from $set.
     */
    private function pick(string $set, int $count): string
    {
        $max = strlen($set) - 1;
        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $out .= $set[$this->randomizer->getInt(0, $max)];
        }

        return $out;
    }
}
