<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = Finder::create()
    ->exclude('vendor')
    ->exclude('public/min')
    ->notPath('application/Bootstrap.php')
    ->in(__DIR__)
;

$config = new Config();
return $config->setRules([
        '@PhpCsFixer' => true,
    ])
    ->setFinder($finder)
;
