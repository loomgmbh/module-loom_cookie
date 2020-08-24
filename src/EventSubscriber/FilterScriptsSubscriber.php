<?php /** @noinspection PhpUnused JSUnresolvedVariable */

namespace Drupal\loom_cookie\EventSubscriber;

use Drupal;
use Drupal\loom_cookie\CategoryInterface;
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

    /** @var CategoryInterface[] $categories */
    $categories = Drupal::entityTypeManager()
      ->getStorage('loom_cookie_category')->loadMultiple();

    // collect regexes by category for performance reasons
    $url_regexes_by_category = [];
    $block_regexes_by_category = [];
    $embed_url_regexes_by_category = [];
    foreach ($categories as $category) {
      $url_regexes_by_category[$category->id()] = !empty($category->scriptUrlRegexes)
        ? '%^(' . implode('|', $category->scriptUrlRegexes) . ')$%imsU'
        : NULL;

      $block_regexes_by_category[$category->id()] = !empty($category->scriptBlockRegexes)
        ? '%<script.*>(' . implode('|', $category->scriptBlockRegexes) . ')</script>%imsU'
        : NULL;

      $embed_url_regexes_by_category[$category->id()] = !empty($category->embedUrlRegexes)
        ? '%^(' . implode('|', $category->embedUrlRegexes) . ')$%imsU'
        : NULL;
    }

    $this->filterScripts($categories,
      $url_regexes_by_category,
      $block_regexes_by_category, $content);

    $this->filterEmbeds($embed_url_regexes_by_category,
      $content);

    // modify the output
    $response->setContent($content);
    $event->setResponse($response);
  }

  /**
   * Replace urls of iframes and embeds.
   *
   * @param $embed_url_regexes_by_category
   * @param $content
   */
  private function filterEmbeds($embed_url_regexes_by_category, &$content) {
    $content = preg_replace_callback(
      '%<iframe.+src="(?<src_iframe>.*)".*>.*</iframe>|<embed.+src="(?<src_embed>.*)"[^>]*\s?/?>%imsU',
      function ($element) use ($embed_url_regexes_by_category) {
        $whole_tag = $element[0];
        if (!empty($element['src_iframe'])) {
          // it is an iframe
          $src = $element['src_iframe'];
          $tag = 'iframe';
        }
        elseif (!empty($element['src_embed'])) {
          // it is an embed
          $src = $element['src_embed'];
          $tag = 'embed';
        }
        else {
          return $whole_tag;
        }

        foreach ($embed_url_regexes_by_category as $category_id => $big_url_regex) {
          if (empty($big_url_regex)) {
            continue;
          }

          if (preg_match($big_url_regex, $src)) {
            // replace url with empty data url
            $whole_tag = str_replace($src, 'data:,', $whole_tag);

            // add url to be restored by JS
            $whole_tag = str_replace(
              '<' . $tag,
              '<' . $tag . ' data-loom-cookie-category="' . $category_id . '"' .
              ' data-loom-cookie-src="' . $src . '"',
              $whole_tag);

            // replace this element
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

          preg_match('%<script(?> .*)* src=["\'](.*)["\'][^>]*>%im',
            $script_element, $matches);
          if (!empty($matches)) {
            // it is a script tag with src attribute

            $big_url_regex = $url_regexes_by_category[$category_id];
            if ($big_url_regex) {
              $src = $matches[1];
              if (preg_match($big_url_regex, $src)) {
                // replace url with empty data url
                $whole_tag = str_replace($src, 'data:,', $whole_tag);

                // add url to be restored by JS
                $whole_tag = str_replace(
                  '<script',
                  '<script data-loom-cookie-category="' . $category_id . '"' .
                  ' data-loom-cookie-src="' . $src . '"',
                  $whole_tag);

                // replace this element
                return $whole_tag;
              }
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
                  function ($matches) use ($category_id) {
                    return $matches['begin'] .
                      ' data-loom-cookie-category="' . $category_id . '"' .
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
