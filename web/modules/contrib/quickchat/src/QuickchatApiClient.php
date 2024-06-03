<?php

namespace Drupal\Quickchat;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Quickchat API client wrapper class.
 */
class QuickchatApiClient {
  use StringTranslationTrait;

  /**
   * Quickchat API base url.
   */
  const QUICKCHAT_API_BASE_URL = 'https://app.quickchat.ai/api';

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new QuickchatApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ClientInterface $http_client,
    UuidInterface $uuid_service,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger) {
    $this->httpClient = $http_client;
    $this->uuidService = $uuid_service;
    $this->messenger = $messenger;
    $this->logger = $logger->get('quickchat');
  }

  /**
   * Performs an Quickchat API request. Wraps the Guzzle HTTP client.
   *
   * @param string $method
   *   HTTP method.
   * @param string $endpoint
   *   The API endpoint to call.
   * @param array $body
   *   The body to send to the API endpoint.
   * @param array $query
   *   API call query parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The request response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @see https://www.quickchat.ai/docs/#introduction
   */
  public function request($method,
    $endpoint,
    array $body = [],
    array $query = []) {

    $endpoint = QuickchatApiClient::QUICKCHAT_API_BASE_URL . $endpoint;

    if ($method === 'GET') {
      $endpoint = Url::fromUri($endpoint, [
        'query' => $query,
      ])->toString();
    }

    $request = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'timeout' => 20,
    ];

    if ($body) {
      $request['body'] = Json::encode($body);
    }

    try {
      $response = $this->httpClient->request($method, $endpoint, $request);
      if ($response->getStatusCode() != 200) {
        $this->messenger->addError(t('@error', [
          '@error' => $response->getReasonPhrase(),
        ]));
        $this->logger->error('Request failed: @response.', [
          '@response' => $response->getReasonPhrase(),
        ]);
      }

      $this->logger->notice('Response from %endpoint: 200 @response.', [
        '%endpoint' => $endpoint,
        '@response' => $response->getReasonPhrase(),
      ]);
    }
    catch (RequestException | ServerException | ClientException | ConnectException $e) {
      $this->logger->error(t('@error', [
        '@error' => $e->getMessage(),
      ]));

      $this->messenger->addError(t('@error', [
        '@error' => $e->getMessage(),
      ]));
    }

    return $response;
  }

}
