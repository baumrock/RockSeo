<?php namespace ProcessWire;
/**
 * @author Bernhard Baumrock, 12.01.2022
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class RockSeo extends WireData implements Module {

  /** @var WireData */
  public $callbacks;

  /** @var WireData */
  public $hrefLang;

  /** @var WireData */
  public $markup;

  /** @var WireData */
  public $opt;

  /** @var Page */
  public $page;

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
  }

  public function ready() {
    // set defaults when api is ready
    // necessary for $page->localHttpUrl()
    $this->setDefaults();
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
     * Replace the original output of renderTag() by the callback provided as
     * second parameter.
     * @return self
     */
    public function hookRenderTag($key, $callback) {
      $this->addHookAfter("renderTag($key)", $callback);
      return $this;
    }

    /**
     * Shortcut for setOpt
     */
    public function opt($key, $value) {
      return $this->setOpt($key, $value);
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
   * @param mixed $value
   * @param string $tag for hooks (default is "value")
   * @param string $key for hooks (eg "og:image")
   * @return string
   */
  public function ___getStringValue($value, $tag, $key) {
    if($value instanceof Pageimages) $value = $value->first();
    if($value instanceof Pageimage) return $this->img($value)->httpUrl;
    return (string)$value;
  }

  /**
   * Return single Pageimage or WireData
   * @return mixed
   */
  public function img($data) {
    if($data instanceof Pageimages) $data = $data->first();
    if($data instanceof Pageimage) {
      return $data->size(
        $this->opt->ogImageWidth,
        $this->opt->ogImageHeight,
        $this->opt->ogImageOptions,
      );
    }
    return $this->wire(new WireData());
  }

  /**
   * Get markup value
   * @return string
   */
  public function markup($key) {
    return $this->markup->get($key);
  }

  /**
   * Set default tags
   */
  public function setDefaults() {
    $this->page = $this->wire->page;
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
    $this->opt->renderAlternateDefault = true;

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
        if(!$img) return;
        $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        if($ext == 'jpg' OR $ext == 'jpeg') return "image/jpg";
        if($ext == 'png') return "image/png";
      })
      ->setMarkup('og:image:width', '<meta property="og:image:width" content="{value}">')
      ->setCallback('og:image:width', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
        return $seo->img($img)->width;
      })
      ->setMarkup('og:image:height', '<meta property="og:image:height" content="{value}">')
      ->setCallback('og:image:height', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
        return $seo->img($img)->height;
      })
      ->setMarkup('og:image:alt', '<meta property="og:image:alt" content="{value}">')
      ->setCallback('og:image:alt', function($page, $seo) {
        $img = $seo->getReturn('og:image', $page);
        return $seo->img($img)->description;
      })

      ->setMarkup('og:type', '<meta property="og:type" content="website">')

      ->setMarkup('og:url', '<meta property="og:url" content="{value}">')
      ->setCallback('og:url', function($page) { return $page->httpUrl; })

      ->setMarkup('og:locale', '<meta property="og:locale" content="{value}">')
      ->setCallback('og:locale', function($page, RockSeo $seo) {
        return $seo->hrefLang->get($this->wire->user->language->name);
      })

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
      ->hookRenderTag('alternate', function(HookEvent $event) {
        if(!$langs = $event->wire->languages) return;
        $key = $event->arguments(0);
        /** @var RockSeo $seo */
        $seo = $event->arguments(1);
        $out = '';
        $indent = '';

        // add default language
        $userlang = $this->wire->user->language;
        if($seo->opt->renderAlternateDefault) {
          $this->wire->user->language = $langs->getDefault();
          $out .= $indent.
            $seo->replaceTags($key, [
              'href' => $seo->page->httpUrl(),
              'lang' => 'x-default',
            ])
            .$seo->opt->nl;
            $indent = $seo->opt->indent;
        }

        // add other languages
        foreach($event->wire->languages as $lang) {
          $this->wire->user->language = $lang;
          $out .= $indent
            .$seo->replaceTags($key, [
              'href' => $seo->page->httpUrl(),
              'lang' => $seo->hrefLang->get($lang->name) ?: $lang->name,
            ])
            .$seo->opt->nl;
          $indent = $seo->opt->indent;
        }
        $this->wire->user->language = $userlang;

        $event->return = $out;
      })

    ;
  }

  /**
   * Render seo tags
   */
  public function render() {
    // generate tags
    $out = '';
    $i = 0;
    foreach($this->markup as $key=>$str) {
      if(!$markup = $this->renderTag($key, $this)) continue;
      $indent = $i++ ? $this->opt->indent : $this->opt->indentFirst;
      $out .= $indent.$markup.$this->opt->nl;
    }
    return $out;
  }

  /**
   * Hookable method to render a single tag
   * @return string
   */
  public function ___renderTag($key, $seo) {
    $callback = $this->callbacks->$key;
    return $this->replaceTags($key, $callback);
  }

  /**
   * Replace all tags in markup
   * @param string $key
   * @param array|callable $replace
   * @return string
   */
  public function replaceTags($key, $replace) {
    $markup = $this->markup->$key;
    if(!$replace) return $markup;

    $replacements = is_array($replace)
      ? $replace
      : $replace->__invoke($this->page, $this);

    // no callback value --> no tag
    if(!$replacements) return;

    // if the callback returns a single value (not an array)
    // we use the value as replacement for the {value} tag
    if(!is_array($replacements)) $replacements = ['value'=>$replacements];

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
