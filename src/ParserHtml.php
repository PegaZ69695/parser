<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 14.02.2017
 * Time: 13:23
 */

namespace Parser;

abstract class ParserHtml extends ParserBase
{
    /*
    *  Этап 1
    *  Получение ссылок для парсинга и сохранение в бд
    * */
    public function getDonorLinks()
    {
        foreach ($this->findDonorList() as $item) {
            $this->_service->save(1, $item);
        }
    }

    /**
     *  Этап 2
     *  Получение ссылок на страницы категории и сохранение в бд
     * @param null|int $limit
     * */
    public function getPagination($limit = null)
    {
        if (!$items = $this->_service->find($this->searchString, 1, ParserItem::STATUS_ACTIVE, $limit)) {
            $this->status = self::STATUS_SUCCESS;
            return;
        }

        foreach ($this->_transport->bathSend($items) as $transportValue) {
            foreach ($this->findDonorPagination($transportValue->responseText, $transportValue->item) as $item) {
                $this->_service->save(2, $item);
            }
            $this->_service->update($transportValue->item->id);
        }
    }

    /**
     *  Этап 3
     *  Получение списка продуктов и сохранение в бд
     * @param null|int $limit
     * */
    public function getProductList($limit = null)
    {
        if (!$items = $this->_service->find($this->searchString, 2, ParserItem::STATUS_ACTIVE, $limit)) {
            $this->status = self::STATUS_SUCCESS;
            return;
        }
        foreach ($this->_transport->bathSend($items) as $transportValue) {
            foreach ($this->findProductList($transportValue->responseText, $transportValue->item) as $item) {
                $this->_service->save(3, $item);
            }
            $this->_service->update($transportValue->item->id);
        }
    }

    /**
     * @return ParserItem[]
     */
    abstract public function findDonorList();

    /**
     * Получение ссылок на страницы категории
     * Этап 2
     * @param string $document
     * @param ParserItem $item
     * @return ParserItem[]
     * */
    abstract public function findDonorPagination($document, ParserItem $item);

    /**
     *  Этап 3
     *  Получение списка продуктов и сохранение в бд
     * @param string $document
     * @param ParserItem $item
     * @return ParserItem[]
     * */
    abstract public function findProductList($document, ParserItem $item);
}