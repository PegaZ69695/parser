<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 14.02.2017
 * Time: 13:23
 */

namespace Parser;


abstract class ParserFile extends ParserBase
{
    /*  Этап 1
     *  Получение списка продуктов и сохранение в бд
     * */
    public function getProductList()
    {
        $this->items = $this->findProductList();
        foreach ($this->items as $returnItem) {
            $this->provider->save(3, $returnItem['link'], $returnItem['categoryList'], $returnItem['data']);
        }
        $this->items = [];
        return $this;
    }

    /*
     *  Этап 1
     *  Получение списка продуктов и сохранение в бд
     * */
    abstract public function findProductList();
}