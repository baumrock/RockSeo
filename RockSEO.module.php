<?php namespace ProcessWire;
/**
 * @author Bernhard Baumrock, 12.01.2022
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class RockSEO extends WireData implements Module {

  /** @var WireData */
  private $callbacks;

  public $indent = '  ';
  public $indentFirst = '';

  /** @var WireData */
  private $markup;

  /** @var Page */
  private $page;

  public static function getModuleInfo() {
    return [
      'title' => 'RockSEO',
      'version' => '0.0.1',
      'summary' => 'Module to boost your sites SEO performance',
      'autoload' => true,
      'singular' => true,
      'icon' => 'search',
      'requires' => [],
      'installs' => [],
    ];
  }

  public function init() {
    $this->wire('rockseo', $this);
    $this->setDefaults();
  }

  /* ##### chainable api ##### */

    /**
     * Set callback
     *
     * @param string $name
     * @param callable $callback
     * @return self
     */
    public function setCallback(string $name, callable $callback) {
      $this->callbacks->set($name, $callback);
      return $this;
    }

    /**
     * Set markup
     * @return self
     */
    public function setMarkup(string $name, string $markup) {
      $this->markup->set($name, $markup);
      return $this;
    }

    /**
     * @return self
     */
    public function setPage($page) {
      $this->page = $page;
      return $this;
    }

  /* ##### END chainable api ##### */

  /**
   * Set default tags
   */
  public function setDefaults() {
    $this->page = $this->setPage($this->wire->page);
    $this->markup = $this->wire(new WireData);
    $this->callbacks = $this->wire(new WireData);
    $this
      ->setMarkup('brand', '<!-- RockSEO by baumrock.com -->')

      ->setMarkup('title', '<title>{value}</title>')
      ->setCallback('title', function($page) { return $page->title; })

      ->setMarkup('description', '<meta name="description" content="{value}">')
      ->setCallback('description', function($page, $seo) {
        return $seo->texttools()->truncate($page->body);
      })

      ->setMarkup('generator', '<meta name="generator" content="ProcessWire">')

      ->setMarkup('og:title', '<meta property="og:title" content="{value}">')
      ->setCallback('og:title', function($page) { return $page->title; })

      ->setMarkup('og:description', '<meta property="og:description" content="{value}">')
      // ->setCallback('og:description', function($page) { return $page->body; })

      ->setMarkup('og:image', '<meta property="og:image" content="{value}">')
      ->setCallback('og:image', function($page) {
        // by default og:image will be populated by the first image on the page
        foreach($page->fields as $field) {
          if($field->type instanceof FieldtypeImage) {
            return $page->getUnformatted($field->name);
          }
        }
      })
      ->setMarkup('og:image:type', '<meta property="og:image:type" content="{value}">')
      ->setCallback('og:image:type', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
        $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        if($ext == 'jpg' OR $ext == 'jpeg') return "image/jpg";
        if($ext == 'png') return "image/png";
      })
      ->setMarkup('og:image:width', '<meta property="og:image:width" content="{value}">')
      ->setCallback('og:image:width', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
      })
      ->setMarkup('og:image:height', '<meta property="og:image:height" content="{value}">')
      ->setCallback('og:image:height', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
      })
      ->setMarkup('og:image:alt', '<meta property="og:image:alt" content="{value}">')
      ->setCallback('og:image:alt', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
      })

      ->setMarkup('og:type', '<meta property="og:type" content="website">')

      ->setMarkup('og:url', '<meta property="og:url" content="{value}">')
      ->setCallback('og:url', function($page) { return $page->httpUrl; })

      // <meta property="og:locale" content="en_EN">
      // <meta name="twitter:card" content="summary">
      // <meta name="twitter:creator" content="@schtifu">
      // <meta name="twitter:site" content="@schtifu">
      // <script type="application/ld+json">
      // {
      //   "@context": "https://schema.org",
      //   "@type": "BreadcrumbList",
      //   "itemListElement": [
      //   {
      //     "@type": "ListItem",
      //     "position": 1,
      //     "name": "About",
      //     "item": "https://acme.com/en/about/"
      //   }
      //   ]
      // }
      // </script>

      ->setMarkup('canonical', '<link rel="canonical" href="{value}">')
      // <link rel="alternate" href="https://acme.com/en/about/" hreflang="en">
      // <link rel="alternate" href="https://acme.com/en/about/" hreflang="x-default">
      // <link rel="alternate" href="https://acme.com/de/ueber/" hreflang="de">
      // <link rel="alternate" href="https://acme.com/fi/tietoja/" hreflang="fi">
      // <meta name="google-site-verification" content="google-1234">
      // <meta name="msvalidate.01" content="bing-1234">
    ;
  }

  /**
   * Render seo tags
   */
  public function render() {
    $out = '';
    $i = 0;
    foreach($this->markup as $str) {
      $out .= $i++ ? $this->indent : $this->indentFirst;
      $out .= "$str\n";
    }
    return $out;
  }

  public function __debugInfo() {
    return [
      'markup' => $this->markup,
    ];
  }

}
