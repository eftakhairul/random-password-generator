<?php

/**
 * Unit testing for Password Generator library
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com> 
 * @web    http://eftakhairul.com
 */
class RandomPasswordGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeNegated()
    {
        // Arrange
        $passwordGenerator =  new Eftakhairul\RandomPasswordGenerator();

        $this->assertEquals(9, strlen($passwordGenerator->useNumbers()->generatePassword(9) ));
    }
}