<?php

namespace RetailOps\Api\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Ro Rics UPC link repository test class.
 *
 */
class RoRicsLinkUpcRepositoryTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Bootstrap::getObjectManager()->get(\Magento\Framework\App\AreaList::class)
            ->getArea('adminhtml')
            ->load(\Magento\Framework\App\Area::PART_CONFIG);
    }

    /**
     * @magentoDataFixture ../../../../app/code/RetailOps/Api/Test/Integration/_files/add_ro_link.php
     */
    public function testGetAllROUpcsByUpcs()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \RetailOps\Api\Model\RoRicsLinkUpcRepository $repository
         */
        $repository = $objectManager->create(\RetailOps\Api\Model\RoRicsLinkUpcRepository::class);
        $upcs = [
            '91209558430',
            '91209558433',
            '022859473118',
            '22859473118'
        ];
        $newUpcs = $repository->getAllROUpcsByUpcs($upcs);
        $this->assertEquals(4, $newUpcs->count());
    }
}
