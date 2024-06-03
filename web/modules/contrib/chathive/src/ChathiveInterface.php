<?php

namespace Drupal\chathive;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;

/**
 * Provides an interface defining a chathive entity type.
 */
interface ChathiveInterface extends ConfigEntityInterface, ObjectWithPluginCollectionInterface {

  /**
   * Gets the Chathive chatbot ID.
   *
   * @return string
   *   The chatbot ID.
   */
  public function getChatbotId(): string;

  /**
   * Allow notifications to be shown when widget is closed on mobile.
   *
   * @return bool
   *   TRUE if we allow mobile notifications when the widget is closed,
   *   FALSE otherwise.
   */
  public function isMobileNotifications(): bool;

  /**
   * Returns an array of visibility condition configurations.
   *
   * @return array
   *   An array of visibility condition configuration keyed by the condition ID.
   */
  public function getVisibility();

  /**
   * Gets conditions for this block.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  public function getVisibilityConditions();

}
