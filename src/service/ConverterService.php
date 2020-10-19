<?php

class ConverterService extends Phobject {
  const REPLACE_STANDARD_TAG_REGEX = array(
    //Remove html tag not use in phriction
    '#(?:<center>)+([\w\W]+?)(?:</center>)+#' => "\$1",
    '#(?:<font(?:[^>])*>)+((?:[^>])*)(?:</font>)+#' => "\$1",
    '#(?:<div(?:[^>])*>)+((?:[^>])*)(?:</div>)+#' => "\$1",

    //Remove <br> in header
    '/\n*(=+)([^\=<>]+)(<br>|<br\s*\/>)+(\s+)(=+)\n+/' => "\n\n\$1\$2\$4\$5\n",

    // replace bolded or italicized headers with regular headers
    // and make sure there are empty lines around headers
    '/\n+(=+)\'*([^\'=]+)\'*(=+)\n+/' => "\n\n\$1\$2\$3\n\n",

    // bullet space
    '/^[[:blank:]]*([*#]+)[[:blank:]]*([^#*<>]+)((<br>|<br\s*\/>)*)/m' => "\$1 \$2",

    // use two spaces for pre-formatted block, and make sure there is a blank line before
    '/\n+ +/' => "\n\n  ",

    // replace <pre> with ``` and ensure a blank line
    '#\n*<pre>([\w\W]+?)</pre>\n*#' => "\n\n```\$1```\n\n",

    // replace <blockquote> with > and ensure a blank line
    '#\n*<blockquote>([\w\W]+?)</blockquote>\n*#' => "\n> $1\n\n",

    // replace <source> with > and ensure a blank line
    '#\n*<source(?:\s*)(?:(lang=)"(\w*)")?>(?:\n*)([\w\W]+?)</source>\n*#' => "\n```\n$1$2\n$3\n```\n",

    // use [[...]] instead of [...] for external links
    '/([^[])\[(http[^ ]+) ([^\]]+)\]/' => '$1[[$2|$3]]',

    // bold
    "/'''+/" => '**',

    // underline
    '#<u>([\w\W]+?)</u>#' => "__\$1__",

    // strike
    '#<strike>([\w\W]+?)</strike>#' => "~~\$1~~",

    // new lines need not be forced
    '#<br\s*/?>#' => "\n",

    //Divider
    '/^(\n*)([-]{4}){1}\n*/m' => "\n\n---\n\n",

    //source
    '/\n*<source(?:\s*)(?:(lang=)"(\w*)")?>(?:\n)([\w\W]+?)<\/source>\n*/' => "\n```\n$1$2\n$3\n```\n",

    //Category
    '/(\[\[category:(([^|\]])+)(?:([^\]])*)\]\])(\s*)(\n*)/i' => "[[catégories/$2|Catégorie $2]]\n",

    //=> to ->
    '/(=(&gt;|>))/' => "->",

    //Acteur spécifique
    '/(?:\n*)(?:{{Acteur_)((?:[^{])+)}}/i' => "[[pages/$1|$1]]",

    //Removing unicode
    '/(\x{200e}|\x{200f})/u' => "",

    //Remove cartouche
    '/(?:\n*)(?:{{Cartouche(?:[^{])+)}}/i' => "",
    '/(?:\n*)(?:{{Doc(?:[^{])+)}}/i' => "",
    '/(?:\n*)(?:{{tdm(?:[^{])+)}}/i' => "",
    '/\n*__NOTOC__(\n|\s)+/i' => "",
  );

  const EXTRACT_TABLE_REGEX = '#^\{\|(.*?)(?:^\|\+(.*?))?(^(?:((?R))|.)*?)^\|}#msi';
  const EXTRACT_TABLE_CONTENT_REGEX = '#(?:^([|!])-|\G)(.*?)^(.+?)(?=^[|!]-|\z)#msi';
  const EXTRACT_LINE_CONTENT_REGEX = '#((?:^\||^!|\|\||!!|\G))(?:([^|\n]*?)\|(?!\|))?(?:\n*)(.+?)(?:\n*)(?=^\||^!|\|\||!!|\z)#msi';
  const EXTRACT_CELL_ATTRIBUTE_REGEX = '/(?:((colspan=|rowspan=|bgcolor=|background-color:|color[:=])"?(#?\w+)"?)+)/';
  const EXTRACT_CATEGORY_REGEX = '/\[\[category:catégories\/([^\]]+)\]\]/i';
  const EXTRACT_IMAGES_REGEX = '/(?:\[\[Image:|File:|Media:|Fichier:)([^\]|]+)((?:[|][^\]|]*)*[\]]{2})/';
  const EXTRACT_LINK_INTERNAL = '/\[\[((?!Category:|Image:|File:|Images:|Files:|Fichier:|http:|https:|Media:|catégories\/)(?:[^\]]+))\]\]/i';


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

