<?php declare(strict_types=1);

namespace Hyva\AdminProductGrid\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Ui\DataProvider\Product\AddQuantityAndStockStatusFieldToCollection;
use Magento\CatalogInventory\Ui\DataProvider\Product\AddQuantityFieldToCollection;
use Magento\Framework\Event\Observer as Event;
use Magento\Framework\Event\ObserverInterface;

class AddProductCollectionFields implements ObserverInterface
{

    /**
     * @var AddQuantityFieldToCollection
     */
    private $addQuantityFieldToCollection;

    /**
     * @var AddQuantityAndStockStatusFieldToCollection
     */
    private $addQuantityAndStockStatusFieldToCollection;

    public function __construct(
        AddQuantityFieldToCollection $addQuantityFieldToCollection,
        AddQuantityAndStockStatusFieldToCollection $addQuantityAndStockStatusFieldToCollection
    ) {
        $this->addQuantityFieldToCollection               = $addQuantityFieldToCollection;
        $this->addQuantityAndStockStatusFieldToCollection = $addQuantityAndStockStatusFieldToCollection;
    }

    public function execute(Event $event)
    {
        /** @var ProductCollection $collection */
        $collection = $event->getData('source');
        $this->addQuantityFieldToCollection->addField($collection, 'qty');
        //$this->addQuantityAndStockStatusFieldToCollection->addField($collection, 'quantity_and_stock_status');
    }
}
