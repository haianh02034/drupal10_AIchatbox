<?php

namespace Drupal\quickchat_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quickchat_sync\QuickchatSyncApiClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the module settings form.
 */
class SettingsForm extends ConfigFormBase {

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
    parent::__construct($config_factory);
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
    return 'quickchat_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quickchat_sync.settings');
    $sync = $config->get('sync');

    $num_lines = $form_state->get('num_lines');

    if ($num_lines === NULL) {
      $form_state->set('num_lines', 1);

      if ($config->get('num_lines')) {
        $form_state->set('num_lines', $config->get('num_lines'));
      }

      $num_lines = $form_state->get('num_lines');
    }

    $removed_fields = $form_state->get('removed_fields');
    if ($removed_fields === NULL) {
      $form_state->set('removed_fields', []);
      $removed_fields = $form_state->get('removed_fields');
    }

    $form['#tree'] = TRUE;
    $form['models'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Chatbot Models Configuration'),
      '#prefix' => '<div id="models-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_lines; $i++) {
      if (in_array($i, $removed_fields)) {
        continue;
      }

      $form['models'][$i] = [
        '#type' => 'fieldset',
        '#title' => '',
      ];

      $form['models'][$i]['label'] = [
        '#type' => 'textfield',
        '#id' => 'model_label_' . $i,
        '#title' => $this->t('Label'),
        '#required' => TRUE,
        '#description' => $this->t('Enter a model label'),
        '#default_value' => !empty($sync) ? $sync[$i]['label'] : '',
      ];

      $form['models'][$i]['label_machine_name'] = [
        '#type' => 'machine_name',
        '#default_value' => !empty($sync) ? $sync[$i]['label_machine_name'] : '',
        '#machine_name' => [
          'source' => ['models', $i, 'label'],
          'exists' => [$this, 'machineNameExists'],
        ],
      ];

      $form['models'][$i]['scenario_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Scenario ID'),
        '#required' => TRUE,
        '#description' => $this->t('Visit the integrations tab at Quick Chat to discover the scenario ID: https://app.quickchat.ai.'),
        '#default_value' => !empty($sync) ? $sync[$i]['scenario_id'] : '',
      ];

      $form['models'][$i]['token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Token'),
        '#required' => TRUE,
        '#description' => $this->t('Token used for chatbot model trainings.'),
        '#default_value' => !empty($sync) ? $sync[$i]['token'] : '',
      ];

      $form['models'][$i]['view_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View Name'),
        '#required' => TRUE,
        '#description' => $this->t('Enter view name.'),
        '#default_value' => !empty($sync) ? $sync[$i]['view_name'] : '',
      ];

      $form['models'][$i]['view_display'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View Display'),
        '#required' => TRUE,
        '#description' => $this->t('Enter view display.'),
        '#default_value' => !empty($sync) ? $sync[$i]['view_display'] : '',
      ];

      $arguments = '';

      if (!empty($sync) && array_key_exists('view_arguments', $sync[$i])) {
        $arguments = $sync[$i]['view_arguments'];
      }

      $form['models'][$i]['view_arguments'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View Arguments (optional)'),
        '#description' => $this->t('Enter comma-separated arguments.'),
        '#default_value' => $arguments,
      ];

      $form['models'][$i]['actions'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => $i,
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::ajaxSycCallback',
          'wrapper' => 'models-fieldset-wrapper',
        ],
      ];

      $form['models'][$i]['actions']['#attributes']['class'][] = 'button--danger';
    }

    $form['models']['actions'] = [
      '#type' => 'actions',
    ];

    $form['models']['actions']['add_model'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => ['::addModel'],
      '#ajax' => [
        'callback' => '::ajaxSycCallback',
        'wrapper' => 'models-fieldset-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the models sync in it.
   */
  public function ajaxSycCallback(array &$form, FormStateInterface $form_state) {
    return $form['models'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addModel(array &$form, FormStateInterface $form_state) {
    $num_field = $form_state->get('num_lines');
    $add_button = $num_field + 1;
    $form_state->set('num_lines', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove" button.
   *
   * Removes the corresponding line.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $indexToRemove = $trigger['#name'];

    unset($form['models'][$indexToRemove]);
    $namesFieldset = $form_state->getValue('models');
    unset($namesFieldset[$indexToRemove]);
    $form_state->setValue('models', $namesFieldset);

    $removed_fields = $form_state->get('removed_fields');
    $removed_fields[] = $indexToRemove;
    $form_state->set('removed_fields', $removed_fields);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $models = $values['models'];
    unset($models['actions']);
    $this->configFactory->getEditable('quickchat_sync.settings')
      ->set('sync', array_values($models))
      ->set('num_lines', count($models))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Checks if a model machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'machine_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the model machine name is taken.
   */
  public function machineNameExists($value, array $element, FormStateInterface $form_state) {
    $config = $this->configFactory->get('quickchat_sync.settings');
    $sync = $config->get('sync');

    foreach ($sync as $properties) {
      if ($properties['label_machine_name'] === $value) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
