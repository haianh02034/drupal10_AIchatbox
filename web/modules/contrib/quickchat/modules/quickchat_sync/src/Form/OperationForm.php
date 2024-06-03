<?php

namespace Drupal\quickchat_sync\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quickchat_sync\QuickchatSyncApiClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements the module operation form.
 */
class OperationForm extends FormBase {

  /**
   * Maximum number of words for paragraph is 150.
   */
  const QUICKCHAT_MAX_NUMBERS_WORDS = 150;

  /**
   * Maximum number of characters for paragraph is 1000.
   */
  const QUICKCHAT_MAX_NUMBERS_CHARACTERS = 1000;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The quickchat_sync.api_client service.
   *
   * @var \Drupal\quickchat_sync\QuickchatSyncApiClient
   */
  protected $quickchatSyncApiClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, QuickchatSyncApiClient $quickchat_api_client) {
    $this->configFactory = $config_factory;
    $this->quickchatSyncApiClient = $quickchat_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('quickchat_sync.api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'quickchat_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quickchat_sync_operation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $model_name = NULL) {
    $config = $this->configFactory->get('quickchat_sync.settings');
    $sync = $config->get('sync');
    $model_exists = FALSE;

    foreach ($sync as $model_id => $properties) {
      $machine_name = $properties['label_machine_name'];

      if ($machine_name === $model_name) {
        $model_exists = TRUE;
        break;
      }
    }

    if (!$model_exists) {
      $path = Url::fromRoute('quickchat_sync.list')->toString();
      $response = new RedirectResponse($path, 302);
      $response->send();
    }

    $form['#tree'] = TRUE;
    $form = [
      '#type' => 'fieldset',
      '#title' => $sync[$model_id]['label'],
      '#prefix' => '<div id="models-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $arguments = '';

    if (array_key_exists('view_arguments', $sync[$model_id])) {
      $arguments = $sync[$model_id]['view_arguments'];
    }

    $view_name = $sync[$model_id]['view_name'];
    $view_display = $sync[$model_id]['view_display'];
    $view_arguments = explode(',', $arguments);
    $preview = $this->quickchatSyncApiClient->getViewHtml($view_name, $view_display, $view_arguments);
    $preview = array_filter(explode('\n', $preview));
    $preview = $this->generateHtmlList($preview);

    if ($confirm_action = $form_state->get('confirm')) {
      $form[$model_id]['confirm'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Are you sure you want to %action this model?', [
          '%action' => $confirm_action,
        ]),
        '#required' => TRUE,
        '#description' => $this->t('Check this box to confirm that you want to %action this model.', [
          '%action' => $confirm_action,
        ]),
      ];
    }

    $form[$model_id]['update'] = [
      '#type' => 'link',
      '#title' => $this->t('Update'),
      '#url' => Url::fromRoute('quickchat_sync.modal_form', [
        'model_name' => $model_name,
        'operation' => 'update',
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];

    $form[$model_id]['rebuild'] = [
      '#type' => 'link',
      '#title' => $this->t('Rebuild'),
      '#url' => Url::fromRoute('quickchat_sync.modal_form', [
        'model_name' => $model_name,
        'operation' => 'retrain',
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];

    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form[$model_id]['preview'] = [
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => $preview,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Helper to generate a HTML list using given array.
   *
   * @param array $values
   *   Array containing the values to parse into a HTML list.
   */
  private function generateHtmlList(array $values) {
    $return = '<ul>';

    foreach ($values as $value) {
      $errors = '';

      if (str_word_count($value) > OperationForm::QUICKCHAT_MAX_NUMBERS_WORDS) {
        $this->messenger()->addError($this->t('You entered @words words. Maximum number of words for paragraph is @max_words.', [
          '@words' => str_word_count($value),
          '@max_words' => OperationForm::QUICKCHAT_MAX_NUMBERS_WORDS,
        ]));
        $errors = '❌';
      }

      if (strlen($value) > OperationForm::QUICKCHAT_MAX_NUMBERS_CHARACTERS) {
        $this->messenger()->addError($this->t('You entered @characters characters. Maximum number of characters for paragraph is @max_characters.', [
          '@characters' => strlen($value),
          '@max_characters' => OperationForm::QUICKCHAT_MAX_NUMBERS_CHARACTERS,
        ]));
        $errors = '❌';
      }

      $return .= "<li>$errors $value</li>";
    }

    $return .= '</ul>';

    return $return;
  }

}
