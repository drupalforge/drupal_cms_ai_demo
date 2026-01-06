<?php

namespace Drupal\flowdrop_ui_agents\Plugin\AiFunctionCall;

use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of a specific RAG Index Search.
 */
#[FunctionCall(
    id: 'flowdrop_rag_index',
    function_name: 'search_knowledge_base',
    name: 'Search Knowledge Base',
    description: 'Searches a specific knowledge base index.',
    group: 'information_tools',
    deriver: 'Drupal\flowdrop_ui_agents\Plugin\Deriver\RagIndexToolDeriver',
    context_definitions: [
        'search_string' => new ContextDefinition(
            data_type: 'string',
            label: new TranslatableMarkup("Search Query"),
            description: new TranslatableMarkup("The query string to search for."),
            required: TRUE,
        ),
        'amount' => new ContextDefinition(
            data_type: 'integer',
            label: new TranslatableMarkup("Amount"),
            description: new TranslatableMarkup("Number of results to return."),
            required: FALSE,
            default_value: 5,
        ),
    ],
)]
class RagIndexTool extends FunctionCallBase
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static
    {
        $instance = new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('ai.context_definition_normalizer')
        );
        $instance->entityTypeManager = $container->get('entity_type.manager');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $plugin_def = $this->getPluginDefinition();
        $index_id = $plugin_def['index_id'] ?? NULL;

        if (!$index_id) {
            throw new \Exception("No index ID defined for this RAG tool.");
        }

        $search_string = $this->getContextValue('search_string');
        $amount = $this->getContextValue('amount') ?? 5;

        try {
            /** @var \Drupal\search_api\Entity\Index $index */
            $index = $this->entityTypeManager->getStorage('search_api_index')->load($index_id);

            if (!$index) {
                $this->setOutput("The search index '$index_id' was not found.");
                return;
            }

            // Execute search query
            $query = $index->query(['limit' => $amount]);

            // Attempt to set option for chunks if supported by backend (like Solr/AI Search)
            $query->setOption('search_api_ai_get_chunks_result', TRUE);
            $query->keys($search_string);

            $results = $query->execute();
            $items = $results->getResultItems();

            if (empty($items)) {
                $this->setOutput("No results found in '$index_id' for: $search_string");
                return;
            }

            $output = "Results from '$index_id' for '$search_string':\n\n";
            $i = 1;

            foreach ($items as $item) {
                // Try to get chunk content, fallback to excerpt or rendered item
                $content = $item->getExtraData('content');

                if (!$content) {
                    // Fallback: try excerpt
                    if ($item->getExcerpt()) {
                        $content = $item->getExcerpt();
                    } else {
                        // Fallback: try to render fields?
                        // For now, if no content/excerpt, usage might be limited.
                        // But generally AI Search indexes populate 'content'.
                        $content = "Result #" . $item->getId();
                    }
                }

                $output .= "Result #$i:\n```\n" . $content . "\n```\n\n";
                $i++;
            }

            $this->setOutput($output);

        } catch (\Exception $e) {
            $this->setOutput("Error searching index '$index_id': " . $e->getMessage());
        }
    }

}
