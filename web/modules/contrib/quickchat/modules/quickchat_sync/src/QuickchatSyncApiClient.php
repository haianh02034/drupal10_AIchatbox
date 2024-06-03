<?php

namespace Drupal\quickchat_sync;

use Drupal\quickchat\QuickchatApiClient;
use Drupal\Component\Utility\Html;
use Drupal\views\Views;

/**
 * Quickchat Sync API client extending QuickchatApiClient base class.
 */
class QuickchatSyncApiClient extends QuickchatApiClient {

  /**
   * API endpoint to fetch, re-train or update a Drupal chatbot model.
   *
   * @param string $action
   *   Action to perform: fetch, retrain, update_name, update_short_description,
   *   update_knowledge_base, update_how_to_reach_you.
   * @param string $scenario_id
   *   ID associated with your Quickchat API implementation.
   *   update_knowledge_base, update_how_to_reach_you.
   * @param string $token
   *   Token to use for API limited access.
   * @param string $text
   *   Optional parameter, only to be provided for update actions.
   *
   * @return array
   *   Json from API response parsed to array format.
   *
   * @see https://www.quickchat.ai/docs/#send-message-to-ai
   */
  public function sync($action, $scenario_id, $token, $text = NULL) {
    $data = [
      'token' => $token,
      'action' => $action,
      'scenario_id' => $scenario_id,
    ];

    if ($text) {
      $data['text'] = $this->normalizeText($text);

      if ($action == 'update_knowledge_base') {
        $data['text'] = json_encode(explode('\n', $data['text']));
      }
    }

    if ($response = $this->request('POST', '/drupal/', $data)) {
      if ($response->getStatusCode() != 200) {
        $this->messenger->addError($this->t('@error', [
          '@error' => $response->getReasonPhrase(),
        ]));
      }

      return $response;
    }
  }

  /**
   * Removes superfluous whitespace and unescapes HTML entities.
   *
   * @param string $value
   *   The text to process.
   *
   * @return string
   *   The text without unnecessary whitespace and HTML entities transformed
   *   back to plain text.
   */
  protected function normalizeText($value) {
    $value = Html::decodeEntities($value);
    $value = trim($value);
    return $value;
  }

  /**
   * Helper function to get the render HTML from a given view.
   */
  public function getViewHtml($view_name, $view_display, $arguments = []) {
    $html = '';

    if ($view = Views::getView($view_name)) {
      $build = $view->buildRenderable($view_display, $arguments);
      $html = strip_tags(\Drupal::service('renderer')->renderPlain($build));
      $html = preg_replace('/\r|\n/', '', trim($html));
    }

    return rtrim($html);
  }

}
