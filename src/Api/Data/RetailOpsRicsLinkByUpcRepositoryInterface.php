<?php

namespace Gudtech\RetailOps\Api\Data;

/**
 * Interface RetailOpsRicsLinkByUpcRepositoryInterface
 *
 */
interface RetailOpsRicsLinkByUpcRepositoryInterface
{
    /**
     * Save model.
     *
     * @param \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface $link
     * @return \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface $link);

    /**
     * @param $id
     * @return $this
     */
    public function load($id);

    /**
     * @param $upc
     * @return \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface
     */
    public function getRoUpc($upc);

    /**
     * @param $upc
     * @return \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface
     */
    public function getAllUpcs($upc);

    /**
     * @param string $upc
     * @return void
     */
    public function setRoUpc($upc);
}
