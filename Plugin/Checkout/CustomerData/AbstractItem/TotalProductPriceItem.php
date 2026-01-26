<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Plugin\Checkout\CustomerData\AbstractItem;

use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Checkout\Helper\Data;
use Magento\Quote\Model\Quote\Item;

class TotalProductPriceItem
{
    protected const string KEY = 'product_row_total';

    /**
     * @var Data
     */
    protected Data $dataHelper;

    public function __construct(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function afterGetItemData(AbstractItem $subject, array $result, Item $item): array
    {
        $result[self::KEY] = $this->dataHelper->formatPrice($item->getRowTotal());
        return $result;
    }
}
