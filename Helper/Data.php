<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const MODULE_NAME = 'Nimasystems_AddMultipleProducts';

    const XML_PATH = 'nimasystems_add_multiple_products/settings/';

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ModuleListInterface
     */
    protected ModuleListInterface $moduleList;

    /**
     * @var Url
     */
    protected Url $urlHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param Url $urlHelper
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface   $moduleList,
        Url                   $urlHelper
    )
    {
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->urlHelper = $urlHelper;

        parent::__construct($context);
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return mixed
     */
    public function getStoreConfig(string $code, string $path = self::XML_PATH, int $storeId = null)
    {
        return $this->scopeConfig->getValue($path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return bool
     */
    public function getStoreConfigFlag(string $code, string $path = self::XML_PATH, int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return false|string[]
     */
    public function getStoreConfigArray(string $code, string $path = self::XML_PATH, int $storeId = null)
    {
        return explode(',', $this->scopeConfig->getValue(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        ));
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->moduleList
                   ->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function isModuleActive(): bool
    {
        return $this->getStoreConfigFlag('enabled');
    }

    public function getMaximumQuantity(): ?int
    {
        return $this->getStoreConfig('maximum_qty') ? (int)$this->getStoreConfig('maximum_qty') : null;
    }
}
