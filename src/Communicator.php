<?php

namespace Drupal\loom_cookie;

use Drupal;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;

/**
 * Class Communicator
 *
 * @package Drupal\loom_cookie
 */
class Communicator {

  /**
   * For d8 '/rest/session/token', for d9 '/session/token'
   *
   * @var string
   */
  const csrfTokenRoute = '/session/token';

  protected $httpClient;
  protected $domain;
  protected $auth = [];
  protected $cookie;
  protected $token;
  protected $basic_auth = [];

  /**
   * Communicator constructor.
   *
   * @param string $domain
   * @param array $auth
   * @param array $basic_auth
   */
  public function __construct($domain, array $auth, array $basic_auth = []) {
    $this->domain = $domain;
    $this->auth = $auth;
    $this->basic_auth = $basic_auth;

    $this->cookie = $this->initSessionCookie();

    $params = [
      'cookies' => $this->cookie,
    ];

    if ($this->getBasicAuth()) {
      $params['auth'] = $this->getBasicAuth();
    }

    $this->httpClient = new Client($params);

    $this->token = $this->getToken();
  }

  protected function initSessionCookie() {
    $base_url = $this->getDomain();
    $cid = $base_url . '.sessioncookie';
    $cookie = NULL;

    // Handle session cookie.
    if ($cache = Drupal::cache()->get($cid)) {
      $cookie = new CookieJar(true, $cache->data);
    }

    $logged_in = $this->getUserStatus($cookie);

    if (!$logged_in || !$cookie || !$cookie->count()) {
      $cookie = new CookieJar();
      if ($this->getSessionCookie($cookie)) {
        Drupal::cache()->set($cid, $cookie);
      }
    }

    return $cookie;
  }

  /**
   * @return bool
   */
  protected function getUserStatus($cookie) {
    $base_url = $this->getDomain();

    $params = [
      'cookies' => $cookie,
      'verify' => FALSE,
    ];

    if ($this->getBasicAuth()) {
      $params['auth'] = $this->getBasicAuth();
    }

    $response = Drupal::httpClient()->get($base_url . '/user/login_status?_format=json', $params);

    if ($response->getStatusCode() == 200) {
      return (bool)(string)$response->getBody(TRUE);
    }
    return FALSE;
  }

  /**
   * @return bool
   */
  protected function getSessionCookie(&$cookie) {
    $base_url = $this->getDomain();
    [$name, $pass] = $this->getCredentials();

    $params = [
      'form_params' => [
        'name'=> $name,
        'pass'=> $pass,
        'form_id' => 'user_login_form',
      ],
      'cookies' => $cookie,
      'verify' => FALSE,
    ];

    if ($this->getBasicAuth()) {
      $params['auth'] = $this->getBasicAuth();
    }

    $response = Drupal::httpClient()->post($base_url . '/user/login', $params);

    if ($response->getStatusCode() == 200) {
      return TRUE;
    }

    return FALSE;
  }

  public function getHttpClient() {
    return $this->httpClient;
  }

  public function getDomain() {
    return $this->domain;
  }

  public function getCredentials() {
    return $this->auth;
  }

  public function getBasicAuth() {
    return $this->basic_auth;
  }

  /**
   * @return \GuzzleHttp\Cookie\CookieJar
   */
  public function getCookie() {
    return $this->cookie;
  }

  protected function getToken() {
    if ($this->token) {
      return $this->token;
    }

    $base_url = $this->getDomain();

    $client = $this->getHttpClient();
    $token = (string)$client->get($base_url . self::csrfTokenRoute, ['verify' => FALSE])->getBody(TRUE);

    return $token;
  }

  public function httpRequest($method, $path, $body = NULL) {
    $base_url = $this->getDomain();
    $client = $this->getHttpClient();

    $params = [
      'headers' => [
        'Content-Type' => 'application/hal+json',
        'X-CSRF-Token' => $this->getToken(),
      ],
      'cookies' => $this->cookie,
      'timeout' => 300,
      'verify' => FALSE,
    ];

    if ($body) {
      $params['body'] = $body;
    }

    $response = $client->{$method}($base_url . $path, $params);

    $body = json_decode($response->getBody()->getContents());
    $status = $response->getStatusCode();

    if ($body && $status == 200) {
      return $body;
    }

    return FALSE;
  }

  public function loadContent(string $path, string $format = 'json', string $method = 'get', $body = NULL) {
    $divider = '?';

    if(strpos($path, '?') !== FALSE) {
      $divider = '&';
    }

    $requestPath = sprintf('%s%s_format=%s', $path, $divider, $format);
    return $this->httpRequest($method, $requestPath, $body);
  }

}