    if (preg_match_all(self::EXTRACT_CATEGORY_REGEX, $pageContent, $catMatch)) {
      if ($catMatch !== null && count($catMatch) > 0) {
        foreach ($catMatch[1] as $cat) {
          if (in_array($cat, $categories)) {
            $phriction->addCategory($cat);
          }
        }
      }
    }

    $linkMatch = array();
    $i = 0;
    if (preg_match_all(self::EXTRACT_LINK_INTERNAL, $pageContent, $linkMatch)) {
      if ($linkMatch !== null && count($linkMatch) > 0) {
        foreach ($linkMatch[1] as $link) {
          $split = preg_split('/[|]/', $link);

          if (strpos(mb_strtolower($link), ':catégorie:') === false) {
            if (count($split) > 1) {
              if (strpos(mb_strtolower($split[0]), 'pages/') !== false) {
                $newLink = "[[".mb_strtolower($split[0])."|".$split[1]."]]";
              }else {
                $newLink = "[[pages/".ScriptingUtils::formatUrl($split[0])."|".$split[1]."]]";
              }
            } else {
              $newLink = "[[pages/".ScriptingUtils::formatUrl($link)."|".$link."]]";
            }
          } else {
            if (count($split) > 1) {
              $link = preg_replace("/:catégorie:/i", "", $split[0]);
              $newLink = "[[categories/".ScriptingUtils::formatUrl($link)."|".$split[1]."]]";
            } else {
              $link = preg_replace("/:catégorie:/i", "", $link);
              $newLink = "[[categories/".ScriptingUtils::formatUrl($link)."|".$link."]]";
            }
          }
          $pageContent = str_replace($linkMatch[0][$i], $newLink, $pageContent);

          $i++;
        }
      }
    }

    $imagesMatch = array();
    $j = 0;
    if (preg_match_all(self::EXTRACT_IMAGES_REGEX, $pageContent, $imagesMatch)) {
      if ($imagesMatch !== null && count($imagesMatch) > 0) {
        foreach ($imagesMatch[1] as $image) {
          foreach ($phriction->getImages() as $phImage) {

            if ($phImage->getTitle() === $image || str_replace(" ", "_", strtolower ($phImage->getTitle())) === str_replace(" ", "_", strtolower (strtolower($image)))) {
              $replaceValue = $phImage->getPrhictionId();
              $size = array();
              if (preg_match_all('/[\|](\d*)px/', $imagesMatch[2][$j], $size)) {
                $replaceValue = $replaceValue.', width='.$size[1][0];
              }

              if (strpos($imagesMatch[0][$j], "[[") !== false) {
                $pageContent = str_replace($imagesMatch[0][$j], "{F".$replaceValue."}", $pageContent);
              } else {
                $pageContent = str_replace("[[".$imagesMatch[0][$j], "{F".$replaceValue."}", $pageContent);
              }
              break;
            }
          }
          $j++;
        }
      }
    }

    $pageContent = html_entity_decode($pageContent, ENT_NOQUOTES);

    $phriction->setContent($pageContent);

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

    return "<tr>$sub</tr>\n";
  }

  static function processCells(&$matches) {
    $attrs = array();
    preg_match_all(self::EXTRACT_CELL_ATTRIBUTE_REGEX, $matches[2], $attrs);

    $i = 0;
    $attributes = "";
    foreach ($attrs[2] as $attr) {
      $attr = rtrim($attr, "=:");
      if ($attr === 'colspan' || $attr === 'rowspan' || $attr === 'color' || $attr === 'bgcolor') {
        $attributes = $attributes." ".$attr."=".$attrs[3][$i];
      } else if ($attr === 'background-color') {
        $attributes = $attributes." bgcolor=".$attrs[3][$i];
      }
      $i++;
    }

    $cellType = 'td';
    if(strpos($matches[1], '!') !== false){
      $cellType = 'th';
    }

    if($attributes === '' && strpos($matches[3], '[[') === false && strpos($matches[3], ']]') !== false){
      return '<'.$cellType.'>'.trim($matches[2].'|'.$matches[3]).'</'.$cellType.'>';
    }
    return '<'.$cellType.$attributes.'>'.trim($matches[3]).'</'.$cellType.'>';
  }
}
