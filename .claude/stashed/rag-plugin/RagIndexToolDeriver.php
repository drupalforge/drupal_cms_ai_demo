<?php

namespace Drupal\flowdrop_ui_agents\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver to create a RAG tool for each Search API index.
 */
class RagIndexToolDeriver extends DeriverBase implements ContainerDeriverInterface
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a RagIndexToolDeriver object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivativeDefinitions($base_plugin_definition)
    {
        try {
            if (!$this->entityTypeManager->hasDefinition('search_api_index')) {
                return [];
            }

            $indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple();

            foreach ($indexes as $index) {
                if (!$index->status()) {
                    continue;
                }

                $derivative_id = $index->id();
                $this->derivatives[$derivative_id] = $base_plugin_definition;
                $this->derivatives[$derivative_id]['label'] = t('Search @index', ['@index' => $index->label()]);
                $this->derivatives[$derivative_id]['description'] = t('Search knowledge base: @index', ['@index' => $index->label()]);
                $this->derivatives[$derivative_id]['index_id'] = $index->id();
                $this->derivatives[$derivative_id]['category'] = 'Search';
            }
        } catch (\Exception $e) {
            // Ignore if search_api is not functional.
        }

        return $this->derivatives;
    }

}
