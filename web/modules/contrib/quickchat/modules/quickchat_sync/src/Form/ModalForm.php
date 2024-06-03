<?php

namespace Drupal\quickchat_sync\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\quickchat_sync\QuickchatSyncApiClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modal form to handle the quickchat sync operations.
 */
class ModalForm extends FormBase {
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
  public function getFormId() {
    return 'quickchat_operation_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $model_name = NULL, $operation = NULL) {
    $form['#prefix'] = '<div id="quickchat_operation_modal">';
    $form['#suffix'] = '</div>';

    $markup = '<p>Are you sure you want to ' . $operation . ' the model?</p>';

    if ($operation == 'update') {
      $markup .= 'This will overwrite the knowledge base at Quickchat AI.';
      $operation = 'update_knowledge_base';
    }

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    $form['operation'] = [
      '#type' => 'hidden',
      '#value' => $operation,
    ];

    $form['model_name'] = [
      '#type' => 'hidden',
      '#value' => $model_name,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button--primary',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#quickchat_operation_modal', $form));
    }
    else {
      $response->addCommand(new OpenModalDialogCommand(
        "Operation completed",
        "The knowledge base has been synced on Quickchat AI.")
      );
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($operation = $form_state->getValue('operation')) {
      $config = $this->configFactory->get('quickchat_sync.settings');
      $sync = $config->get('sync');
      $model_name = $form_state->getValue('model_name');

      foreach ($sync as $model_id => $properties) {
        if ($properties['label_machine_name'] === $model_name) {
          break;
        }
      }

      $scenario_id = $sync[$model_id]['scenario_id'];
      $token = $sync[$model_id]['token'];

      if (array_key_exists('view_arguments', $sync[$model_id])) {
        $arguments = $sync[$model_id]['view_arguments'];
      }

      $view_name = $sync[$model_id]['view_name'];
      $view_display = $sync[$model_id]['view_display'];
      $view_arguments = explode(',', $arguments);
      $text = $this->quickchatSyncApiClient->getViewHtml($view_name, $view_display, $view_arguments);

      $response = $this->quickchatSyncApiClient->sync($operation, $scenario_id, $token, $text);
    }
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('quickchat_sync.operation', [
      'model_name' => $form['model_name']['#value'],
    ]);
  }

}
