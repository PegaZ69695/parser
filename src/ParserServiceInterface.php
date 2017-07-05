<?php

namespace Parser;

interface ParserServiceInterface
{
    public function clearDb();

    /**
     * @param int $type
     * @param string $link
     * @param string $categoryList
     * @param null|array $data
     * @return void
     */
    public function save($type, $link, $categoryList, $data = null);

    /**
     * @param int $id
     * @param int $status
     * @return void
     */
    public function update($id, $status);

    /**
     * @param string $like_
     * @param int $type_
     * @param bool $active
     * @param null|int $limit
     * @return array
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
