<?php declare(strict_types=1);

namespace Hyva\AdminProductGrid\HyvaGridProcessor;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface as CatalogProduct;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DB\Select;

use function array_column as pick;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;

class ProductGridQueryProcessor implements HyvaGridSourceProcessorInterface
{
    private const GRID_ATTRIBUTES = ['name', 'thumbnail', 'visibility', 'status', 'price'];

    /**
     * @var EavConfig
     */
    private $eavConfig;

    public function __construct(EavConfig $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param Select $select
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     */
    public function beforeLoad($select, SearchCriteriaInterface $searchCriteria, string $gridName): void
    {
        $this->addAttributeIdBindings($select);
    }

    private function addAttributeIdBindings(Select $select): void
    {
        $bindings = reduce(self::GRID_ATTRIBUTES, function (array $bindings, string $code): array {
            $id = $this->eavConfig->getAttribute(CatalogProduct::ENTITY_TYPE_CODE, $code)->getId();
            return merge($bindings, [':' . $code . '_id' => $id]);
        }, []);

        $select->bind($bindings);
    }

    /**
     * @param mixed[] $rawResult Result format: ['data' => [...rows...], 'count' => n]
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     * @return mixed|void
     */
    public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, $gridName)
    {
        // null op
    }

}
