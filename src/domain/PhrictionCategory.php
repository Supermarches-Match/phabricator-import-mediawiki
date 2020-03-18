<?php

class PhrictionCategory extends AbstractPhrictionPage {
  private $name;
  private $pages;

  /**
   * PhrictionCategory constructor.
   * @param string $title
   * @param string $content
   * @param string $wikiUrl
   */
  public function __construct(string $title, string $content, string $wikiUrl) {
    parent::__construct($title, $content, $wikiUrl);
    $this->name = $title;
    $this->origin = $wikiUrl."index.php/Catégorie:".str_replace(' ', '_', $this->title);
    $this->title = "Categorie ".$title;
    $this->pages = array();
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name): void {
    $this->name = $name;
  }

  /**
   * @return array
   */
  public function getPages(): array {
    return $this->pages;
  }

  /**
   * @param array $pages
   */
  public function setPages(array $pages): void {
    $this->pages = $pages;
  }

  /**
   * @return string
   */
  public function getUrl(): string {
    return "Catégories/".parent::getUrl();
  }


}
