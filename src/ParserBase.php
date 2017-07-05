<?php
namespace Parser;

use Parser\Product\ProductModel;

abstract class ParserBase
{
    const STATUS_ACTIVE = 0;
    const STATUS_SUCCESS = 1;

    public $searchString;
    public $downloadPageWhenAdding = true;
    public $downloadPageWhenUpdate = false;
    public $status = self::STATUS_ACTIVE;

    protected $_service;
    protected $_transport;

    public function __construct(ParserServiceInterface $service, TransportInterface $transport)
    {
        $this->_service = $service;
        $this->_transport = $transport;
    }

    /*
     *  Этап 0
     *  Очистка базы
     * */
    public function clearDb(){
        $this->_service->clearDb();
        return $this;
    }


    /**
     * @param null|null $limit
     */
    public function getProduct($limit = null)
    {
        if (!$items = $this->_service->find($this->searchString, 3, ParserItem::STATUS_ACTIVE, $limit)) {
            $this->status = self::STATUS_SUCCESS;
            return;
        }

        $addProducts = [];
        $updateProducts = [];

        foreach ($items as $key => $item) {
            $productId = $this->findProduct($item);
            if ($productId) {
                $item->productId = $productId;
                $updateProducts[] = $item;
            } else {
                $addProducts[] = $item;
            }
        }
        unset($items);

        $this->addAndUpdateProduct($updateProducts, $addProducts);
    }

    /**
     * @param ParserItem[] $updateProducts
     * @param ParserItem[] $addProducts
     */
    private function addAndUpdateProduct($updateProducts, $addProducts)
    {
        if ($this->downloadPageWhenUpdate) {
            foreach ($this->_transport->bathSend($updateProducts) as $transportValue) {
                $product = $this->updateProduct($transportValue->responseText, $transportValue->item);
                $this->_service->updateProduct($product);
                $this->_service->update($transportValue->item->id);
            }
        } else {
            foreach ($updateProducts as $item) {
                $product = $this->updateProduct(null, $item);
                $this->_service->updateProduct($product);
                $this->_service->update($item->id);
            }
        }
        unset($updateProducts);

        if ($this->downloadPageWhenAdding) {
            foreach ($this->_transport->bathSend($addProducts) as $transportValue) {
                $product = $this->addProduct($transportValue->responseText, $transportValue->item);
                $this->_service->addProduct($product);
                $this->_service->update($transportValue->item->id);
            }
        } else {
            foreach ($addProducts as $item) {
                $product = $this->updateProduct(null, $item);
                $this->_service->updateProduct($product);
                $this->_service->update($item->id);
            }
        }
        unset($addProducts);
    }

    protected function findProduct(ParserItem $item)
    {
        return $this->_service->findProduct(['sku' => $item->data['sku']]);
    }

    /**
     * @param string $document
     * @param ParserItem $item
     * @return ProductModel
     */
    abstract public function addProduct($document, $item);

    /**
     * @param string $document
     * @param ParserItem $item
     * @return ProductModel
     */
    abstract public function updateProduct($document, $item);

    /**
     * Отключение продуктов каторые не были обновлены в течении $day дней
     *
     * @param integer $days
     * @param integer $vendorId
     * @return boolean
     * */
    public function disableOldProduct($vendorId, $days = 1)
    {
        return $this->_service->disableOldProduct($vendorId, $days);
    }

    /**
     * @return array
     */
    public function getCurlOptions(){
        return [];
    }
    
    public function setMenu()
    {
        return null;
    }
}