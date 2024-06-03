<?php

namespace Drupal\chathive\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class ChathiveController.
 *
 * Controller to add & intitialise the Chathive chatbot widget.
 */
class ChathiveController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected ConditionManager $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected Session $session;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected AdminContext $adminContext;

  /**
   * ChathiveController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager interface.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the visibility UI.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match interface.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler interface.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ExecutableManagerInterface $manager, LanguageManagerInterface $language_manager, RouteMatchInterface $route_match, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler, Session $session, AdminContext $admin_context) {
    $this->entityTypeManager = $entity_type_manager;
    $this->manager = $manager;
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->session = $session;
    $this->adminContext = $admin_context;
  }

  /**
   * Attaches the Oswald chatbot widget and initializes it.
   *
   * @param array $page
   *   The whole page array.
   */
  public function attachAndInitialize(array &$page): void {
    if ($this->adminContext->isAdminRoute()) {
      return;
    }

    $chatbots = $this->getChatbots();
    // Exit early if we have not received a chatbot.
    if (empty($chatbots)) {
      return;
    }

    foreach ($chatbots as $chatbot) {
      // Set necessary caching information.
      $cacheable_metadata = CacheableMetadata::createFromRenderArray($page);
      $cacheable_metadata->addCacheTags($chatbot->getCacheTags());
      $cacheable_metadata->applyTo($page);

      if (!$chatbot->status()) {
        continue;
      }

      // Initialise the chatbot and pass our settings.
      $page['#attached']['library'][] = 'chathive/chathive-initialisation';
      $page['#attached']['drupalSettings']['chathive']['instances'][$chatbot->id()]['apiKey'] = $chatbot->getChatbotId();
      $page['#attached']['drupalSettings']['chathive']['instances'][$chatbot->id()]['langcode'] = $this->languageManager->getCurrentLanguage()->getId();
      $page['#attached']['drupalSettings']['chathive']['instances'][$chatbot->id()]['mobileNotifications'] = $chatbot->isMobileNotifications();
    }
  }

  /**
   * Get the active chatbot.
   *
   * @return \Drupal\chathive\ChathiveInterface[]
   *   The chatbot instances, Empty array if no active chatbots found.
   */
  protected function getChatbots(): array {
    try {
      // Get all the enabled chatbots.
      $chatbots = $this->entityTypeManager->getStorage('chathive')
        ->loadMultiple();

      /** @var \Drupal\Core\Condition\ConditionManager $manager */
      $language = $this->languageManager->getCurrentLanguage()->getId();
      // Remove chatbots which do not evaluate our conditions.
      /** @var \Drupal\chathive\Entity\Chathive $chatbot */
      foreach ($chatbots as $key => $chatbot) {
        if (!$chatbot->status()) {
          unset($chatbots[$key]);
          continue;
        }

        foreach ($chatbot->getVisibilityConditions() as $condition_id => $condition) {
          if ($condition_id === 'language') {
            $condition->setContextValue('language', $language);
          }
          if (!$this->manager->execute($condition)) {
            unset($chatbots[$key]);
            break;
          }
        }
      }

      // Allow other modules to alter the active chatbot.
      if (count($chatbots) >= 1) {
        $data = [
          'route' => $this->routeMatch->getRouteName(),
          'bots' => $chatbots,
          'user' => $this->currentUser,
        ];
        $this->moduleHandler->alter('chathive_active_bot', $data);
        if (isset($data['bots'])) {
          return $data['bots'];
        }
      }
    }
    catch (\Exception $e) {
      // Silently failed.
    }

    return [];
  }

}
