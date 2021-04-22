<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\GridSource\GridSourceProcessorBuilder;
use Hyva\Admin\Model\GridSource\SearchCriteriaBindings;
use function array_merge as merge;
use Hyva\Admin\Model\GridSourceType\SourceTypeClassLocator;

use Magento\Framework\ObjectManagerInterface;

class HyvaGridSourceFactory
{
    /**
     * @var SourceTypeClassLocator
     */
    private $sourceTypeClassLocator;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GridSourceProcessorBuilder
     */
    private $gridSourceProcessorBuilder;

    public function __construct(
        ObjectManagerInterface $objectManager,
        SourceTypeClassLocator $sourceTypeClassLocator,
        GridSourceProcessorBuilder $gridSourceProcessorBuilder
    ) {
        $this->objectManager              = $objectManager;
        $this->sourceTypeClassLocator     = $sourceTypeClassLocator;
        $this->gridSourceProcessorBuilder = $gridSourceProcessorBuilder;
    }

    public function createFor(HyvaGridDefinitionInterface $gridDefinition): HyvaGridSourceInterface
    {
        $gridSourceConfiguration = $gridDefinition->getSourceConfig();

        if (empty($gridSourceConfiguration)) {
            $message = sprintf('No grid source configuration found for grid "%s"', $gridDefinition->getName());
            throw new \RuntimeException($message);
        }

        $sharedConstructorArguments = [
            'gridName'            => $gridDefinition->getName(),
            'sourceConfiguration' => $gridSourceConfiguration,
        ];
        $gridSourceType             = $this->objectManager->create(
            $this->sourceTypeClassLocator->getFor($gridDefinition->getName(), $gridSourceConfiguration),
            merge($sharedConstructorArguments, ['processors' => $this->buildProcessors($gridSourceConfiguration)])
        );
        $bindingsConfig             = $gridSourceConfiguration['defaultSearchCriteriaBindings'] ?? [];
        $searchCriteriaBindings     = $this->objectManager->create(
            SearchCriteriaBindings::class,
            merge(['bindingsConfig' => $bindingsConfig], $sharedConstructorArguments)
        );

        $dependencies = ['gridSourceType' => $gridSourceType, 'searchCriteriaBindings' => $searchCriteriaBindings];
        return $this->objectManager->create(
            HyvaGridSourceInterface::class,
            merge($dependencies, $sharedConstructorArguments)
        );
    }

    /**
     * @param array[] $gridSourceProcessorsConfig
     * @return HyvaGridSourceProcessorInterface[]
     */
    private function buildProcessors(array $gridSourceConfiguration): array
    {
        return $this->gridSourceProcessorBuilder->build($gridSourceConfiguration['processors'] ?? []);
    }
}
