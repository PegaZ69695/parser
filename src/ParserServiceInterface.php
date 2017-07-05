<?php

namespace Parser;

interface ParserServiceInterface
{
    public function clearDb();

    /**
     * @param int $type
     * @param ParserItem $item
     * @return void
     */
    public function save($type, ParserItem $item);

    /**
     * @param int $id
     * @param int $status
     * @return void
     */
    public function update($id, $status = ParserItem::STATUS_SUCCESS);

    /**
     * @param string $like_
     * @param int $type_
     * @param bool $active
     * @param null|int $limit
     * @return ParserItem[]
     */
    public function find($like_, $type_, $active = false, $limit = null);
    /**
     * @param string|array $criteria
     * @return mixed
     */
    public function findProduct($criteria);

    public function addProduct($product);
    public function updateProduct($product);

    public function disableOldProduct($vendorId, $days);
    public function getUrlsDonorCategories($searchString);
}
