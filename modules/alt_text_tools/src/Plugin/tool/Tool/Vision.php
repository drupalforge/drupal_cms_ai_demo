<?php

namespace Drupal\alt_text_tools\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\file\FileInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the Vision tool.
 */
#[Tool(
  id: "vision_tool",
  label: new TranslatableMarkup("Vision Image Analyzer"),
  description: new TranslatableMarkup("Analyzes an image using Vision AI."),
  operation: ToolOperation::Explain,
  input_definitions: [
    'image_id' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Image File ID"),
      description: new TranslatableMarkup("The file ID of the image to analyze."),
      required: TRUE,
    ),
    'current_alt_text' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Current Alt Text"),
      description: new TranslatableMarkup("The current alt text of the image, if any."),
      required: FALSE,
    ),
  ],
  output_definitions: [
    'alt_text_score' => new ContextDefinition('integer'),
    'recommended_alt_text' => new ContextDefinition('string'),
    'alt_text_explanation' => new ContextDefinition('string'),
    'media_id' => new ContextDefinition('integer'),
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
      $current_alt = $values['current_alt_text'] ?? '';

      $file = $this->entityTypeManager
        ->getStorage('file')
        ->load($image_id);

      if (!$file) {
        throw new \InvalidArgumentException("File {$image_id} not found");
      }

      $result = $this->analyzeImageWithOpenAI($file, $current_alt);

      return ExecutableResult::success(
        $this->t('Image analyzed successfully.'),
        [
          'alt_text_score' => $result['score'],
          'recommended_alt_text' => $result['recommended_alt_text'] ?? '',
          'alt_text_explanation' => $result['explanation'] ?? '',
          'media_id' => $image_id,
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
  protected function analyzeImageWithOpenAI(FileInterface $file, string $current_alt): array {
    $prompt = $this->buildVisionPrompt($current_alt);

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
    // Build message with the image.
    $input = new ChatInput([
      new ChatMessage(
        'user',
        $prompt,
        $images,
      ),
    ]);

    // Call the provider.
    $response = $provider->chat(
      $input,
      $default['model_id'],
    );


    $vision_text = $response->getNormalized()->getText();

    if (!$vision_text) {
      throw new \RuntimeException("AI provider returned empty response for image {$file->id()}.");
    }

    // Try to extract JSON from markdown code blocks if present
    $json_text = $vision_text;
    if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $vision_text, $matches)) {
      $json_text = $matches[1];
    }

    $data = json_decode($json_text, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException("AI response is not valid JSON for image {$file->id()}. Error: " . json_last_error_msg() . ". Response: " . substr($vision_text, 0, 200));
    }

    // Validate required fields
    $required_fields = ['score', 'decision', 'recommended_alt_text', 'explanation'];
    $missing_fields = array_diff($required_fields, array_keys($data));
    if (!empty($missing_fields)) {
      throw new \RuntimeException("AI response missing required fields for image {$file->id()}: " . implode(', ', $missing_fields) . ". Response: " . substr($vision_text, 0, 200));
    }

    return $data;
  }


  /**
   * Access control
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|\Drupal\Core\Access\AccessResultInterface {
    $result = $account->hasPermission('view media')
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Build OpenAI Vision prompt based on analysis mode.
   */
  protected function buildVisionPrompt(string $current_alt = ''): string {
    return "You are a Media Inspector Agent that inspects media images and their alt text to ensure they follow EU guidelines.

Your Responsibilities:
1. Look at the image content.
2. Understand the page context (if provided).
3. Review the current alt text.
4. Decide whether the alt text is correct, missing, meaningless, or needs improvement.
5. Score based on EU rules.

Scoring criteria:
10: Alt text perfectly describes the image content and context
7-9: Alt text is mostly accurate but missing some details
4-6: Alt text is partially correct but has significant gaps
1-3: Alt text poorly describes the actual image
0: Alt text is completely inaccurate, generic (e.g., \"image\", \"photo\"), or missing

EU Evaluation Rules:
1. If the image is decorative, return alt=\"\"
2. If the image is informative, give a short meaningful description (under 125 characters).
3. Describe only what is important in context, not every visual detail.
4. If the image is functional (button, link, control), describe its action or destination.
5. Do not describe the visual appearance of functional images.
6. If the icon has meaning (warning, info, download), return the icon's meaning as alt text.
7. Do not start with \"image of\", \"picture of\", or similar.
8. Do not include phrases like \"click here\" or \"link to\".
9. Do not repeat text that is already present near the image.
10. Use the same language as the surrounding content.
11. Keep alt text concise and clear.
12. For charts or diagrams, give a short summary of the main message.
13. SVGs follow the same rules and may use <title> or aria labels when embedded.

Current Alt Text: " . ($current_alt ?: '(empty)') . "

CRITICAL: You MUST respond with ONLY a valid JSON object. Do not include any text before or after the JSON. Do not wrap it in markdown code blocks. The JSON must be parseable and contain ALL required fields.

Required JSON format:
{
  \"is_decorative\": true,
  \"score\": 8,
  \"decision\": \"pass\",
  \"recommended_alt_text\": \"Example alt text here\",
  \"explanation\": \"Brief explanation of the score\"
}

Required fields (all must be present):
- is_decorative (boolean): true if image is purely decorative
- score (integer 0-10): Quality score of current alt text
- decision (string): Must be \"pass\", \"needs_improvement\", or \"fail\"
- recommended_alt_text (string): Suggested alt text (empty string if decorative)
- explanation (string): Brief reason for the score";
  }
}