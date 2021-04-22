<?php declare(strict_types=1);

namespace Hyva\AdminProductGrid\HyvaGridProcessor;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface as CatalogProduct;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DB\Select;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_values as values;

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
    public function prepareLoad($select, SearchCriteriaInterface $searchCriteria, string $gridName): void
    {

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
     * @param Select $select
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $gridName
     */
    public function beforeLoad($select, SearchCriteriaInterface $searchCriteria, string $gridName): void
    {

        $this->addAttributeIdBindings($select);

        if ($websiteFilter = $this->extractWebsiteFilter($searchCriteria)) {
            $this->applyWebsiteFilterToWebsiteJoinCondition($select, $websiteFilter);
            $where = filter($select->getPart(Select::WHERE), function ($condition): bool {
                return strpos((string) $condition, 'website_ids') === false;
            });
            $select->setPart(Select::WHERE, $where);
        }
    }

    private function extractWebsiteFilter(SearchCriteriaInterface $searchCriteria): ?Filter
    {
        // return first filter for website or null if there is no matching filter
        $allFilters = merge(...values(map(function (FilterGroup $filterGroup): array {
            return $filterGroup->getFilters();
        }, $searchCriteria->getFilterGroups())));

        $websiteFilters = values(filter($allFilters, function (Filter $filter): bool {
            return $filter->getField() === 'website_ids';
        }));

        return $websiteFilters[0] ?? null;
    }

    private function applyWebsiteFilterToWebsiteJoinCondition(Select $select, ?Filter $websiteFilter): void
    {
        $from              = $select->getPart(Select::FROM);
        $websiteJoin       = $from['t_website'] ?? [];
        $condition         = 't_website.website_id=' . ((int) $websiteFilter->getValue());
        $replacement       = [
            'joinType'      => Select::INNER_JOIN,
            'joinCondition' => $websiteJoin['joinCondition'] . ' AND ' . $condition,
        ];
        $from['t_website'] = merge($from['t_website'], $replacement);
        $select->setPart(Select::FROM, $from);
    }

    private function removeFilter(SearchCriteriaInterface $searchCriteria, Filter $websiteFilter): void
    {
        $groups = filter(map(function (FilterGroup $group) use ($websiteFilter): ?FilterGroup {
            $filters = values(filter($group->getFilters(), function (Filter $filter) use ($websiteFilter): bool {
                return $filter !== $websiteFilter;
            }));

            return $filters ? $group->setFilters($filters) : null;
        }, $searchCriteria->getFilterGroups()));
        $searchCriteria->setFilterGroups($groups);
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
