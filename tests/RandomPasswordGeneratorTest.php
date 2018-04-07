<?php

/**
 * Unit testing for Password Generator library
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com> 
 * @web    http://eftakhairul.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Eftakhairul;

use PHPUnit\Framework\TestCase;

class RandomPasswordGeneratorTest extends TestCase
{
    /**
     * Checking password's characters count correct or not
     */
    public function testIsReturnCorrectStringCount()
    {
        //Create object
        $passwordGenerator =  new RandomPasswordGenerator();

        //checking password's characters count correct or not
        $this->assertEquals(9, strlen($passwordGenerator->useNumbers()->generatePassword(9) ));
    }


    /**
     * Checking number exists inside password string or not
     */
    public function testIsNumberContain()
    {
        //Create object
        $passwordGenerator = new RandomPasswordGenerator();
        $output            = preg_match("/[0-9]/i", $passwordGenerator->useNumbers(3)->generatePassword());

        //checking number exists inside password string or not
        $this->assertEquals(1, $output);
    }

    /**
     * Checking the password with the special chars
     */
    public function testUseSpecialChars()
    {
        //Create object
        $passwordGenerator = new RandomPasswordGenerator();
        $output            = preg_match("/[@#$%^]/i", $passwordGenerator->useSpecialChars()->generatePassword());

        //checking password's character has the special characters
        $this->assertEquals(1, $output);
    }
}