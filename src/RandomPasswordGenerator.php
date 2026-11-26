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

    /**
     * @param Randomizer|null $randomizer Source of randomness. Defaults to the
     *        CSPRNG-backed Secure engine. Pass a seeded engine only in tests.
     */
    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer(new Secure());
    }

    /**
     * Generate a password of the given length.
     *
     * @throws \InvalidArgumentException If the length is outside 1..MAX_LENGTH.
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

        $chars = $this->pick(self::UPPERCASE . self::LOWERCASE, $length);

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
