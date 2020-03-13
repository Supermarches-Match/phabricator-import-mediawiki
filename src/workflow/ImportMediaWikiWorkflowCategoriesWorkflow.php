<?php

final class ImportMediaWikiWorkflowCategoriesWorkflow
  extends ImportMediaWikiWorkflow {

  protected function didConstruct() {
    $this
      ->setName('categories')
      ->setExamples('**categories** --config foo.json [options]')
      ->setSynopsis(pht('Import categories and their pages'))
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

    if (property_exists($config, 'categories') && count($config->categories) > 0) {
      echo " * Convert all specified categories with their pages\n";
      $categories = $config->categories;
    } else {
      echo " * Convert all categories with their pages\n";
      $categories = $mediaWikiService->getAllCategories();
    }

    echo " * ".count($categories)." categories will be imported\n";
    ScriptUtils::separator();

    $parentContent = "";

    foreach ($categories as $categoryName) {
      if ($categoryName == null && trim($categoryName) === "") {
        continue;
      }
      echo "Importing $categoryName";
      $category = new PhrictionCategory(trim($categoryName), '', $config->wiki->url);

      $categoryPages = $mediaWikiService->getPageByCategoryName($category->getName());
      if ($categoryPages === null || count($categoryPages) === 0) {
        echo " (0 pages)\n";
        echo "Category ignored \n";
        ScriptUtils::separator();
        continue;
      }

      echo " (".count($categoryPages)." pages)\n";

      foreach ($categoryPages as $categoryPage) {
        echo " * Process $categoryPage->title\n";

        $pageContent = '';
        if (property_exists($categoryPage, "pageid") && trim($categoryPage->pageid !== "")) {
          $pageContent = $mediaWikiService->getPageDataById($categoryPage->pageid);
        }

        if($pageContent === ""){
          echo " * * No content\n";
          ScriptUtils::separator();
          continue;
        }

        $phrictionPage = new PhrictionPage($categoryPage->title, $pageContent, $config->wiki->url);

        if (in_array($categoryPage->pageid, $pageFinished)) {
          $category->setContent($category->getContent()."* [[{$phrictionPage->getUrl()}|{$phrictionPage->getTitle()}]]\n");
          echo " * * $categoryPage->title already done\n";
          continue;
        }

        $images = $mediaWikiService->getPageImagesByName($phrictionPage->getSafeTitle());
        if (count($images) > 0) {
          foreach ($images as $image) {
            $phrictionService->getIdImage($image);
          }
        }

        $phrictionPage->setImages($images);
        $converterService->convertMediaWikiContentToPhriction($phrictionPage, $categories);

        if($phrictionPage->getContent() === ""){
          echo " * * No content\n";
          ScriptUtils::separator();
          continue;
        }

        if (count($phrictionPage->getCategories()) > 0) {
          //page with prefix must have prefix created before page
          $cat = new PhrictionCategory($phrictionPage->getCategories()[0], 'To be completed', $config->wiki->url);
          $phrictionService->postPage($cat);
        }

        if ($phrictionService->postPage($phrictionPage)) {
          echo ' * * imported as '.$phrictionPage->getUrl()."\n";
          $category->setContent($category->getContent()."* [[{$phrictionPage->getUrl()}|{$phrictionPage->getTitle()}]]\n");
        }
        $pageFinished[] = $categoryPage->pageid;
      }
      if ($phrictionService->postPage($category)) {
        echo ' * imported as '.$category->getUrl()."\n";
        $parentContent = $parentContent."* [[{$category->getUrl()}|{$category->getTitle()}]]\n";
      }
      ScriptUtils::separator();
    }

    $parentCat = new PhrictionPage("Catégories", $parentContent, $config->wiki->url);
    $parentCat->setPrefix('');
    $parentCat->setOrigin($config->wiki->url."/index.php/Spécial:Catégories");
    if ($phrictionService->postPage($parentCat)) {
      echo "Parent categories created\n";
    }

    $end = new DateTimeImmutable();
    $execution = $end->diff($start);
    echo "Finished at : ".$end->format("Y-m-d H:i:s")."\n";
    echo "Executed in ".$execution->format("%ss %Imin %Hh");
    return 0;
  }
}


