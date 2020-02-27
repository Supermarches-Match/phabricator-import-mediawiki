<?php

class PhrictionPage extends AbstractPhrictionPage {
  private $categories;
  private $prefix;

  /**
   * PhrictionPage constructor.
   * @param $title
   * @param $content
   */
  public function __construct(string $title, string $content, string $wikiUrl) {
    parent::__construct($title, $content, $wikiUrl);
    $this->categories = array();
    $this->prefix = null;
  }

  /**
   * @param mixed $category
   */
  public function addCategory($category) {
    $this->categories[] = $category;
  }

  /**
   * @return mixed
   */
  public function getCategories() {
    return $this->categories;
  }

  /**
   * @param mixed $categories
   */
  public function setCategories($categories): void {
    $this->categories = $categories;
  }

  /**
   * @return mixed
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * @param mixed $prefix
   */
  public function setPrefix($prefix): void {
    $this->prefix = $prefix;
  }

  /**
   * @return string
   */
  public function getUrl(): string {
    return $this->prefix."/".$this->getUrl();
  }


}
