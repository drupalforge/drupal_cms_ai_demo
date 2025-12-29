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
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Tool(
  id: "entity_moderation_tool",
  label: new TranslatableMarkup("Entity Moderation Tool"),
  description: new TranslatableMarkup("Updates Media with AI score, explanation and moderation state."),
  operation: ToolOperation::Write,
  input_definitions: [
    'media_id' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Media ID"),
      description: new TranslatableMarkup("The ID of the media entity to moderate."),
      required: TRUE
    ),
    'alt_score' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup("Alt Score"),
      description: new TranslatableMarkup("Score from 0 to 10"),
      required: FALSE
    ),
    'alt_explanation' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Alt Text Explanation"),
      description: new TranslatableMarkup("Explanation for the alt text score."),
      required: FALSE
    ),
    'moderation_state' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup("Moderation State"),
      description: new TranslatableMarkup("Example: draft, published, archived, needs_review"),
      required: TRUE
    ),
  ],
  output_definitions: [
    'status' => new ContextDefinition('string'),
  ],
)]
class EntityModeration extends ToolBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  protected function doExecute(array $values): ExecutableResult {
    try {
      $media_id = $values['media_id'];
      $score = $values['alt_score'];
      $explanation = $values['alt_explanation'];
      $moderation_state = $values['moderation_state'];

      $media = $this->entityTypeManager->getStorage('media')->load($media_id);

      if (!$media) {
        throw new \Exception("Media not found: $media_id");
      }

      // Update score
      if ($media->hasField('field_alt_text_score')) {
        $media->set('field_alt_text_score', $score);
      }

      // Update explanation
      if ($media->hasField('field_ai_evaluation_explanation')) {
        $media->set('field_ai_evaluation_explanation', $explanation);
      }

      // Apply moderation state (only if moderation enabled)
      if ($media->hasField('moderation_state')) {
        $media->set('moderation_state', $moderation_state);
      }

      $media->save();

      return ExecutableResult::success(
        $this->t("Media updated successfully."),
        [
          'status' => "Media $media_id updated and moved to $moderation_state"
        ]
      );
    }
    catch (\Exception $e) {
      return ExecutableResult::failure(
        $this->t('Moderation failed'),
        ['status' => 'Moderation failed: ' . $e->getMessage()]
      );
    }
  }

  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|\Drupal\Core\Access\AccessResultInterface{
    $result = $account->hasPermission('update media')
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $return_as_object ? $result : $result->isAllowed();
  }

}
