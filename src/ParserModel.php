<?php
namespace Parser;

use RollingCurl\RollingCurl;
use RollingCurl\Request;
use Parser\DataProvider\DataProviderInterface;
use InvalidArgumentException;

/**
 *
 * @property DataProviderInterface $provider
 */
abstract class ParserModel
{
    public $items = [];
    protected $itemPosition = 0;
    protected $productsToAdd = [];
    protected $productsToUpdate = [];
    protected $provider;

    public $configDir = '../Config/';
    public $cacheDir = 'E:\OpenServer\domains\parser.dev\src\Parser\Cache/';
    
    public $currency = DB_CURRENCY;
    const PARSER_TYPE_CURL = 'curl';
    const PARSER_TYPE_DEFAULT = 'default';
    const STATUS_ACTIVE = 0;
    const STATUS_INACTIVE = 0;
    const PARSER_TYPE = 'default';

    const MAIN_CATEGORY_ID = 0;
    const SEARCH_STRING = null;

    const LOAD_PAGE_TO_ADD = true;
    const LOAD_PAGE_TO_UPDATE = false;

    public function __construct()
    {
        $this->setProvider($this->getProvider());
    }

    /**
     * @return \Parser\DataProvider\OpenCartDataProvider
     */
    protected function getProvider()
    {
        return new \Parser\DataProvider\OpenCartDataProvider();
    }

