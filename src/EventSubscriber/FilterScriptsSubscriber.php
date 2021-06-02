<?php /** @noinspection PhpUnused JSUnresolvedVariable */

namespace Drupal\loom_cookie\EventSubscriber;

use Drupal;
use Drupal\loom_cookie\CategoryInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterScriptsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

  /**
   * @param FilterResponseEvent $event
   */
  public function onResponse(FilterResponseEvent $event) {
    // only on frontend pages
    if (Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    $response = $event->getResponse();
    $content = $response->getContent();

    // don`t change empty request, like StreamedResponse or BinaryFileResponse
    // Fix "LogicException: The content cannot be set on a BinaryFileResponse/StreamedResponse instance."
    if (!$content) return;

    /** @var CategoryInterface[] $categories */
    $categories = Drupal::entityTypeManager()
      ->getStorage('loom_cookie_category')->loadMultiple();

    $vendor_storage = Drupal::entityTypeManager()
      ->getStorage('loom_cookie_vendor');

    // collect regexes by category for performance reasons
    $url_regexes_by_category = [];
    $block_regexes_by_category = [];
    $embed_url_regexes_by_category = [];
    foreach ($categories as $category) {
      $vendors = $category->scriptUrlRegexesVendors ? $vendor_storage->loadByProperties(['uuid' => $category->scriptUrlRegexesVendors]) : [];
      $url_regexes_by_vendor = [];
      /** @var \Drupal\loom_cookie\Entity\Vendor $vendor */
      foreach ($vendors as $vendor) {
        if ($vendor->hasField('script_url_regexes') && !$vendor->get('script_url_regexes')->isEmpty()) {
          $split = _loom_cookie_multiline_split($vendor->get('script_url_regexes')->getString());
          $url_regexes_by_vendor[$vendor->uuid()] = implode('|', $split);
        }
      }

      $url_regexes_by_category[$category->id()] = $url_regexes_by_vendor
        ? '%^(' . implode('|', $url_regexes_by_vendor) . ')$%imsU'
        : NULL;

      $block_regexes_by_category[$category->id()] = !empty($category->scriptBlockRegexes)
        ? '%<script.*>(' . implode('|', $category->scriptBlockRegexes) . ')</script>%imsU'
        : NULL;

      $vendors = $category->embedUrlRegexesVendors ? $vendor_storage->loadByProperties(['uuid' => $category->embedUrlRegexesVendors]) : [];
      $embed_url_regexes_by_vendor = [];
      /** @var \Drupal\loom_cookie\Entity\Vendor $vendor */
      foreach ($vendors as $vendor) {
        if ($vendor->hasField('embed_url_regexes') && !$vendor->get('embed_url_regexes')->isEmpty()) {
          $split = _loom_cookie_multiline_split($vendor->get('embed_url_regexes')->getString());
          $embed_url_regexes_by_vendor[$vendor->uuid()] = implode('|', $split);
        }
      }

      $embed_url_regexes_by_category[$category->id()] = $embed_url_regexes_by_vendor
        ? '%^(' . implode('|', $embed_url_regexes_by_vendor) . ')$%imsU'
        : NULL;
    }

    $this->filterScripts($categories,
      $url_regexes_by_category,
      $block_regexes_by_category, $content);

    $this->filterEmbeds($categories, $embed_url_regexes_by_category,
      $content);

    // modify the output
    $response->setContent($content);
    $event->setResponse($response);
  }

  /**
   * Replace urls of iframes and embeds.
   *
   * @param CategoryInterface[] $categories
   * @param $embed_url_regexes_by_category
   * @param $content
   */
  private function filterEmbeds($categories, $embed_url_regexes_by_category, &$content) {
    $content = preg_replace_callback(
      '%<iframe.+src="(?<src_iframe>.*)".*>.*</iframe>|<embed.+src="(?<src_embed>.*)"[^>]*\s?/?>%imsU',
      function ($element) use ($embed_url_regexes_by_category, $categories) {
        $whole_tag = $element[0];
        if (!empty($element['src_iframe'])) {
          // it is an iframe
          $src = trim($element['src_iframe']);
          $tag = 'iframe';
        }
        elseif (!empty($element['src_embed'])) {
          // it is an embed
          $src = trim($element['src_embed']);
          $tag = 'embed';
        }
        else {
          return $whole_tag;
        }

        foreach ($categories as $category) {
          $category_id = $category->id();

          $big_url_regex = $embed_url_regexes_by_category[$category_id] ?? '';
          if (empty($big_url_regex)) {
            continue;
          }

          $crawler = new Crawler($whole_tag);
          $src = $crawler->filterXPath('//iframe | //embed')->attr('src');
          $loom_cookie_category = $crawler->filterXPath('//iframe | //embed')->attr('data-loom-cookie-category');
          $loom_cookie_src = $crawler->filterXPath('//iframe | //embed')->attr('data-loom-cookie-src');
          $cookie_category = array_filter(explode(',', $loom_cookie_category ?: ''));
          $cookie_category[] = $category_id;
          $cookie_category = implode(',', array_unique($cookie_category));

          if ($loom_cookie_src && !in_array($category_id, explode(',', $loom_cookie_category))) {
            if (preg_match($big_url_regex, $loom_cookie_src)) {
              $whole_tag =  str_replace(
                'data-loom-cookie-category="' . $loom_cookie_category . '"',
                'data-loom-cookie-category="' . $cookie_category . '"',
                $whole_tag);
              $this->filterEmbeds($categories, $embed_url_regexes_by_category, $whole_tag);
              return $whole_tag;
            }
          }
          elseif (preg_match($big_url_regex, $src)) {
            // replace url with empty data url
            $whole_tag = str_replace($src, 'data:,', $whole_tag);

            // add url to be restored by JS
            $whole_tag = str_replace(
              '<' . $tag,
              '<' . $tag .
              ' data-loom-cookie-category="' . $category_id . '"' .
              ' data-loom-cookie-src="' . $src . '"' .
              ' data-loom-cookie-message="' . htmlspecialchars($categories[$category_id]->embedMessage) . '"',
              $whole_tag);

            // replace this element
            $this->filterEmbeds($categories, $embed_url_regexes_by_category, $whole_tag);
            return $whole_tag;
          }
        }

        // no replacement necessary
        return $whole_tag;
      }, $content);
  }

  /**
   * @param $categories
   * @param $url_regexes_by_category
   * @param $block_regexes_by_category
   * @param $content
   */
  private function filterScripts($categories, $url_regexes_by_category, $block_regexes_by_category, &$content) {
    $content = preg_replace_callback(
      '%<script[^>]*>.*</script>%imsU',
      function ($script_element) use ($categories, $url_regexes_by_category, $block_regexes_by_category) {
        $whole_tag = $script_element[0];
        $script_element = $script_element[0];

        foreach ($categories as $category) {
          $category_id = $category->id();

          $crawler = new Crawler($script_element);
          $src = $crawler->filterXPath('//script')->attr('src');
          $loom_cookie_category = $crawler->filterXPath('//script')->attr('data-loom-cookie-category');
          $loom_cookie_src = $crawler->filterXPath('//script')->attr('data-loom-cookie-src');
          $cookie_category = array_filter(explode(',', $loom_cookie_category ?: ''));
          $cookie_category[] = $category_id;
          $cookie_category = implode(',', array_unique($cookie_category));
          if ($loom_cookie_src && !in_array($category_id, explode(',', $loom_cookie_category))) {
            // it is a script tag with data-loom-cookie-src attribute

            $big_url_regex = $url_regexes_by_category[$category_id];
            if ($big_url_regex && preg_match($big_url_regex, $loom_cookie_src)) {
              $whole_tag =  str_replace(
                'data-loom-cookie-category="' . $loom_cookie_category . '"',
                'data-loom-cookie-category="' . $cookie_category . '"',
                $script_element);
              $this->filterScripts($categories, $url_regexes_by_category, $block_regexes_by_category, $whole_tag);
              return $whole_tag;
            }
          }
          elseif ($src) {
            // it is a script tag with src attribute

            $big_url_regex = $url_regexes_by_category[$category_id];
            if ($big_url_regex && preg_match($big_url_regex, $src)) {
              // replace url with empty data url
              $whole_tag = str_replace($src, 'data:,', $whole_tag);

              // add url to be restored by JS
              $whole_tag = str_replace(
                '<script',
                '<script data-loom-cookie-category="' . $cookie_category . '"' .
                ' data-loom-cookie-src="' . $src . '"',
                $whole_tag);

              // replace this element
              $this->filterScripts($categories, $url_regexes_by_category, $block_regexes_by_category, $whole_tag);
              return $whole_tag;
            }
          }
          else {
            // it is a script block

            $big_block_regex = $block_regexes_by_category[$category_id];
            if ($big_block_regex) {
              if (preg_match($big_block_regex, $script_element)) {
                // replace script block with empty script tag and add original
                // content as attribute
                $whole_tag = preg_replace_callback(
                  '%(?<begin><script.*)>(?<script_content>.+)(?<end></script>)%imsU',
                  function ($matches) use ($cookie_category) {
                    return $matches['begin'] .
                      ' data-loom-cookie-category="' . $cookie_category . '"' .
                      ' data-loom-cookie-type="script-block"' .
                      ' data-loom-cookie-content="' . htmlentities($matches['script_content']) . '"' .
                      '>' .
                      $matches['end'];
                  },
                  $whole_tag);

                // replace this element
                return $whole_tag;
              }
            }
          }
        }

        // no replacement necessary
        return $script_element;
      }, $content);
  }

}
