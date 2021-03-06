<?php

class PhrictionService extends Phobject {
  private $client;
  private $replace;

  /**
   * PhrictionService constructor.
   * @param string $token
   * @param bool   $replace
   * @throws Exception
   */
  public function __construct(string $token, bool $replace) {
    $this->replace = $replace;
    if ($token == null) {
      throw new Exception("Failed to create client for conduit API : Token API cannot be null");
    }
    $this->client = new ConduitClient(PhabricatorEnv::getEnvConfig('phabricator.base-uri'));
    $this->client->setConduitToken($token);
  }

  /**
   * @param string $url
   * @return wild|null
   */
  public function getPageByUrl(string $url) {
    try {
      $apiParameters = array(
        'queryKey' => 'all',
        'constraints' => array(
          'paths' => array(
            $url,
          ),
        ),
        'attachments' => array(
          'content' => 'true',
        )
      );
      $result = $this->client->callMethodSynchronous('phriction.document.search', $apiParameters);
      return idxv($result, array('data', 0));
    } catch (Exception $e) {
      phlog($e);
      return null;
    }
  }

  public function postPage(AbstractPhrictionPage $phriction) {
    $page = $this->getPageByUrl($phriction->getUrl()."/");
    $content = $page != null ? idxv(
      $page,
      array(
        'attachments',
        'content',
        'content',
        'raw',
      )) : null;

    $status = $page != null ? idxv(
      $page,
      array(
        'fields',
        'status',
        'value'
      )) : null;

    $apiParameters = array(
      "slug" => $phriction->getUrl(),
      "title" => $phriction->getTitle(),
      "content" => $phriction->getContent(),
      "description" => "Importé depuis ".$phriction->getOrigin()
    );

    if ($page != null) {
      if ($this->replace && ($content !== $phriction->getContent() || $status === "deleted")) {
        $result = $this->client->callMethodSynchronous('phriction.edit', $apiParameters);
        return $result != null && $result['status'];
      } else {
        echo " * * No change \n";
        return true;
      }
    } else {
      $result = $this->client->callMethodSynchronous('phriction.create', $apiParameters);
      return $result != null && $result['status'];
    }
  }

  public function getIdImage(PhrictionImage $image) {
    $api_parameters = array(
      'queryKey' => 'all',
      'constraints' => array(
        'name' => ScriptingUtils::removeAccents($image->getTitle()),
      ),
    );

    try {
      $result = $this->client->callMethodSynchronous('file.search', $api_parameters);
    } catch (Exception $ex) {
      echo 'ERROR : '.$ex->getCode().' - '.$ex->getMessage().'\n';
      $result = null;
    }

    $idImage = null;
    if ($result !== null && count($result['data']) > 0) {
      $idImage = $this->getPhrictionId($image->getTitle(), $result);
    }

    if ($idImage === null) {
      $imagedata = file_get_contents($image->getUrl());
      $base64 = base64_encode($imagedata);

      $api_parameters = array(
        'data_base64' => $base64,
        'name' => ScriptingUtils::removeAccents($image->getTitle()),
      );

      $result = $this->client->callMethodSynchronous('file.upload', $api_parameters);
      if ($result !== null && strpos($result, "PHID-FILE") !== false) {
        echo tsprintf(" * * %s\n", pht('Image %s imported.', $image->getTitle()));

        $api_parameters = array(
          'queryKey' => 'all',
          'constraints' => array(
            'phids' => array(
              $result,
            ),
          ),
        );

        $result = $this->client->callMethodSynchronous('file.search', $api_parameters);
        if ($result !== null && count($result['data']) > 0) {
          $idImage = $this->getPhrictionId($image->getTitle(), $result);
        }
      }
    }
    $image->setPrhictionId($idImage);
    return $image;
  }

  /**
   * @param PhrictionImage $imageName
   * @param                $result
   * @return string|null
   */
  private function getPhrictionId(string $imageName, $result): ?string {
    foreach ($result['data'] as $data) {
      if ($data['fields']['name'] === ScriptingUtils::removeAccents($imageName)) {
        return $data['id'];
      }
    }
    return null;
  }
}
