<?php

/**
 * Unit testing for Password Generator library
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com> 
 * @web    http://eftakhairul.com
 */
class RandomPasswordGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIsReturnCorrectStringCount()
    {
        //Create object
        $passwordGenerator =  new Eftakhairul\RandomPasswordGenerator();

        $this->assertEquals(9, strlen($passwordGenerator->useNumbers()->generatePassword(9) ));
    }


    public function testIsNumberContain()
    {
        //Create object
        $passwordGenerator = new Eftakhairul\RandomPasswordGenerator();
        $output            = preg_match("/[0-9]/i", $passwordGenerator->useNumbers(3)->generatePassword());

        $this->assertEquals(true, $output);
    }
}