<?php

namespace Gudtech\RetailOps\Model;

use \Magento\Framework\Model\AbstractModel;
use Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface;

/**
 * Ro Rics link UPC class.
 *
 */
class RoRicsLinkUpc extends AbstractModel implements RetailOpsRicsLinkByUpcInterface
{
    public function getRicsIntegrationId()
    {
        return parent::getData(self::RICS_ID);
    }

    public function getId()
    {
        return parent::getData(self::ID);
    }

    public function getUpc()
    {
        return parent::getData(self::UPC);
    }

    public function getRoUpc()
    {
        return parent::getData(self::RO_UPC);
    }

    public function getCreatedAt()
    {
        return parent::getData(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return parent::getData(self::UPDATED_AT);
    }

    /**
     * @param  bool $value
     */
    public function setRoUpc(bool $value)
    {
        //for using cascade index set to null
        if ($value === false) {
            return parent::setData(self::RO_UPC, null);
        }
        return parent::setData(self::RO_UPC, (int)$value);
    }

    protected function _construct()
    {
        parent::_init(\Gudtech\RetailOps\Model\ResourceModel\RoRicsLinkUpc::class);
    }
}
