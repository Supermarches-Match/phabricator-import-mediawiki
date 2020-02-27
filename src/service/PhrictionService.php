<?php

class PhrictionService {
  private $client;

  /**
   * PhrictionService constructor.
   * @param string $token
   * @throws Exception
   */
  public function __construct(string $token) {
    if ($token == null) {
      throw new Exception("Failed to create client for conduit API : Token API cannot be null");
    }
    $this->client = new ConduitClient(PhabricatorEnv::getEnvConfig('phabricator.base-uri'));
    $this->client->setConduitToken($token);
  }

  /**
   * @param string $url
   * @return bool|null
   */
  public function getPageByUrl(string $url) {
    try {
      $apiParameters = array(
        'queryKey' => 'all',
        'constraints' => array(
          'paths' => array(
            "'".strtolower($url)."'",
          ),
        ),
        'attachments' => array(
          'content' => 'true',
        )
      );
      $result = $this->client->callMethodSynchronous('phriction.document.search', $apiParameters);
      if ($result['status'] == 'exists' && array_key_exists('data', $result) && count($result['data']) > 0) {
        return $result['data'][0];
      }
      return null;
    } catch (Exception $e) {
      phlog($e);
      return null;
    }
  }

  public function postPage(AbstractPhrictionPage $phriction) {
    $page = $this->getPageByUrl($phriction->getUrl()."/");

    $apiParameters = array(
      "slug" => $phriction->getUrl(),
      "title" => $phriction->getTitle(),
      "content" => $phriction->getContent(),
      "description" => "Importé depuis ".$phriction->getOrigin()
    );

    $result = null;
    try {
      if ($page != null) {
        if ($page['attachments']['content']['content']['raw'] == $phriction->getContent())
          $result = $this->client->callMethodSynchronous('phriction.edit', $apiParameters);
      } else {
        $result = $this->client->callMethodSynchronous('phriction.create', $apiParameters);
      }

      return $result != null && $result['status'];
    } catch (Exception $e) {
      phlog($e);
      return false;
    }
  }

}
