<?php

namespace Drupal\loom_cookie\Services;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\loom_cookie\Communicator;
use Psr\Log\LoggerInterface;

/**
 * Class VendorService.
 */
class VendorService {

  const API_ROUTE = '/vendors';

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var ContentEntityStorageInterface
   */
  protected $vendorStorage;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * VendorService constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger = NULL) {
    $this->entityTypeManager = $entityTypeManager;
    $this->vendorStorage = $this->entityTypeManager->getStorage('loom_cookie_vendor');
    $this->logger = $logger;
  }

  /**
   * Get a specific loom_cookie setting.
   *
   * @param string $name
   * @param bool $log_empty
   *
   * @return array|mixed|null
   */
  public function getConfig($name, $log_empty = FALSE) {
    $value = \Drupal::configFactory()->get('loom_cookie.settings')->get($name);
    if (!$value && $log_empty) {
      $this->logger->error("The drupal setting 'loom_cookie.$name' is empty.");
    }

    return $value;
  }

  /**
   * Import all vendors from remote API.
   */
  public function import() {
    $domain = $this->getConfig('api_domain');
    if (!$domain) {
      return;
    }

    $auth = [
      $this->getConfig('api_user', TRUE),
      $this->getConfig('api_password', TRUE),
    ];
    if (count(array_filter($auth)) !== 2) {
      return;
    }

    $basic_auth = [
      $this->getConfig('api_shield_user'),
      $this->getConfig('api_shield_password'),
    ];

    try {
      $communicator = new Communicator($domain, $auth);
      $content = $communicator->loadContent(self::API_ROUTE);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return;
    }

    // collect data from response
    $data = [];
    foreach ($content as $term) {
      $uuid = $term->uuid[0]->value;

      $regex_field_names = [
        'field_attachment_names',
        'field_script_url_regexes',
        'field_embed_url_regexes',
      ];
      $regex_field_values = [];
      foreach ($regex_field_names as $field_name) {
        foreach ($term->{$field_name} as $value) {
          $regex_field_values[$field_name][] = $value->value;
        }
      }

      $data[$uuid] = [
        'name' => $term->name[0]->value,
        'uuid' => $uuid,
        'changed' => strtotime($term->changed[0]->value),
        'attachment_names' => implode("\r\n", $regex_field_values['field_attachment_names'] ?? []) ?: NULL,
        'script_url_regexes' => implode("\r\n", $regex_field_values['field_script_url_regexes'] ?? []) ?: NULL,
        'embed_url_regexes' => implode("\r\n", $regex_field_values['field_embed_url_regexes'] ?? []) ?: NULL,
      ];
    }

    // update existing
    /** @var \Drupal\loom_cookie\Entity\Vendor $vendor */
    foreach ($data ? $this->vendorStorage->loadByProperties(['uuid' => array_keys($data)]) : [] as $vendor) {
      // check if update is necessary
      $flattened = array_map(function($item) {return $item[0]['value'];}, $vendor->toArray());
      $flattened = array_intersect_key($flattened, $data[$vendor->uuid()]);
      if ($flattened == $data[$vendor->uuid()]) {
        unset($data[$vendor->uuid()]);
        continue;
      }

      // update vendor
      foreach ($data[$vendor->uuid()] as $name => $value) {
        $vendor->set($name, $value);
      }
      $vendor->save();
      unset($data[$vendor->uuid()]);
    }

    // create missing
    foreach ($data as $values) {
      $this->vendorStorage->create($values)->save();
    }
  }

}
