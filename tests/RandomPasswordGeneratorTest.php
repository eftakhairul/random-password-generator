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
class RandomPasswordGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checking password's characters count correct or not
     */
    public function testIsReturnCorrectStringCount()
    {
        //Create object
        $passwordGenerator =  new Eftakhairul\RandomPasswordGenerator();

        //checking password's characters count correct or not
        $this->assertEquals(9, strlen($passwordGenerator->useNumbers()->generatePassword(9) ));
    }


    /**
     * Checking number exists inside password string or not
     */
    public function testIsNumberContain()
    {
        //Create object
        $passwordGenerator = new Eftakhairul\RandomPasswordGenerator();
        $output            = preg_match("/[0-9]/i", $passwordGenerator->useNumbers(3)->generatePassword());

        //checking number exists inside password string or not
        $this->assertEquals(true, $output);
    }
}