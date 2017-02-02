<?php
namespace Parser;

use DB;
use Parser\Product\ProductModel;

class OpenCartDataProvider implements DataProviderInterface
{

    public $db;

    public function __construct()
    {
        $this->db = $this->getDb();
    }

    public function tableName()
    {
        return 'parser_link';
    }

    private function getDb()
    {
        return new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    }

    public function clearDb()
    {
        return $this->db->query("TRUNCATE TABLE `{$this->tableName()}`");
    }

    public function getArrayCategoryIdByName($name){
        $category = null;
        $sql = "SELECT c.category_id, c.donor, c.parent_id, c.categorylist FROM ".DB_PREFIX."category as c
                LEFT JOIN ".DB_PREFIX."category_description as cd ON c.category_id = cd.category_id
                WHERE cd.name = '".$name."'";
        $query = $this->db->query($sql);
        if($query->num_rows){
            $category = $query->rows;

        }
        return $category;
    }

    public function save($type, $link, $categoryList, $data = null) {
        if (!is_array($data)){
            $data = '';
        }
        $data = $this->db->escape(serialize($data));
        return $this->db->query("INSERT INTO `{$this->tableName()}` (`link`, `data`, `categoryList`, `type`) VALUES ('$link', '{$data}', '$categoryList', '$type')");
    }

    public function update($id) {
        return $this->db->query("UPDATE `{$this->tableName()}` SET state = 1 WHERE id = '$id'");
    }

    public function find($like_, $type_, $active = 0, $limit = null)
    {
        $sql = "SELECT *
                FROM `{$this->tableName()}`
                WHERE `link` LIKE '%{$like_}%' AND `type` = '{$type_}' AND `state` = '$active'";
        if (is_int($limit)){
            $sql .= " LIMIT $limit";
        }
        $query = $this->db->query($sql);

        if (!empty($query->rows)) {
            return array_map(function ($item){
                $item['data'] = unserialize($item['data']);
                return $item;
            }, $query->rows);
        }
        return [];
    }
    
    public function findProduct($findField, $findValue)
    {
        $findString = "`$findField` = '{$this->db->escape($findValue)}'";
        $sql = "SELECT product_id FROM  " . DB_PREFIX . "product WHERE $findString";
        $query = $this->db->query($sql);
        return $query->row ? $query->row['product_id'] : false ;
    }

    public function insertProductToDB(ProductModel $product)
    {
        $language_id = 0;
        $image = is_null($product->getImage()) ? 'data/no_image.jpg' : $product->getImage();

        $productImage = null;
        if ($product->getImages()) {
            foreach ($product->getImages() as $key => $image) {
                $productImage[$key] = [
                    'image' => $image,
                    'sort_order' => $key
                ];
            }
        }

        $item = [
            'sku'                 => $product->getSku(),
            'model'               => $product->getSku(),
            'donor'               => $product->getDonor(),
            'quantity'            => 1000,
            'stock_status_id'     => 5,
            'manufacturer_id'     => $product->getManufacturer(),
            'status'              => $product->getStatus(),
            'upc'                 => '',
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '',
            'mpn'                 => '',
            'location'            => '',
            'subtract'            => 0,
            'date_available'      => date('Y-m-d H:i:s'),
            'date_modified'       => date('Y-m-d H:i:s'),
            'points'              => 0,
            'weight'              => '',
            'weight_class_id'     => 1,
            'length'              => 0,
            'width'               => 0,
            'length_class_id'     => 1,
            'tax_class_id'        => 0,
            'seo_h1'              => '',
            'shipping'            => 1,
            'height'              => '',
            'product_option'      => [
                //$option_color,
            ],
            'product_description' => [
                $language_id => [
                    'name'             => $product->getName(),
                    'meta_description' => '',
                    'meta_keyword'     => '',
                    'tag'              => '',
                    'seo_h1'           => '',
                    'seo_title'        => '',
                    // DESC
                    'description'      => $product->getDescription()
                ]
            ],
            'product_store'       => [0],
            // SORT ORDER
            'sort_order'          => $this->getProductSortOrder(),
            // TRANSLITE NAME
            'keyword'             => $this->str2url($product->getName()),
            // CATEGORIES
            'product_category'    => array_unique(explode(',', $product->getProductCategory())),
            'main_category_id'    => $product->getMainCategoryId(),
            // MINIMUM
            'minimum'             => $product->getMinimum(),
            // IMAGE
            'image'               => $image,
            'product_image'       => $productImage,
            // PRICE
            'price'               => $product->getPrice(),
            'brand'               => $product->getBrand(),
            'production'          => $product->getBrand(),
        ];
        
        return true;
    }

