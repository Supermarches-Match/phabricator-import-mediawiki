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



//
//$configFile = $args->getArg('config');
//if ($configFile == null) {
//  $args->printHelpAndExit();
//}
//
//try {
//  $jsonRaw = Filesystem::readFile($configFile);
//  $config = json_decode($jsonRaw);
//  if (!is_object($config)) {
//    die("File '$configFile' is not valid JSON!");
//  }
//} catch (FilesystemException $e) {
//  die("File '$configFile' is not valid File!");
//}
//
//
//$type = $args->getArg('type');
//if ($type == null) {
//  $args->printHelpAndExit();
//}
//
//$action = $args->getArg('action');
//if ($type !== null) {
//  $action = 'replace';
//} else {
//  $all_actions = array('insert', 'replace');
//  if (!in_array($action, $all_actions)) {
//    $args->printHelpAndExit();
//  }
//}
//
//
//try {
//  $mediaWikiService = new MediaWikiService($config->wiki->url, $config->wiki->user, $config->wiki->pass);
//} catch (Exception $e) {
//  die("Error login to mediawiki : [$e->getCode()] $e->getMessage()");
//}
//
//try {
//  $phrictionService = new PhrictionService($config->conduit->token);
//} catch (Exception $e) {
//  die("Error creating conduit client : [$e->getCode()] $e->getMessage()");
//}
//
//try {
//  $converterService = new ConverterService();
//} catch (Exception $e) {
//  die("Error creating converter service : [$e->getCode()] $e->getMessage()");
//}
//
//
//$start = new DateTimeImmutable();
//echo "Started at : ".$start->format("Y-m-d H:i:s")."\n";
//separator();
//
//$categories = array();
//$pages = array();
//
//switch ($type) {
//  case "allcat":
//    echo " * Convert all categories with their pages\n";
//    $categories = $mediaWikiService->getAllCategories();
//    echo " * ".count($categories)." categories will be imported\n";
//    break;
//  case "allpage":
//    echo " * Convert all pages with their categories\n";
//    $pages = $mediaWikiService->getAllPages();
//    echo " * ".count($pages)." pages will be imported\n";
//    break;
//  case "cat":
//    echo " * Convert all specified categories with their pages\n";
//    $categories = $config->categories;
//    echo " * ".count($categories)." categories will be imported\n";
//    break;
//  case "pages":
//    echo " * Convert all specified pages with their categories\n";
//    $pages = $config->pages;
//    echo " * ".count($pages)." pages will be imported\n";
//    break;
//}
//separator();
//
////Process Categories
//foreach ($categories as $categoryName) {
//  if ($categoryName == null && trim($categoryName) === "") {
//    continue;
//  }
//  echo "Importing $categoryName";
//  $category = new PhrictionCategory(trim($categoryName), '', $config->wiki->url);
//
//  $categoryPages = $mediaWikiService->getPageByCategoryName($category->getName());
//  if ($categoryPages == null || count($categoryPages) == 0) {
//    echo " (0 pages)\n";
//    echo "Category ignored \n";
//    separator();
//    continue;
//  }
//
//  echo " (".count($categoryPages)." pages)\n";
//  foreach ($categoryPages as $categoryPage) {
//    echo " * Process $categoryPage->title\n";
//
//    if (property_exists($categoryPage, "pageid") && trim($categoryPage->pageid != "")) {
//      $pageContent = $mediaWikiService->getPageDataById($categoryPage->pageid);
//    }
//    $phrictionPage = new PhrictionPage($categoryPage->title, $pageContent, $config->wiki->url);
//    $converterService->convertMediaWikiContentToPhriction($phrictionPage, $categories);
//
//    if (count($phrictionPage->getCategories()) > 0) {
//      //page with prefix must have prefix created before page
//      $cat = new PhrictionCategory($phrictionPage->getCategories()[0], '', $config->wiki->url);
//      $phrictionService->postPage($cat);
//    }
//
//    if ($phrictionService->postPage($phrictionPage)) {
//      echo 'imported as '.$phrictionPage->getUrl();
//      $category->setContent($category->getContent()."* [[{$phrictionPage->getUrl()}|{$phrictionPage->getTitle()}]]\n");
//    }
//    separator();
//  }
//  if ($phrictionService->postPage($category)) {
//    echo 'imported as '.$category->getUrl();
//  }
//}
//
//
//$end = new DateTimeImmutable();
//$execution = $end->diff($start);
//echo "Finished at : ".$end->format("Y-m-d H:i:s")."\n";
//echo "Executed in ".$execution->format("%ss %Imin %Hh");
//
//exit(0);
//
///* ******************************* FUNCTION ******************************* */
//function separator() {
//  echo "----------------------------------------------------\n";
//}
//
//?>
