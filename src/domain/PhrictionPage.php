<?php

class PhrictionPage extends AbstractPhrictionPage {
  private $categories;
  private $prefix;
  private $images;

  /**
   * PhrictionPage constructor.
   * @param string $title
   * @param string $content
   * @param string $wikiUrl
   */
  public function __construct(string $title, string $content, string $wikiUrl) {
    parent::__construct($title, $content, $wikiUrl);
    $this->categories = array();
    $this->images = null;
    $this->prefix = "pages/";
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
   * @return string
   */
  public function getUrl(): string {
    return $this->prefix.parent::getUrl();
  }

  /**
   * @return mixed
   */
  public function getImages() {
    return $this->images;
  }

  /**
   * @param mixed $images
   */
  public function setImages($images): void {
    $this->images = $images;
  }

  /**
   * @return string
   */
  public function getPrefix(): string {
    return $this->prefix;
  }

  /**
   * @param string $prefix
   */
  public function setPrefix(string $prefix): void {
    $this->prefix = $prefix;
  }
}
