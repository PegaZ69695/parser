<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 05.07.2017
 * Time: 12:55
 */

namespace Parser;


class ParserItem
{
    const STATUS_ACTIVE = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 2;

    public $id;
    public $type;
    public $link;
    public $categoryList;
    public $data;

    public $productId;

    /**
     * @param int $id
     * @param int $type
     * @param string $link
     * @param string $categoryList
     * @param array|null $data
     * @return static
     */
    public static function create($id, $type, $link, $categoryList, $data = null)
    {
        $item = new static();
        $item->id = $id;
        $item->type = $type;
        $item->link = $link;
        $item->categoryList = $categoryList;
        $item->data = $data;
        return $item;
    }
}