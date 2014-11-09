<?php
/*
* This file is part of the ramdom-passwrod-generator package.
*
* (c) Eftakhairul Islam <eftakhairul@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    $autoloader = require __DIR__ . '/../vendor/autoload.php';
    $autoloader->addPsr4('Eftakhairul\\', __DIR__.'/../src');
} else {
    throw new \Exception('Run "composer install --dev" to create autoloader.');
}


