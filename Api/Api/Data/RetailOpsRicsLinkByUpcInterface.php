<?php

namespace RetailOps\Api\Api\Data;

/**
 * Interface RetailOpsRicsLinkByUpcInterface
 *
 */
interface RetailOpsRicsLinkByUpcInterface
{
    const ID = 'entity_id';
    const RICS_ID = 'rics_integration_id';
    const UPC = 'upc';
    const RO_UPC = 'retail_ops_upc';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return string|null
     */
    public function getId();

    /**
     * @return string|null
     */
    public function getRicsIntegrationId();

    /**
     * @return string|null
     */
    public function getUpc();

    /**
     * @return string|null
     */
    public function getRoUpc();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param bool
     * @return string|null
     */
    public function setRoUpc(bool $flag);
}
