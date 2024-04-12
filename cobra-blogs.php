<?php

/**
 *
 * @link              https://wez.com.br
 * @since             1.0.0
 * @package           Cobra_Blogs
 *
 * @wordpress-plugin
 * Plugin Name:       WeZ-Cobrança
 * Plugin URI:        https://wez.com.br
 * Description:       Verifica se cliente pagou a fatura e informa na tela de login
 * Version:           1.0.1
 * Author:            Hélio
 * Author URI:        https://helio.me/
 * Text Domain:       cobra-blogs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}


function custom_login_message(string $message): string
{
  $apiResult = makeApiRequest();

  $inlineStyles = <<<CSS
<style>
#cobra {
  width: 100vw;
  position: fixed;
  /*bottom: 0;*/
  left: 0;
  background-color: darkorange;
  font-size: large;
  text-align: center;
  font-weight: 700;
  color: white;
}

#cobra a {
  color: black;
}
</style>
CSS;


//  if (WP_DEBUG) {
//    echo '<pre>';
//    var_dump($apiResult);
//    echo '</pre>';
//  }

  if (empty($apiResult)) return '';

  $debits = $apiResult['data']['response']['totalCount'];
  $pluralizedDebits = $debits === 1 ? 'fatura pendente' : 'faturas pendentes';

  $payNowUrl = end($apiResult['data']['response']['data'])['invoiceUrl'];
  $payNowLink = "<br><a href='$payNowUrl' target='_blank'>PAGAR AGORA</a>";

  $customMessage = "Você tem <b>$debits</b> $pluralizedDebits! $payNowLink";

  return "$message$inlineStyles<div id='cobra'><p>$customMessage</p></div>";

}

add_filter('login_site_html_link', 'custom_login_message');

/**
 * Make an API request.
 *
 * @return string The result of the API request.
 */
function makeApiRequest(): array
{
  $siteHost = wp_parse_url(home_url(), PHP_URL_HOST);
  $siteHost = str_replace('www.', '', $siteHost);

  $base = WP_DEBUG ? 'http://localhost:8787' : 'https://cobra-blogs.helio.me';
  $apiUrl = $base . '/check/' . $siteHost;

  $response = wp_remote_get($apiUrl);

  if (is_wp_error($response)) return [];

  $responseBody = wp_remote_retrieve_body($response);
  $data         = json_decode($responseBody, true);

  if (empty($data) || $data['ok'] !== true || $data['data']['PENDING'] !== true) return [];

  return $data;
}
