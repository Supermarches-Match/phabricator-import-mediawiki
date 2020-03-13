<?php

class MediaWikiService extends Phobject {
  private $url;
  private $user;
  private $pass;
  private $login;
  private $loginCookie;
  private $https;

  function __construct(string $url, string $user, string $pass) {
    $this->url = $url;
    $this->user = $user;
    $this->pass = $pass;
    $this->https = preg_match("/^(https:\/\/)/", $url);
    $this->loginWiki();
  }

  /**
   * @return array
   * @throws Exception
   */
  public function getAllCategories() {
    $categoriesUrl = $this->url."api.php?action=query&format=json&list=allcategories&aclimit=100&acfrom=";

    $result = new stdClass();
    $result->{'query-continue'} = new stdClass();
    $result->{'query-continue'}->allcategories = new stdClass();
    $result->{'query-continue'}->allcategories->acfrom = "";

    $categories = array();
    do {
      list($responseBody) = $this->getFuture($categoriesUrl.$result->{'query-continue'}->allcategories->acfrom);
      $result = json_decode($responseBody);

      foreach (array_column($result->query->allcategories, '*') as $category) {
        $categories[] = $category;
      }
    } while (property_exists($result, "query-continue") && $result->{'query-continue'}->allcategories->acfrom != null);

    return $categories;
  }

  public function getAllPages() {
    $pagesUrl = $this->url."api.php?action=query&format=json&list=allpages&aplimit=100&apfrom=";

    $result = new stdClass();
    $result->{'query-continue'} = new stdClass();
    $result->{'query-continue'}->allpages = new stdClass();
    $result->{'query-continue'}->allpages->apfrom = "";

    $pages = array();
    do {
      list($responseBody) = $this->getFuture($pagesUrl.$result->{'query-continue'}->allpages->apfrom);
      $result = json_decode($responseBody);

      foreach ($result->query->allpages as $page) {
        $pages[] = $page;
      }
    } while (property_exists($result, "query-continue") && $result->{'query-continue'}->allpages->apfrom != null);

    return $pages;
  }

  function getPageByCategoryName(string $categoryName) {
    $pages = [];

    $api = $this->url."/api.php?action=query&format=json&list=categorymembers&cmlimit=100&cmtitle=Category:".urlencode($categoryName)."";
    list($responseBody) = $this->getFuture($api);
    $categoryData = json_decode($responseBody);

    foreach ($categoryData->query->categorymembers as $page) {
      $pages[] = $page;
    }

    while (property_exists($categoryData, "query-continue") && $categoryData->{'query-continue'}->categorymembers->cmcontinue != null) {
      list($responseBody) = $this->getFuture($api."&cmcontinue=".urlencode($categoryData->{'query-continue'}->categorymembers->cmcontinue));
      $categoryData = json_decode($responseBody);

      foreach ($categoryData->query->categorymembers as $page) {
        $pages[] = $page;
      }
    }
    return $pages;
  }

  function getPageDataById(string $pageId) {
    $api = $this->url."/api.php?action=query&format=json&prop=revisions&rvprop=content&pageids=".$pageId;
    list($responseBody) = $this->getFuture($api);
    $data = json_decode($responseBody);

    if ($data == null || !property_exists($data->query->pages->{$pageId}, "revisions")) {
      return null;
    }
    return $data->query->pages->{$pageId}->revisions[0]->{'*'};
  }

  function getPageDataByTitle(string $title) {
    $api = $this->url."/api.php?action=query&format=json&prop=revisions&rvprop=content&titles=".$title;
    list($responseBody) = $this->getFuture($api);
    $data = json_decode($responseBody);

    if ($data == null || !property_exists($data->query, 'pages')) {
      return null;
    }
    foreach ($data->query->pages as $result) {
      if (mb_strtolower($result->title) === mb_strtolower($title)) {
        return $result;
      }
    }
    return $data->query->pages->{$title}->revisions[0]->{'*'};
  }

  function getPageImagesByName(string $pageName) {
    $api = $this->url."/api.php?action=query&format=json&prop=images&imlimit=100&titles=".urlencode($pageName);
    list($responseBody) = $this->getFuture($api);
    $data = json_decode($responseBody, true);

    $images = array();
    if (count($data['query']) > 0 && count($data['query']['pages']) > 0) {
      foreach ($data['query']['pages'] as $page) {
        if (array_key_exists('images', $page) && count($page['images']) > 0) {
          $images = $page['images'];
        }
      }
    }

    $result = array();
    foreach ($images as $image) {
      $api = $this->url."/api.php?action=query&prop=imageinfo&format=json&iiprop=timestamp|user|url&titles=".urlencode($image['title']);
      list($responseBody) = $this->getFuture($api);
      $data = json_decode($responseBody, true);

      if (count($data['query']) > 0 && count($data['query']['pages']) > 0) {
        foreach ($data['query']['pages'] as $page) {
          if (in_array('imageinfo', $page) && $page['imageinfo'] !== null) {
            $phImage = new PhrictionImage($page['title'], $page['imageinfo'][0]['url']);
            $result[] = $phImage;
            break;
          }
        }
      }
    }
    return $result;

  }

  /**
   * Connect to the mediawiki api
   * @throws Exception
   */
  private function loginWiki() {
    $connectUrl = $this->url."api.php?action=login&lgname=".urlencode($this->user)."&lgpassword=".urlencode($this->pass)."&format=json";

    list($responseBody, $headers) = $this->getFuture($connectUrl, "POST");

    foreach ($headers as $header) {
      if ($header[0] === "Set-Cookie") {
        $this->loginCookie = $header[1];
        break;
      }
    }

    $token = json_decode($responseBody)->login->token;

    $connectUrl = $connectUrl."&lgtoken=".$token;

    list($responseBody) = $this->getFuture($connectUrl, "POST");

    $response = json_decode($responseBody);
    if (property_exists($response, 'login') && $response->login->result == "Success") {
      $this->login = $response->login;
      $this->loginCookie = $response->login->cookieprefix."UserID=".$response->login->lguserid."; ".$response->login->cookieprefix."UserName=".$response->login->lgusername."; ".$response->login->cookieprefix."Token=".$response->login->lgtoken;
    } else {
      throw new Exception("Failed to login on MediaWikiService [$this->url]");
    }
  }

  /**
   * @param string $connectUrl : Fully-qualified URI to send a request to.
   * @param string $method : Select the HTTP method (e.g., "GET", "POST", "PUT") to use for the request. By default, requests use "GET"
   * @return tuple  HTTP request result <body, headers> tuple.
   * @throws Exception
   */
  private function getFuture(string $connectUrl, string $method = "GET") {
    if ($this->https) {
      $future = new HTTPSFuture($connectUrl);
    } else {
      $future = new HTTPFuture($connectUrl);
    }
    $future->setMethod($method);
    if ($this->loginCookie != null) {
      $future->addHeader("Cookie", $this->loginCookie);
    }
    return $future->resolvex();
  }
}
