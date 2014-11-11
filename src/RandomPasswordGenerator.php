<?php

/**
 * Class RandomPasswordGenerator
 *
 * @author Eftakhairul Islam <eftakhairul@gmail.com> 
 * @web    http://eftakhairul.com
 */
namespace Eftakhairul;

class RandomPasswordGenerator
{
    private $vowels             = 'aeiouAEIOU';
    private $consonants         = 'bcdfghijklmnpqrstvwxyzBCDFGHIJKLMNPQRSTVWXYZ';
    private $numbers            = '1234567890';
    private $specialChars       = '@#$%^';
    private $flag               = 1;
    private $numberLength       = 0;
    private $specialCharsLength = 0;    
    private $isUseNumbers       = false;
    private $isUseSpecialChars  = false;


    /**
     * Generate the password based on filter parameters
     *
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 8)
    {        
        $password = '';
        
        if ($this->isUseNumbers === true && $length >= $this->numberLength) {
            $length -= $this->numberLength;
        } else {
            $this->isUseNumbers = false;         
        }
        
        if ($this->isUseSpecialChars === true && $length >= $this->specialCharsLength) {
            $length -= $this->specialCharsLength; 
        } else {
            $this->isUseSpecialChars = false;           
        }
        
        for ($count = 0; $count < $length; $count++)
        {
            if($count < $length/2){
                $password .= $this->upperCaseChar();              
            } else {
                $password .= $this->lowerCaseChar(); 
            }            
        }
        
        if ($this->isUseSpecialChars === true) {

            for ($count = 0; $count < $this->specialCharsLength; $count++)
            {
                $password .= $this->specialChars[mt_rand(0,4)];
            }
        }
        
        if ($this->isUseNumbers === true) {
            for ($count = 0; $count < $this->numberLength; $count++)
            {
                $password .= $this->numbers[mt_rand(0,9)];                
            }
        }
        
        return $password;        
    }


    /**
     * Set the length of number set and set active the isUseNumbers flag
     *
     * @param int $length
     * @return \RandomPasswordGenerator
     */
    public function useNumbers($length = 2)
    {
        $this->numberLength = $length;
        $this->isUseNumbers = true;

        return $this;
    }


    /**
     * Set the length of Special Chars set and set active the isUseSpecialChars flag
     *
     * @param int $length
     * @return \RandomPasswordGenerator
     */
    public function useSpecialChars($length = 1)
    {
        $this->specialCharsLength = $length;
        $this->isUseSpecialChars = true;

        return $this;
    }

    /**
     * @return mixed
     */
    private function upperCaseChar()
    {
        if ($this->flag === 1) {            
            $this->flag = 0;
            return $this->vowels[mt_rand(5,9)];
        } else {            
            $this->flag = 1;            
            return $this->consonants[mt_rand(22, 43)];
        }
    }

    /**
     * @return mixed
     */
    private function lowerCaseChar()
    {
        if ($this->flag === 0) {
            $this->flag = 1;
            return $this->vowels[mt_rand(0,4)];
            
        } else {
            $this->flag = 0;
            return $this->consonants[mt_rand(0, 21)];
        }
    }
}