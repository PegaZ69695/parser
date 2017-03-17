<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 01.02.2017
 * Time: 14:09
 */

namespace Parser\Product;


class ProductModel
{
    protected $productId;
    protected $price;
    protected $sku;
    protected $name;
    protected $description;
    protected $minimum;
    protected $image;
    protected $images;
    protected $manufacturerId;
    protected $status = 1;
    protected $productOption;
    protected $productCategory;
    protected $mainCategoryId;
    protected $brand;
    protected $production;
    protected $donor;
    

    public function setProductId($value)
    {
        $this->productId = $value;
        return $this;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setPrice($value = 0.00)
    {
        $value = $this->priceRound($value);
        $this->price = $value < 10.00 ? $value * 1.1 : $value;
        return $this;
    }

    protected function priceRound($value)
    {
        return ceil($value * 100) / 100;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setSku($value)
    {
        $this->sku = trim($value);
        return $this;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function setDonor($value)
    {
        $this->donor = trim($value);
        return $this;
    }

    public function getDonor()
    {
        return $this->donor;
    }

    public function setName($value)
    {
        $this->name = trim($value);
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($value)
    {
        $this->description = trim($value);
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setMinimum($value)
    {
        $this->minimum = $value;
        return $this;
    }

    public function getMinimum()
    {
        return $this->minimum;
    }

    public function setImages($value, $prefix_ = '', $subDir = '')
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('%s expects parameter 1 to be array, %s given', __METHOD__, gettype($value)));
        }

        if (count($value)) {
            $this->image = array_shift($value);
            $this->images = $value;
        }
        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setManufacturer($value)
    {
        $this->manufacturerId = $value;
        return $this;
    }

    public function getManufacturer()
    {
        return $this->manufacturerId;
    }

    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setProductOption($value)
    {
        $this->productOption = $value;
        return $this;
    }

    public function getProductOption()
    {
        return  $this->productOption;
    }

    public function setProductCategory($value)
    {
        $this->productCategory = $value;
        return $this;
    }

    public function getProductCategory()
    {
        return $this->productCategory;
    }

    public function setMainCategoryId($value)
    {
        $this->mainCategoryId = $value;
        return $this;
    }

    public function getMainCategoryId()
    {
        return $this->mainCategoryId;
    }


    public function setBrand($value)
    {
        $this->brand = $value;
        return $this;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setProduction($value)
    {
        $this->production = $value;
        return $this;
    }

    public function getProduction()
    {
        return $this->production;
    }
}