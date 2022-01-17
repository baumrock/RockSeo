![img](logo.svg)

![img](https://i.imgur.com/XYCgj4e.gif)

# Todos

- add $seo->findDescription()

# Why another SEO module?

- Show preview with current settings (not "inherit")

# Usage

Zero-configuration usage with default settings:

```php
echo $rockseo->render();
```

Simple configuration:

```php
echo $rockseo
  ->title("{$page->title} | My Company")
  ->render();
```

Advanced usage using callbacks (no limits):

```php
echo $rockseo
  ->title(function($page, $seo) {
    if($page->template == 'home') return "Welcome to RockSeo!";
    if($page->template == 'basic-page') return "RockSeo page ".$page->title;
    return $page->title." | RockSeo";
  })
  ->image(function($page, $seo) {
    $img = $seo->findImage($page);
    if($page->template == 'car') return $page->car_front_view ?: $img;
    if($page->template == 'person') return $page->portrait_image ?: $img;
    return $img;
  })
  ->render();
```

[![img](donate.svg)](https://paypal.me/baumrock)

😎🤗👍

![img](hr.svg)

<br>

# Setup

Simply install the module and `render()` the output in your main markup file:

```html
<?php namespace ProcessWire;
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
  /** @var RockSeo $rockseo */
  echo $rockseo->render();
  ?>
</head>
<body>

</body>
</html>
```

If you have a properly set up IDE you will get instant help while typing (dont forget to add the ProcessWire namespace on top of your file) like shown in the intro gif of this module.

<br>

![img](hr.svg)

<br>

# Customizations

I put a lot of effort into making customizations as easy as possible but to get great flexibility at the same time.

You can try all examples easily by using the tracy console by wrapping the output in a `db()` call.

You can see the default markup tags by dumping `$rockseo` to the console:

```php
db($rockseo);
```
![img](https://i.imgur.com/sqt2z0J.png)

You can easily customize any of those tags in several ways:

## addTag()

```php
$rockseo
  ->addTag('<meta name="google-site-verification" content="google-1234">')
  ->render();
```

## setValue()

To set the replacement for the `{value}` tag use `setValue()`:

```php
$rockseo
  ->setValue('og:description', 'This is my website!')
  ->render();
```

## markup() or setMarkup()

This will set the whole markup for a given key or add a new tag:

```php
$rockseo
  ->markup('og:description', "<foo>{value}</foo>")
  ->render();
```




## title()

This will set both `<title>` and the `og:title` tags!

Simple

```php
$rockseo
  ->title("{$page->title} | My Company")
  ->render();
```

Advanced

```php
$rockseo
  ->title(function($page, $seo) {
    if($page->template == 'home') return "Welcome to RockSeo!";
    if($page->template == 'basic-page') return "RockSeo page ".$page->title;
    return $page->title." | RockSeo";
  })
  ->render();
```

If you want different text for both tags simply use the `setValue()` or `callback()` method:

```php
$rockseo
  ->setValue('title', "My great website")
  ->callback('og:title', function($page) {
    $out = "Hi there! ";
    if($page->template == 'home') $out .= "You are on the home page";
    else $out .= "This is my great website";
    return $out;
  })
  ->render();
```


## description()

This will set both `meta description` and the `og:description` tags!

```php
$rockseo
  ->description($page->body)
  ->render();
```

Advanced

```php
$rockseo
  ->title(function($page, $seo) {
    if($page->template == 'home') return "Welcome to RockSeo!";
    if($page->template == 'basic-page') return "RockSeo page ".$page->title;
    return $page->title." | RockSeo";
  })
  ->render();
```

Wait! You might think: Wouldn't that echo the whole body copy of my page including all html tags that I use in my CKEditor field?

No! RockSeo is smart und will automatically strip all tags and truncate your text to 200 characters maximum.


## generator()

By default rockseo will render a tag that shows that your site is powered by ProcessWire. You can set a custom value like this:

```php
$rockseo
  ->generator("My App")
  ->render();
```

Hide the tag by setting it to false:

```php
$rockseo
  ->generator(false)
  ->render();
```


## alternate

If you have a multilanguage site RockSeo will automatically render all available language alternates for you. The output might look something like this:

```
<link rel="canonical" href="https://your.site/rockseo-beispiel/">
<link rel="alternate" href="https://your.site/rockseo-beispiel/" hreflang="x-default">
<link rel="alternate" href="https://your.site/rockseo-beispiel/" hreflang="default">
<link rel="alternate" href="https://your.site/en/rockseo-example/" hreflang="en">
```

Note that the german language shows `default` for `hreflang` which should be `de`. You need to map your language names to the corresponding two letter code:

```php
$rockseo
  ->setHrefLang("default", "de")
  ->render();
```

## og:image

If you are like me and you always have to lookup the exact settings and dimensions for `og:image` tags you'll really like this one. Simply return any `PageImage` object in the callback and RockSeo will do everything else for you including the creation of all the related tags like `og:image:width` etc.

```php
$rockseo
  ->image(function(Page $page, RockSeo $seo) {
    // find the first available image on the current page and save it for later;
    // $seo is a reference to the RockSeo module that has several handy helpers
    $img = $seo->findImage($page);

    // if we are on a car page we return the front view image
    // if that image does not exist we return the image we found above
    if($page->template == 'car') return $page->car_front_view ?: $img;

    // for all other pages we return the first image we can find
    return $img;
  })
  ->render();
```
