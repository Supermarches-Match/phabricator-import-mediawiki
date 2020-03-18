<?php

final class ImportMediaWikiWorkflowPagesWorkflow
  extends ImportMediaWikiWorkflow {

  protected function didConstruct() {
    $this
      ->setName('pages')
      ->setExamples('**pages** --config foo.json [options]')
      ->setSynopsis(pht('Import pages and their categories'))
      ->setArguments(
        array(
          array(
            'name' => 'config',
            'param' => 'file',
            'help' => pht('JSON config file with wiki.url, conduit.user, and conduit.api, ....'),
          ),
          array(
            'name' => 'action',
            'param' => 'action',
            'help' => pht('Action to perform (insert, replace - defaults to replace)'),
          ),
        ));
  }

  public function execute(PhutilArgumentParser $args) {
    $configFile = $args->getArg('config');
    if ($configFile === null) {
      throw new PhutilArgumentUsageException(
        pht('Provide a a valid JSON config with "--config".'));
    }

    try {
      $jsonRaw = Filesystem::readFile($configFile);
      $config = json_decode($jsonRaw);
      if (!is_object($config)) {
        throw new PhutilArgumentUsageException(
          pht('Provide a a valid JSON config with "--config".'));
      }
    } catch (FilesystemException $e) {
      throw new PhutilArgumentUsageException(
        pht('Provide a a valid JSON config with "--config".'));
    }

    $action = $args->getArg('action');
    if ($action === null) {
      $action = true;
    } else {
      $all_actions = array('insert', 'replace');
      if (!in_array($action, $all_actions)) {
        $args->printHelpAndExit();
      }
      $action = $action === 'replace';
    }

    try {
      $mediaWikiService = new MediaWikiService($config->wiki->url, $config->wiki->user, $config->wiki->pass);
    } catch (Exception $e) {
      die("Error login to mediawiki : [$e->getCode()] $e->getMessage()");
    }

    try {
      $phrictionService = new PhrictionService($config->conduit->token, $action);
    } catch (Exception $e) {
      die("Error creating conduit client : [$e->getCode()] $e->getMessage()");
    }

    try {
      $converterService = new ConverterService();
    } catch (Exception $e) {
      die("Error creating converter service : [$e->getCode()] $e->getMessage()");
    }

    $pageFinished = array();

    $start = new DateTimeImmutable();
    echo "Started at : ".$start->format("Y-m-d H:i:s")."\n";
    ScriptUtils::separator();

    if (property_exists($config, 'pages') && count($config->pages) > 0) {
      echo " * Convert all specified pages\n";
      $pages = $config->pages;
    } else {
      echo " * Convert all pages \n";
      $pages = $mediaWikiService->getAllPages();
    }
    echo " * ".count($pages)." pages will be imported\n";
    ScriptUtils::separator();

    foreach ($pages as $wikiPage) {
      if (!property_exists($wikiPage, 'title')) {
        $wikiPage = $mediaWikiService->getPageDataByTitle($wikiPage);
      }
      echo " * Process $wikiPage->title\n";

      $pageContent = '';
      if (property_exists($wikiPage, "pageid") && trim($wikiPage->pageid !== "")) {
        $pageContent = $mediaWikiService->getPageDataById($wikiPage->pageid);
      }


      if ($pageContent === "") {
        echo " * * No content\n";
        ScriptUtils::separator();
        continue;
      }

      $phrictionPage = new PhrictionPage($wikiPage->title, $pageContent, $config->wiki->url);

      $images = $mediaWikiService->getPageImagesByName($phrictionPage->getSafeTitle());
      if (count($images) > 0) {
        foreach ($images as $image) {
          $phrictionService->getIdImage($image);
        }
      }

      $phrictionPage->setImages($images);
      $converterService->convertMediaWikiContentToPhriction($phrictionPage, array());

      if ($phrictionPage->getContent() === "") {
        echo " * * No content\n";
        ScriptUtils::separator();
        continue;
      }

      if ($phrictionService->postPage($phrictionPage)) {
        echo ' * * imported as '.$phrictionPage->getUrl()."\n";
      }
      ScriptUtils::separator();
    }

    $end = new DateTimeImmutable();
    $execution = $end->diff($start);
    echo "Finished at : ".$end->format("Y-m-d H:i:s")."\n";
    echo "Executed in ".$execution->format("%ss %Imin %Hh");
    return 0;
  }
}
