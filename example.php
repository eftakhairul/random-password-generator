<?php

declare(strict_types=1);

/**
 * Example usage of Eftakhairul\RandomPasswordGenerator.
 *
 * Run: composer install && php example.php
 */

require __DIR__ . '/vendor/autoload.php';

use Eftakhairul\RandomPasswordGenerator;

// 16 random letters (default).
echo (new RandomPasswordGenerator())->generatePassword(), PHP_EOL;

// 12 characters, exactly 2 of them digits.
echo (new RandomPasswordGenerator())->useNumbers()->generatePassword(12), PHP_EOL;

// 20 characters: 4 digits, 2 special characters, 14 letters, all shuffled.
echo (new RandomPasswordGenerator())->useNumbers(4)->useSpecialChars(2)->generatePassword(20), PHP_EOL;
