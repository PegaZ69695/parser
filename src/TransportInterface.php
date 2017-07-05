<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 05.07.2017
 * Time: 12:46
 */

namespace Parser;


interface TransportInterface
{
    /**
     * @param ParserItem $item
     * @return TransportValue
     */
    public function send(ParserItem $item);
    /**
     * @param ParserItem[] $items
     * @return TransportValue[]
     */
    public function bathSend(array $items);
}