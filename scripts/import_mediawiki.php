#!/usr/bin/env php
<?php

$root = dirname(dirname(__FILE__));
require_once $root.'/scripts/init/init-script.php';
init_script();

$args = new PhutilArgumentParser($argv);
$args->setTagline('Import MediaWikiService articles');
$args->setSynopsis(<<<EOSYNOPSIS
    **import_mediwiki.php** __command__ --config foo.json [options]

    Import by categories or by pages.
    Convert MW syntax to Remarkup.
    Create "category" pages with links to all imported articles.
EOSYNOPSIS
);
$args->parseStandardArguments();

$workflows = id(new PhutilClassMapQuery())
  ->setAncestorClass('ImportMediaWikiWorkflow')
  ->execute();
$workflows[] = new PhutilHelpArgumentWorkflow();
$args->parseWorkflows($workflows);
