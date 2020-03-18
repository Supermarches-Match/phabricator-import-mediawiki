<?php


class PhrictionImage extends Phobject {
  private $title;
  private $origin;
  private $url;
  private $prhictionId;
  private $content;

  /**
   * PhrictionImage constructor.
   * @param $origin
   * @param $url
   */
  public function __construct($origin, $url) {
    $this->origin = $origin;
    $this->title = str_replace("Fichier:", "", $this->origin);
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title): void {
    $this->title = $title;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url): void {
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getPrhictionId() {
    return $this->prhictionId;
  }

  /**
   * @param mixed $prhictionId
   */
  public function setPrhictionId($prhictionId): void {
    $this->prhictionId = $prhictionId;
  }

  /**
   * @return mixed
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * @param mixed $content
   */
  public function setContent($content): void {
    $this->content = $content;
  }
}