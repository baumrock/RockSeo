<?php namespace ProcessWire;
/**
 * @author Bernhard Baumrock, 12.01.2022
 * @license MIT
 * @link https://www.baumrock.com
 *
 * @method string getStringValue($value, $tag, $key)
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
     * Shortcut for setCallback()
     * @param string $name
     * @param mixed $callback
     * @return self
     */
    public function callback(string $name, $callback) {
      return $this->setCallback($name, $callback);
    }

    /**
     * Set canonical value
     *
     * Usage:
     *
     *
     * @return self
     */
    public function canonical($data) {
      if($data === false) {
        $this->markup->set("canonical", null);
      }
      elseif(is_string($data)) {
        $markup = $this->replaceTags("canonical", $data);
        $this->markup("canonical", $markup);
      }
      elseif(is_callable($data)) $this->callback("canonical", $data);
      return $this;
    }

    /**
     * @return self
     */
    public function description($data, $options = []) {
      if(is_string($data)) {
        $str = $this->sanitize($data, $options);

        $markup = $this->replaceTags('description', $str);
        $this->markup('description', $markup);

        $markup = $this->replaceTags('og:description', $str);
        $this->markup('og:description', $markup);
      }
      elseif(is_callable($data)) {
        $this->callback('description', $data);
        $this->callback('og:description', $data);
      }
      return $this;
    }

    /**
     * Set generator
     *
     * Usage:
     * $rockseo->generator(false); // hide tag
     * $rockseo->generator("RockSeo"); // custom value
     *
     * @return self
     */
    public function generator($data) {
      $this->setCallback('generator', $data);
      return $this;
    }

    /**
     * Replace the original output of renderTag() by the callback provided as
     * second parameter.
     * @return self
     */
    public function hookRenderTag($key, $callback) {
      $this->addHookAfter("renderTag($key)", function($event) use($callback) {
        $key = $event->arguments(0);
        $seo = $event->arguments(1);
        $event->return = $callback->__invoke($key, $seo);
      });
      return $this;
    }

    /**
     * Set og:image tag
     * @return self
     */
    public function image($data) {
      $this->setCallback('og:image', $data);
      return $this;
    }

    /**
     * Shortcut for setMarkup()
     * @return self
     */
    public function markup(string $name, $markup) {
      return $this->setMarkup($name, $markup);
    }

    /**
     * Shortcut for setOpt
     */
    public function opt($key, $value) {
      return $this->setOpt($key, $value);
    }

    /**
     * Unset callback for given key
     * @return self
     */
    public function removeCallback($key) {
      $this->callbacks->set($key, null);
      return $this;
    }

    /**
     * Remove markup tag
     * @return self
     */
    public function removeMarkup($key) {
      $this->markup->set($key, null);
      return $this;
    }

    /**
     * Set callback
     *
     * Usage:
     * $rockseo->setCallback('og:image', function($page) { ... });
     *
     * Shortcut for directly populating the {value} tag:
     * $rockseo->setCallback('generator', 'ProcessWire');
     * $rockseo->setCallback('generator', false);
     *
     * Also possible:
     * $rockseo->setCallback('foo', [
     *   'foo' => 'my foo value',
     *   'bar' => 'my bar value',
     * ]);
     *
     * @param string $name
     * @param mixed $callback
     * @return self
     */
    public function setCallback(string $name, $callback) {
      if(is_string($callback)
        OR is_array($callback)
        OR $callback === false) {
        $callback = function() use($callback) { return $callback; };
      }
      $this->callbacks->set($name, $callback);
      return $this;
    }

    /**
     * Set default tags
     * @return self
     */
    public function setDefaults() {
      $this->page = $page = $this->wire->page;
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
        ->setValue('title', $page->title)

        ->setMarkup('description', '<meta name="description" content="{value}">')
        ->setValue('description', $page->body)

        ->setMarkup('generator', '<meta name="generator" content="{value}">')
        ->setValue('generator', 'ProcessWire')

        ->setMarkup('og:title', '<meta property="og:title" content="{value}">')
        ->setValue('og:title', $page->title)

        ->setMarkup('og:description', '<meta property="og:description" content="{value}">')
        ->setValue('og:description', $page->body)

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
          if(!$lang = $this->wire->user->language) return;
          return $seo->hrefLang->get($lang->name);
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
        ->hookRenderTag('alternate', function($key, RockSeo $seo) {
            if(!$langs = $this->wire->languages) return;
            if($langs->count()<2) return;

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
            foreach($this->wire->languages as $lang) {
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

            return $out;
        })

      ;

      return $this;
    }

    /**
     * Set the hreflang value for given language
     * @param string $langName Language name in ProcessWire
     * @param string $hrefLang Language string in the tag, eg DE
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

    /**
     * Set value for the {value} tag
     *
     * This is the same as setCallback() but might be more easy to understand
     * when used with string values:
     * $rockseo->setValue('generator', 'My App');
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setValue($key, $value) {
      $this->setCallback($key, $value);
      return $this;
    }

    /**
     * @return self
     */
    public function title($data) {
      if(is_string($data)) {
        $this->markup('title', $this->replaceTags('title', $data));
        $this->markup('og:title', $this->replaceTags('og:title', $data));
      }
      elseif(is_callable($data)) {
        $this->callback('title', $data);
        $this->callback('og:title', $data);
      }
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
      $images = $page->getUnformatted($field->name);
      foreach($images as $image) {
        $ext = strtolower(pathinfo($image->filename, PATHINFO_EXTENSION));
        if($ext == 'gif') continue;
        return $image;
      }
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
  public function ___getStringValue($value, $tag=null, $key=null) {
    if($value instanceof Pageimages) $value = $value->first();
    if($value instanceof Pageimage) return $this->img($value)->httpUrl;
    return $this->sanitize($value);
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

    // If no tag exists in the markup we return it directly.
    // This ensures that no callback is executed on tags that
    // provide a simple string (eg $rockseo->description('foo'))
    if(!strpos($markup, "{")) return $markup;

    // if we got a string as replacement we make it replace the value tag
    if(is_string($replace)) $replace = ['value' => $replace];

    // bd($replace, $key);

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
   * Sanitize string for output
   *
   * Usage:
   * $seo->sanitize($page->body, 200);
   * $seo->sanitize($page->body, [
   *   'maxLength' => 160,
   *   'truncate' => // options array for texttools truncate()
   *   'markupToText' => // options array for markupToText()
   * ]);
   *
   * @return string
   */
  public function sanitize($str, $options = []) {
    if(is_int($options)) $options = ['maxLength'=>$options];
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'maxLength' => 200,
      'truncate' => [], // options for truncate()
      'markupToText' => [], // options for markupToText()
    ]);
    $opt->setArray($options);

    $texttools = $this->texttools();
    $str = $texttools->markupToText($str, $opt->truncate);
    $str = $texttools->truncate($str, $opt->maxLength, $opt->truncate);
    return $str;
  }

  /**
   * @return WireTextTools
   */
  public function texttools() {
    return $this->wire->sanitizer->getTextTools();
  }

  public function __debugInfo() {
    return [
      'markup' => $this->markup,
      'opt' => $this->opt,
      'hrefLang' => $this->hrefLang,
      'callbacks' => $this->callbacks,
    ];
  }

}
