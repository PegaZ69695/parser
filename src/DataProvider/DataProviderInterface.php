<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 30.01.2017
 * Time: 10:09
 */

namespace Parser\DataProvider;

use Parser\ParserBase;

interface DataProviderInterface
{
    public function clearDb();
    public function save($type, $link, $categoryList, $data = null);
    public function getArrayCategoryIdByName($name);
    public function update($id, $status = ParserBase::STATUS_INACTIVE);
    public function find($like_, $type_, $active = 0, $limit = null);
    public function findProduct($findField, $findValue);
    public function insertProductToDB($product);
    public function updateProductToDB($product);
    public function hasCategoryParent($category_id, $parent_id);
    public function cloneDonorMenu($menuItems = [], $parentId);
    public function disableOldProduct($vendorId, $days);
    public function getUrlsDonorCategories($searchString);
}