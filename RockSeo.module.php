<?php namespace ProcessWire;
/**
 * @author Bernhard Baumrock, 12.01.2022
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class RockSeo extends WireData implements Module {

  /** @var WireData */
  private $callbacks;

  /** @var WireData */
  private $hrefLang;

  /** @var WireData */
  private $markup;

  /** @var WireData */
  private $opt;

  /** @var Page */
  private $page;

  public static function getModuleInfo() {
    return [
      'title' => 'RockSeo',
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
    $this->addHookBefore("renderTag", $this, "renderAlternate");
  }

  /* ##### chainable api ##### */

    /**
     * Add a static tag
     * @return self
     */
    public function addTag($markup) {
      $this->setMarkup(uniqid(), $markup);
      return $this;
    }

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
     * Set the hreflang value for given language
     * @return self
     */
    public function setHrefLang($langName, $hrefLang) {
      $this->hrefLang->$langName = $hrefLang;
      return $this;
    }

    /**
     * Set markup
     * @param string $name
     * @param string|array markup
     * @return self
     */
    public function setMarkup(string $name, $markup) {
      $this->markup->set($name, $markup);
      return $this;
    }

    /**
     * Set option
     * @return self
     */
    public function setOpt($key, $value) {
      $this->opt->set($key, $value);
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
   * Find the first image on that page
   * @return mixed
   */
  public function findImage($page) {
    foreach($page->fields as $field) {
      if(!$field->type instanceof FieldtypeImage) continue;
      return $page->getUnformatted($field->name);
    }
  }

  /**
   * Get return value of callback for given page
   * @return mixed
   */
  public function getReturn($key, $page) {
    $callback = $this->callbacks->$key;
    return $callback->__invoke($page, $this);
  }

  /**
   * Get string value
   * @return string
   */
  public function ___getStringValue($value, $tag, $key) {
    if($value instanceof Pageimages) $value = $value->first();
    if($value instanceof Pageimage) {
      return $value
        ->size(
          $this->opt->ogImageWidth,
          $this->opt->ogImageHeight,
          $this->opt->ogImageOptions
        )
        ->httpUrl;
    }
    return (string)$value;
  }

  /**
   * Set default tags
   */
  public function setDefaults() {
    $this->markup = $this->wire(new WireData);
    $this->callbacks = $this->wire(new WireData);
    $this->hrefLang = $this->wire(new WireData);

    $this->opt = $this->wire(new WireData);
    $this->opt->nl = "\n";
    $this->opt->indent = '  ';
    $this->opt->indentFirst = '';
    $this->opt->ogImageWidth = 1200;
    $this->opt->ogImageHeight = 630;
    $this->opt->ogImageOptions = [
      'upscaling' => true,
    ];

    $this
      ->setMarkup('brand', '<!-- RockSeo by baumrock.com -->')

      ->setMarkup('title', '<title>{value}</title>')
      ->setCallback('title', function($page) { return $page->title; })

      ->setMarkup('description', '<meta name="description" content="{value}">')
      ->setCallback('description', function($page, $seo) {
        return $seo->texttools()->truncate($page->body, 200);
      })

      ->setMarkup('generator', '<meta name="generator" content="ProcessWire">')

      ->setMarkup('og:title', '<meta property="og:title" content="{value}">')
      ->setCallback('og:title', function($page) { return $page->title; })

      ->setMarkup('og:description', '<meta property="og:description" content="{value}">')
      ->setCallback('og:description', function($page, RockSeo $seo) {
        return $seo->texttools()->truncate($page->body, 200);
      })

      ->setMarkup('og:image', '<meta property="og:image" content="{value}">')
      ->setCallback('og:image', function(Page $page, RockSeo $seo) {
        return $seo->findImage($page);
      })
      ->setMarkup('og:image:type', '<meta property="og:image:type" content="{value}">')
      ->setCallback('og:image:type', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
        if(!$img) return false; // dont render this tag
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
      ->setCallback('canonical', function($page) {
        return $page->httpUrl;
      })

      ->setMarkup('alternate', '<link rel="alternate" href="{href}" hreflang="{lang}">')
      ->setCallback('alternate', function($page) {
        return [
          'href' => 'my-url.html',
          'lang' => 'de',
        ];
      });


      // <meta name="msvalidate.01" content="bing-1234">
    ;
  }

  /**
   * Render seo tags
   */
  public function render() {
    // if no page is set we set it now
    if(!$this->page) $this->setPage($this->wire->page);

    // generate tags
    $out = '';
    $i = 0;
    foreach($this->markup as $key=>$str) {
      $out .= $this->renderTag($key, $i++);
    }
    return $out;
  }

  /**
   * Output all alternate tags on multilang sites
   * @return void
   */
  public function renderAlternate(HookEvent $event) {
    $key = $event->arguments(0);
    if($key !== 'alternate') return;

    // for alternate tag we replace the original renderTag output
    $event->replace = true;

    // loop all languages and return tag for each language

  }

  /**
   * Hookable method to render a single tag
   * @return string
   */
  public function ___renderTag($key, $index) {
    $callback = $this->callbacks->$key;
    $markup = $this->replaceTags($key, $callback);
    if(!$markup) return;
    $indent = $index ? $this->opt->indent : $this->opt->indentFirst;
    return $indent.$markup.$this->opt->nl;
  }

  /**
   * Replace all tags in markup
   * @return string
   */
  public function replaceTags($key, $callback) {
    $markup = $this->markup->$key;
    if(!$callback) return $markup;

    // get the result of the callback
    $callbackResult = $callback->__invoke($this->page, $this);

    // if the callback returns FALSE we prevent rendering the whole tag
    if($callbackResult === false) return;

    // if the callback returns a single value (not an array)
    // we use the value as replacement for the {value} tag
    $replacements = $callbackResult;
    if(!is_array($callbackResult)) $replacements = ['value'=>$callbackResult];

    foreach($replacements as $tag=>$value) {
      $value = $this->getStringValue($value, $tag, $key);
      $markup = str_replace("{{$tag}}", $value, $markup);
    }
    return $markup;
  }

  /**
   * @return
   */
  public function texttools() {
    return $this->wire->sanitizer->getTextTools();
  }

  public function __debugInfo() {
    return [
      'markup' => $this->markup,
      'opt' => $this->opt,
      'hrefLang' => $this->hrefLang,
    ];
  }

}
