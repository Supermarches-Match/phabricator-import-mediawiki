<?php

class ConverterService {
  const REPLACE_STANDARD_TAG_REGEX = array(
    // replace bolded or italicized headers with regular headers
    // and make sure there are empty lines around headers
    '/\n+(=+)\'*([^\'=]+)\'*(=+)\n+/' => "\n\n\$1\$2\$3\n\n",

    // bullet space
    '/^[[:blank:]]*([*#]{1})[[:blank:]]*/' => "\$1 ",

    // use two spaces for pre-formatted block, and make sure there is a blank line before
    '/\n+ +/' => "\n\n  ",

    // replace <pre> with ``` and ensure a blank line
    '#\n*<pre>([\w\W]+?)</pre>\n*#' => "\n\n```\$1```\n\n",

    // replace <blockquote> with > and ensure a blank line
    '#\n*<blockquote>([\w\W]+?)</blockquote>\n*#' => "\n> $1\n\n",

    // replace <source> with > and ensure a blank line
    '#\n*<source(?:\s*)(?:(lang=)"(\w*)")?>(?:\n)([\w\W]+?)</source>\n*#' => "\n```\n$1$2\n$3\n```\n",

    // use [[...]] instead of [...] for external links
    '/([^[])\[(http[^ ]+) ([^\]]+)\]/' => '$1[[$2|$3]]',

    // bold
    "/'''+/" => '**',

    // new lines need not be forced
    '#<br\s*/?>#' => "\n",

    //Divider
    '/(\n*----\n*)/' => "\n\n---\n\n",

    //source
    '/\n*<source(?:\s*)(?:(lang=)"(\w*)")?>(?:\n)([\w\W]+?)<\/source>\n*/' => "\n```\n$1$2\n$3\n```\n"
  );

  const EXTRACT_TABLE_REGEX = '#^\{\|(.*?)(?:^\|\+(.*?))?(^(?:((?R))|.)*?)^\|}#msi';
  const EXTRACT_TABLE_CONTENT_REGEX = '#(?:^([|!])-|\G)(.*?)^(.+?)(?=^[|!]-|\z)#msi';
  const EXTRACT_LINE_CONTENT_REGEX = '#((?:^\||^!|\|\||!!|\G))(?:([^|\n]*?)\|(?!\|))?(?:\n*)(.+?)(?:\n*)(?=^\||^!|\|\||!!|\z)#msi';
  const EXTRACT_CELL_ATTRIBUTE_REGEX = '/(?:((colspan|rowspan|color|bgcolor)="?(#?\w+)")+)/';
  const EXTRACT_CATEGORY_REGEX = '/\[\[category:([^\]]+)\]\]/i';
  const EXTRACT_IMAGES_REGEX = '/\[\[Image:([^\]|]+)[|]?(?:[^\]]*)\]\]/';


  /**
   * ConverterService constructor.
   */
  public function __construct() {
  }

  /**
   * @param PhrictionPage $phriction
   * @param array         $categories
   * @return mixed
   */
  public function convertMediaWikiContentToPhriction(PhrictionPage $phriction, array $categories) {
    //Standard tag
    $pageContent = preg_replace(array_keys(self::REPLACE_STANDARD_TAG_REGEX), self::REPLACE_STANDARD_TAG_REGEX, $phriction->getContent());
    // italics
    $pageContent = str_replace("''", '//', $pageContent);
    // tables
    $pageContent = preg_replace_callback(self::EXTRACT_TABLE_REGEX, 'ConverterService::processTables', $pageContent);

    $phriction->setContent($pageContent);

    if (preg_match_all(self::EXTRACT_CATEGORY_REGEX, $pageContent, $catMatch)) {
      foreach ($catMatch[1] as $cat) {
        if (in_array($cat, $categories)) {
          $phriction->addCategory($cat);
        }
      }
      $phriction->setPrefix(strtolower($phriction->getCategories()[0])."/");
    }

    return $phriction;
  }

  /**
   * @param $matches
   * @return string
   */
  static function processTables(&$matches) {
    return "\n<table>\n"
      .preg_replace_callback(self::EXTRACT_TABLE_CONTENT_REGEX, 'ConverterService::processRows', $matches[3])
      ."</table>\n";
  }

  static function processRows(&$matches) {
    if (trim($matches[3]) == '|-') {
      return '';
    }

    $sub = preg_replace_callback(self::EXTRACT_LINE_CONTENT_REGEX, 'ConverterService::processCells', $matches[3]);

    if ($matches[3][0] == '!') {
      $sub = str_replace('td>', 'th>', $sub);
    }
    return "<tr>$sub</tr>\n";
  }

  static function processCells(&$matches) {
    $attrs = array();
    preg_match_all(self::EXTRACT_CELL_ATTRIBUTE_REGEX, $matches[2], $attrs);

    $i = 0;
    $attributes = "";
    foreach ($attrs[2] as $attr) {
      if ($attr === 'colspan' || $attr === 'rowspan' || $attr === 'color' || $attr === 'bgcolor') {
        $attributes = $attributes." ".$attr."=".$attrs[3][$i];
      }
      $i++;
    }

    return '<td'.$attributes.'>'.trim($matches[3]).'</td>';
  }
}
