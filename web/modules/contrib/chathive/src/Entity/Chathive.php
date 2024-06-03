<?php

namespace Drupal\chathive\Entity;

use Drupal\chathive\ChathiveInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the chathive entity type.
 *
 * @ConfigEntityType(
 *   id = "chathive",
 *   label = @Translation("Chathive"),
 *   label_collection = @Translation("Chathives"),
 *   label_singular = @Translation("chathive"),
 *   label_plural = @Translation("chathives"),
 *   label_count = @PluralTranslation(
 *     singular = "@count chathive",
 *     plural = "@count chathives",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\chathive\ChathiveListBuilder",
 *     "form" = {
 *       "default" = "Drupal\chathive\Form\ChathiveForm",
 *       "add" = "Drupal\chathive\Form\ChathiveForm",
 *       "edit" = "Drupal\chathive\Form\ChathiveForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "chathive",
 *   admin_permission = "administer chathive configuration",
 *   links = {
 *     "collection" = "/admin/structure/chathives",
 *     "add-form" = "/admin/structure/chathives/add",
 *     "edit-form" = "/admin/structure/chathives/{chathive}",
 *     "delete-form" = "/admin/structure/chathives/{chathive}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "chatbot_id" = "chatbot_id",
 *     "description" = "description"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "chatbot_id",
 *     "visibility",
 *     "mobile_notifications"
 *   }
 * )
 */
class Chathive extends ConfigEntityBase implements ChathiveInterface, EntityWithPluginCollectionInterface {

  /**
   * The chathive ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The chathive label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The chathive status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The chathive description.
   *
   * @var string
   */
  protected string $description;

  /**
   * Allow notifications to be shown when widget is closed on mobile.
   *
   * @var bool
   */
  protected bool $mobile_notifications;

  /**
   * The chathive chatbot ID.
   *
   * @var string
   */
  protected $chatbot_id;

  /**
   * The visibility settings for this block.
   *
   * @var array
   */
  protected $visibility = [];

  /**
   * {@inheritdoc}
   */
  public function getChatbotId(): string {
    return $this->chatbot_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isMobileNotifications(): bool {
    return $this->mobile_notifications ?? TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'visibility' => $this->getVisibilityConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->getVisibilityConditions()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions() {
    if (!isset($this->visibilityCollection)) {
      $this->visibilityCollection = new ConditionPluginCollection($this->conditionPluginManager(), $this->get('visibility'));
    }
    return $this->visibilityCollection;
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

}
