<?php

namespace Drupal\quickchat_sync\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements the module operation form.
 */
class ListOperationForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
    return 'quickchat_sync_operation_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('quickchat_sync.settings');
    $sync = $config->get('sync');
    $num_lines = $config->get('num_lines');
    $output = [];
    $header = [
      'model' => $this->t('Model'),
    ];

    for ($i = 0; $i < $num_lines; $i++) {
      $machine_name = $sync[$i]['label_machine_name'];
      $output[] = [
        'model' => Link::fromTextAndUrl(
          $sync[$i]['label'],
          Url::fromUserInput("/admin/content/kb/$machine_name/operations")
        ),
      ];
    }

    $form['models'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => t('No models found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