    protected function setProvider(DataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /*
     *  Этап 0
     *  Очистка базы
     * */
    public function clearDb(){
        $this->provider->clearDb();
        return $this;
    }

    /*
     *  Этап 1
     *  Получение ссылок для парсинга и сохранение в бд
     * */
    public function getDonorLinks()
    {
        $mainCategoryId = static::MAIN_CATEGORY_ID;
        $this->items = $this->findDonorList();
        foreach ($this->items as $returnItem) {
            if (isset($returnItem['categoryList'])) {
                $categoryList = "{$returnItem['category_id']},{$returnItem['parent_id']},$mainCategoryId";
                if ($returnItem['categoryList'] != '') {
                    $categoryList .= ",{$returnItem['categoryList']}";
                }
            } else {
                $categoryList = '';
            }
            $this->provider->save(1, $returnItem['link'], $categoryList);
        }
        $this->items = [];
        return $this;
    }

    /*
     *  Этап 2
     *  Получение ссылок на страницы категории и сохранение в бд
     * */
    public function getPagination($limit = null)
    {
        if (count(($this->items)) < 1) {
            $this->items = $this->provider->find(static::SEARCH_STRING, 1, self::STATUS_ACTIVE, $limit);
        }
        
        switch (static::PARSER_TYPE) {
            case self::PARSER_TYPE_DEFAULT:
                    /*   code */
                break;
            case self::PARSER_TYPE_CURL:
                $this->getCurlPages(false, $this->getCurlOptions(),
                    function(Request $request) {
                        $item = $request->getExtraInfo();
                        $returnItems = $this->findDonorPagination($request->getResponseText(), $item);
                        foreach ($returnItems as $returnItem) {
                            $this->provider->save(2, $returnItem['link'], $returnItem['categoryList'], $returnItem['data']);
                        }
                        $this->provider->update($item['id']);
                    });
                break;
            
        }
        return $this;
    }

    /*
     *  Этап 3
     *  Получение списка продуктов и сохранение в бд
     * */
    public function getProductList($limit = null)
    {
        if (count(($this->items)) < 1) {
            $this->items = $this->provider->find(static::SEARCH_STRING, 2, self::STATUS_ACTIVE, $limit);
        }

        switch (static::PARSER_TYPE) {
            case self::PARSER_TYPE_DEFAULT:
                /*   code */
                break;
            case self::PARSER_TYPE_CURL:
                $this->getCurlPages(false, $this->getCurlOptions(),
                    function(Request $request) {
                        $item = $request->getExtraInfo();
                        $returnItems = $this->findProductList($request->getResponseText(), $item);
                        foreach ($returnItems as $returnItem) {
                            $this->provider->save(3, $returnItem['link'], $returnItem['categoryList'], $returnItem['data']);
                        }
                        $this->provider->update($item['id']);
                        unset($item, $returnItems, $request);
                    });
                break;

        }
        return $this;
    }

    /*
     *  Этап 4
     *  Получение данных продукта и сохранение или обновление в бд
     * */
    public function getProduct($limit = null)
    {
        if (count(($this->items)) < 1) {
            $this->items = $this->provider->find(static::SEARCH_STRING, 3, self::STATUS_ACTIVE, $limit);
        }

        foreach ($this->items as $key => $item) {
            $productId = $this->provider->findProduct('sku', "ur_{$item['data']['sku']}");
            if($productId) {
                $item['productId'] = $productId;
                $this->productsToUpdate[] = $item;
            } else {
                $this->productsToAdd[] = $item;
            }
            unset($this->items[$key]);
        }

        foreach ($this->productsToUpdate as $key => $item) {
            if (static::LOAD_PAGE_TO_UPDATE) {

            } else {
                $this->updateProduct(null, $item);
            }

            unset($this->productsToUpdate[$key]);
        }

        foreach ($this->productsToAdd as $key => $item) {
            if (static::LOAD_PAGE_TO_ADD) {

            } else {
                $this->addProduct(null, $item);
            }
            unset($this->productsToAdd[$key]);
        }
//        switch (static::PARSER_TYPE) {
//            case self::PARSER_TYPE_DEFAULT:
//                /*   code */
//                break;
//            case self::PARSER_TYPE_CURL:
//                $this->getCurlPages($limit, $this->getCurlOptions(),
//                    function(Request $request, RollingCurl $rollingCurl) {
//                        $item = $request->getExtraInfo();
//                        $returnItems = $this->provider->findProductList($request->getResponseText(), $item);
//                        foreach ($returnItems as $returnItem) {
//                            $this->provider->save(2, $returnItem['link'], $returnItem['categoryList'], $returnItem['data']);
//                        }
//                        $this->provider->update($item['id']);
//                        unset($item, $returnItems, $request);
//                    });
//                break;
//
//        }
        return $this;
    }

    public static function recursive_array_search($needle, $haystack, $currentKey = '') {
        foreach($haystack as $key=>$value) {
            if (is_array($value)) {
                $nextKey = self::recursive_array_search($needle,$value, $currentKey . '[' . $key . ']');
                if ($nextKey) {
                    return $nextKey;
                }
            }
            else if($value==$needle) {
                return is_numeric($key) ? (int)$key : $key;
            }
        }
        return false;
    }
    
    public function getCurlPages($limit = false, $options = [], \Closure $closure)
    {
        if (!is_array($this->items)) {
            throw new InvalidArgumentException(sprintf('this items expects  to be array, %s given', gettype($this->items)));
        }

        $results = [];
        $postData = [];

        if (isset($options[CURLOPT_POSTFIELDS])) {
            $postData = $options[CURLOPT_POSTFIELDS];
            unset($options[CURLOPT_POSTFIELDS]);
        }

        $rollingCurl = new RollingCurl();

        foreach ($this->items as $item) {
            $request = new Request($item['link'], 'POST');
            unset($item['link']);
            $request->setPostData($postData);
            $request->setExtraInfo($item);
            $rollingCurl->add(
                $request->addOptions($options)
            );
        }

        $this->items = [];
        
        $rollingCurl->setCallback(function(Request $request, RollingCurl $rollingCurl) use (&$results, &$closure) {
            if (($request->getResponseText()['http_code'] !== 200) || !trim($request->getResponseText())) {
                throw new \RuntimeException($request->getResponseError());
            }
            if (!empty($closure)) {
                $closure($request, $rollingCurl);
            } else {
                $results[] = $request->getResponseText();
            }
            $rollingCurl->clearCompleted();
            $rollingCurl->prunePendingRequestQueue();
        });
        if ($limit) {
            $rollingCurl->setSimultaneousLimit($limit);
        }
        
        $rollingCurl->execute();

        return $results;
    }
    
    
    /*
     * Поиск всех ссылок категорий для парсинга
     * Этап 1
     * */
    abstract public function findDonorList();

    /*
     * Получение ссылок на страницы категории
     * Этап 2
     * */
    abstract public function findDonorPagination($htmlText, $item);

    /*
     *  Этап 3
     *  Получение списка продуктов и сохранение в бд
     * */
    abstract public function findProductList($htmlText, $item);

    /*
     *  Этап 4.add
     *  Получение данных продукта и сохранение в бд
     * */
    abstract public function addProduct($htmlText, $item);

    /*
     *  Этап 4.update
     *  Обновление данных продукта бд
     * */
    abstract public function updateProduct($htmlText, $item);


    public function getCurlOptions(){
        return [];
    }
}