<?php

final class ImportMediaWikiWorkflowDuplicatesWorkflow
  extends ImportMediaWikiWorkflow {

  protected function didConstruct() {
    $this
      ->setName('duplicates')
      ->setExamples('**duplicate** --config foo.json [options]')
      ->setSynopsis(pht('Identify pages that will pages have the same URL in phabrictator'))
      ->setArguments(
        array(
          array(
            'name' => 'config',
            'param' => 'file',
            'help' => pht('JSON config file with wiki.url, conduit.user, and conduit.api, ....'),
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

    try {
      $mediaWikiService = new MediaWikiService($config->wiki->url, $config->wiki->user, $config->wiki->pass);
    } catch (Exception $e) {
      die("Error login to mediawiki : [$e->getCode()] $e->getMessage()");
    }

    $pageFinished = array();

    $start = new DateTimeImmutable();
    echo "Started at : ".$start->format("Y-m-d H:i:s")."\n";
    ScriptingUtils::separator();

    $pages = $mediaWikiService->getAllPages();

    echo " * ".count($pages)." pages will be checked\n";
    ScriptingUtils::separator();

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
        echo " * * No content (ignored) \n";
        ScriptingUtils::separator();
        continue;
      }

      $phrictionPage = new PhrictionPage($wikiPage->title, $pageContent, $config->wiki->url);

      if (strpos($phrictionPage->getContent(), '#REDIRECTION [[') !== false || strpos($phrictionPage->getContent(), '#REDIRECT [[') !== false) {
        echo " * * Redirection content (ignored)\n";
        ScriptingUtils::separator();
        continue;
      }

      $url = $phrictionPage->getUrl();
      echo " Page url : ".$url."\n";

      if(array_key_exists($url, $pageFinished)){
        $duplicate = $pageFinished[$url];
        $duplicate->addWikiUrl($phrictionPage->getOrigin());
      } else {
        $duplicate = new Duplicate();
        $duplicate->setPhabricatorUrl($url);
        $duplicate->addWikiUrl($phrictionPage->getOrigin());
      }
      $pageFinished[$url] = $duplicate;

      ScriptingUtils::separator();
    }

    echo "********** Result **********\n";
    ScriptingUtils::separator();
    foreach ($pageFinished as $duplicate) {
      if(count($duplicate->getWikiUrls()) > 1){
        echo " * duplicate : ".$duplicate->getPhabricatorUrl()."\n";
        foreach ($duplicate->getWikiUrls() as $origin) {
          echo " *** wiki url : ".$origin."\n";;
        }
        ScriptingUtils::separator();
      }
    }

    $end = new DateTimeImmutable();
    $execution = $end->diff($start);
    echo "Finished at : ".$end->format("Y-m-d H:i:s")."\n";
    echo "Executed in ".$execution->format("%ss %Imin %Hh");
    return 0;
  }
}

class Duplicate {
  var $phabricatorUrl = "";
  var $wikiUrls = array();

  /**
   * @return string
   */
  public function getPhabricatorUrl(): string {
    return $this->phabricatorUrl;
  }

  /**
   * @param string $phabricatorUrl
   */
  public function setPhabricatorUrl(string $phabricatorUrl): void {
    $this->phabricatorUrl = $phabricatorUrl;
  }

  /**
   * @return array
   */
  public function getWikiUrls(): array {
    return $this->wikiUrls;
  }

  /**
   * @param string $wikiUrl
   */
  public function addWikiUrl(string $wikiUrl): void {
    array_push($this->wikiUrls, $wikiUrl);
  }
}
