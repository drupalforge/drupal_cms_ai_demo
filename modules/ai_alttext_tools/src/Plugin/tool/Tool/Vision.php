<?php

namespace Drupal\ai_alttext_tools\Plugin\tool\Tool;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the Vision tool.
 */
#[Tool(
  id: "vision",
  label: new TranslatableMarkup("AI Vision"),
  description: new TranslatableMarkup("Analyzes an image using Vision AI."),
  operation: ToolOperation::Explain,
  input_definitions: [
    'image_id' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Image File ID"),
      description: new TranslatableMarkup("The file ID of the image to analyze."),
      required: TRUE,
    ),
    'prompt' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Prompt"),
      description: new TranslatableMarkup("The prompt of how to look at the image and how to describe it."),
      required: FALSE,
    ),
    'extra_info' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Extra information"),
      description: new TranslatableMarkup("Additional context such as existing alt text or metadata to evaluate."),
      required: FALSE,
    ),
  ],
  output_definitions: [
    'vision_response' => new ContextDefinition('string'),
  ],
)]
class Vision extends ToolBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The ai provider manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected AiProviderPluginManager $aiProviderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->aiProviderManager = $container->get('ai.provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    try {
      $image_id = $values['image_id'];
      $prompt = $values['prompt'] ?? '';
      $extra_info = $values['extra_info'] ?? '';
      $file = $this->entityTypeManager
        ->getStorage('file')
        ->load($image_id);

      if (!$file) {
        throw new \InvalidArgumentException("File {$image_id} not found");
      }

      $response = $this->callVisionProvider($file, $prompt, $extra_info);

      return ExecutableResult::success(
        $this->t('Vision analysis completed.'),
        [
          'vision_response' => $response,
        ]
      );
    }
    catch (\Exception $e) {
      return ExecutableResult::failure(
        $this->t('Vision analysis failed: @msg', ['@msg' => $e->getMessage()])
      );
    }
  }

  /**
   * Analyze image using OpenAI Vision capabilities.
   */
  protected function callVisionProvider(FileInterface $file, string $prompt, string $extra_info = ''): string {

    // Tell AI provider to use chat vision mode.
    $default = $this->aiProviderManager->getDefaultProviderForOperationType('chat_with_image_vision');

    if (empty($default)) {
      throw new \RuntimeException("No AI provider configured for image vision.");
    }

    // Create provider instance.
    $provider = $this->aiProviderManager->createInstance($default['provider_id']);
    $image = new ImageFile();
    $image->setFileFromFile($file);
    $images = [$image];
    $final_prompt = $prompt . ($extra_info ? "\nAdditional information: " . $extra_info : '');
    // Build message with the image.
    $input = new ChatInput([
      new ChatMessage(
        'user',
        $final_prompt,
        $images,
      ),
    ]);

    // Call the provider.
    $response = $provider->chat(
      $input,
      $default['model_id'],
    );

    $vision_response = $response->getNormalized()->getText();

    if (!$vision_response) {
      throw new \RuntimeException("AI provider returned empty response.");
    }

    return $vision_response;
  }

  /**
   * Access control.
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $result = $account->hasPermission('view media')
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $return_as_object ? $result : $result->isAllowed();
  }

}