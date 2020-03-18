<?php

class AbstractPhrictionPage extends Phobject {
  protected $title;
  private $safeTitle;
  protected $origin;
  private $content;
  private $url;

  /**
   * PhrictionCategory constructor.
   * @param string $title
   * @param string $content
   * @param string $wikiUrl
   */
  public function __construct(string $title, string $content, string $wikiUrl) {
    $this->title = $title;
    $this->content = $content;
    $this->safeTitle = str_replace(' ', '_', $this->title);
    $this->origin = $wikiUrl."index.php/".$this->safeTitle;
    $this->url = ScriptUtils::formatUrl($this->title);
    if(mb_strtolower($title) === "accueil"){
      $this->url = "";
    }
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
  public function getContent() {
    return $this->content;
  }

  /**
   * @param mixed $content
   */
  public function setContent($content): void {
    $this->content = $content;
  }

  /**
   * @return string
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl(string $url): void {
    $this->url = $url;
  }

  /**
   * @return string|string[]
   */
  public function getOrigin() {
    return $this->origin;
  }

  /**
   * @param string|string[] $origin
   */
  public function setOrigin($origin): void {
    $this->origin = $origin;
  }

  /**
   * @return string|string[]
   */
  public function getSafeTitle() {
    return $this->safeTitle;
  }

  /**
   * @param string|string[] $safeTitle
   */
  public function setSafeTitle($safeTitle): void {
    $this->safeTitle = $safeTitle;
  }

}
