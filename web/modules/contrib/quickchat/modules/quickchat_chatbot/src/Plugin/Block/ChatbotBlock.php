<?php

namespace Drupal\quickchat_chatbot\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Chatbot' block.
 *
 * @Block(
 *   id = "chatbot_block",
 *   admin_label = @Translation("Chatbot"),
 *   category = @Translation("Quickchat"),
 * )
 */
class ChatbotBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'scenario_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['scenario_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scenario ID'),
      '#required' => TRUE,
      '#description' => $this->t('Visit the integrations tab at Quick Chat to discover the scenario ID: https://app.quickchat.ai.'),
      '#default_value' => $config['scenario_id'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['scenario_id'] = $form_state->getValue('scenario_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#markup' => '',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $build['#attached']['library'][] = 'quickchat_chatbot/quickchat';

    $build['#attached']['drupalSettings']['quickchat_chatbot'] = [
      'scenario_id' => $this->configuration['scenario_id'],
    ];

    return $build;
  }

}
