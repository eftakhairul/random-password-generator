<?php

/**
 * Example of RandomPassword class
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com> * 
 * Web    http://eftakhairul.com
 */

require_once './RandomPasswordGenerator.php';

$testPass =  new RandomPasswordGenerator();
echo $testPass->useNumbers()->generatePassword(9) ; 
//Output: OBUDfej06 

echo '<br>';
echo $testPass->useNumbers(3)->generatePassword();
//Output: UNEad107

echo '<br>';
$testPass->useNumbers(4)->useSpecialChars(5);
echo $testPass->generatePassword(9);
//Output: @$#@^2763
