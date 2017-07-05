<?php
namespace Parser;

abstract class ParserFile extends ParserBase
{
    /*  Этап 1
     *  Получение списка продуктов и сохранение в бд
     * */
    public function getProductList()
    {
        foreach ($this->findProductList() as $item) {
            $this->_service->save(3, $item);
        }
    }

    /**
     *  Этап 1
     *  Получение списка продуктов и сохранение в бд
     * @return ParserItem[]
     * */
    abstract public function findProductList();
}