<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 05.07.2017
 * Time: 12:52
 */

namespace Parser;


class TransportValue
{
    /**
     * @var ParserItem
     */
    public $item;
    /**
     * @var string
     */
    public $responseText;

    /**
     * @param ParserItem $item
     * @param string $responseText
     * @return static
     */
    public static function create(ParserItem $item, $responseText)
    {
        $value = new static();
        $value->item = $item;
        $value->responseText = $responseText;
        return $value;
    }
}