    public function updateProductToDB(ProductModel $product)
    {
        $productFields = ["p.price = '{$product->getPrice()}'"];
        $productDescriptionFields = [];
        $table = [ DB_PREFIX . 'product as p'];
        if ($product->getMinimum()) {
            $productFields[] = "p.minimum = '{$product->getMinimum()}'";
        }

        if ($product->getBrand()) {
            $productFields[] = "p.brand = '{$this->db->escape($product->getBrand())}'";
        }

        if ($product->getProduction()) {
            $productFields[] = "p.production = '{$this->db->escape($product->getProduction())}'";
        }

        if ($product->getDescription()) {
            $productDescriptionFields[] = "pd.description = '{$this->db->escape($product->getDescription())}'";
        }

        if (count($productDescriptionFields)) {
            $table[] = DB_PREFIX . 'product_description as pd';
        }

        $productFields[] = "p.date_modified = NOW()";
        $productFields[] = "p.status = {$product->getStatus()}";

        $table = implode(', ', $table);
        $updateFields = implode(', ', $productFields);
        if (count($productDescriptionFields)) {
            $updateFields .= ", " . implode(', ', $productDescriptionFields);
        }
        $query = "UPDATE {$table} SET $updateFields WHERE p.product_id = '{$product->getProductId()}'";
        if (count($productDescriptionFields)) {
            $query .= " AND p.product_id = pd.product_id";
        }
        $productCategory = $product->getProductCategory();
        
        $query = $this->db->query("SELECT GROUP_CONCAT(category_id SEPARATOR '_') as categorylist
               FROM `" . DB_PREFIX . "product_to_category`
               where product_id = '{$product->getProductId()}'");
        if (strrpos($query->row['categorylist'], '1399') !== false) {
            $productCategory .= ',1399';
        }
        
    }

    protected function updateProductToCategory($category_id, $product_id)
    {
        $result = false;
        if($category_id){
            $category_id = explode(',', $category_id);

            $this->db->query("DELETE FROM ".DB_PREFIX."product_to_category WHERE product_id = '".$product_id."'");
            foreach(array_unique($category_id) as $category){
                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET product_id = '".$product_id."', category_id = '".$category."'");
            }
            $result =  false;
        }
        return $result;
    }

    protected function getProductSortOrder()
    {
        // SORT ORDER
        $sql = "SELECT MAX( sort_order ) as sort_order FROM  ".DB_PREFIX."product";
        $query = $this->db->query($sql);
        return $query->row['sort_order'] + 1;
    }

    public function hasCategoryParent($category_id, $parent_id)
    {

        $sql = "SELECT parent_id, top 
            FROM " . DB_PREFIX . "category 
            WHERE category_id = ".$category_id." 
            LIMIT 1";
        $category = $this->db->query($sql);
        if ($category->num_rows > 0) {
            if (($category->row['parent_id'] == $parent_id) || $this->hasCategoryParent($category->row['parent_id'], $parent_id)) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    public function rus2translit($string) {
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }

    public function str2url($str){
        // переводим в транслит
        $str = $this->rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        // удаляем начальные и конечные '-'
        $str = trim($str, "-");
        return $str;
    }
}