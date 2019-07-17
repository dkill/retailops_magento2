<?php
namespace Gudtech\RetailOps\Ui\Component\Listing\Column\Queuecancelgrid;

/**
 * Page actions class.
 *
 */
class PageActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                $id = "X";
                if (isset($item["gudtech_retailops_queue_id"])) {
                    $id = $item["gudtech_retailops_queue_id"];
                }
                $item[$name]["view"] = [
                    "href"=>$this->getContext()->getUrl(
                        "adminhtml/queue_cancel_grid/viewlog",
                        ["id"=>$id]
                    ),
                    "label"=>__("Edit")
                ];
            }
        }

        return $dataSource;
    }
}
