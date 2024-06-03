<?php

namespace Drupal\custom_chatbot\Plugin\Block;
use Drupal\Core\Render\Markup;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Custom Chatbot' Block.
 *
 * @Block(
 *   id = "custom_chatbot_block",
 *   admin_label = @Translation("Custom Chatbot Block"),
 *   category = @Translation("Custom"),
 * )
 */
class CustomChatbotBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Tạo button có id để sử dụng trong JavaScript
    $button = '<div id="chat-icon-container" style="position: fixed; bottom: 20px; right: 20px;">';
    $button .= '<button id="chat-icon" aria-pressed="false">';
    $button .= '<i class="fa fa-comment" aria-hidden="true"></i>';
    $button .= '</button></div>';

    // Tạo iframe để nhúng URL của Voiceflow
    $iframe = '<div id="chat-iframe" style="display: none; position: fixed; bottom: 20px; right: 20px; width: 300px; height: 400px; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); z-index: 9999;">';
    $iframe .= '<iframe src="https://creator.voiceflow.com/prototype/6640e765f009f48ec9ea3b27" width="100%" height="100%" frameborder="0"></iframe>';
    $iframe .= '</div>';    
    // Sử dụng Markup để tạo một chuỗi HTML an toàn
    $markup = Markup::create($button . $iframe);

    // Trả về một mảng renderable array
    return [
      '#markup' => $markup,
      '#attached' => [
        'library' => [
          'custom_chatbot/custom_chatbot',
        ],
      ],  
    ];
  }

}