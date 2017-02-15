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
abstract class ParserBase
{
    public $items = [];
    public $thisStatus = 0;
    protected $itemPosition = 0;
    protected $productsToAdd = [];
    protected $productsToUpdate = [];
    protected $provider;
    
    public $currency = 1;
    const PARSER_TYPE_CURL = 'curl';
    const PARSER_TYPE_DEFAULT = 'default';
    const STATUS_ACTIVE = 0;
    const STATUS_INACTIVE = 1;
    const STATUS_ERROR = 2;
    const PARSER_TYPE = 'default';

    const MAIN_CATEGORY_ID = 0;
    const SEARCH_STRING = null;

    const LOAD_PAGE_TO_ADD = true;
    const LOAD_PAGE_TO_UPDATE = false;

    public function __construct($config =  [])
    {
        if (!empty($config)) {
            self::configure($this, $config);
        }
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    protected function setCurrency($value = 1)
    {
        $this->currency = $value;
    }

    protected function getCurrency()
    {
        return $this->currency;
    }

    protected function setProvider(DataProviderInterface $provider)
    {
        $this->provider = $provider;
        return $this;
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
     *  Этап 4
     *  Получение данных продукта и сохранение или обновление в бд
     * */
    public function getProduct($limit = null)
    {
        $this->thisStatus = 0;
        if (!$this->items = $this->provider->find(static::SEARCH_STRING, 3, self::STATUS_ACTIVE, $limit)) {
            $this->thisStatus = 1;
            return $this;
        }

        foreach ($this->items as $key => $item) {
            $productId = $this->findProduct($item);
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
                $this->provider->update($item['id']);
            }

            unset($this->productsToUpdate[$key]);
        }

        foreach ($this->productsToAdd as $key => $item) {
            if (static::LOAD_PAGE_TO_ADD) {

            } else {
                $this->addProduct(null, $item);
                $this->provider->update($item['id']);
            }
            unset($this->productsToAdd[$key]);
        }
        return $this;
    }
    
    protected function findProduct($item)
    {
        return $this->provider->findProduct('sku', "{$item['data']['sku']}");
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

        foreach ($this->items as $key => $item) {
            $request = new Request($item['link'], 'POST');
            unset($item['link']);
            $request->setPostData($postData);
            $item['key'] = $key;
            $request->setExtraInfo($item);
            $rollingCurl->add(
                $request->addOptions($options)
            );
        }


        
        $rollingCurl->setCallback(function(Request $request, RollingCurl $rollingCurl) use (&$results, &$closure) {
            if ($request->getResponseInfo()['http_code'] >= 400) {
                throw new \RuntimeException(sprintf('Request URL:%s' . PHP_EOL . 'Status Code:%s', $request->getUrl(),  $request->getResponseInfo()['http_code']));
            }

            if (!empty($closure)) {
                $closure($request, $rollingCurl);
            } else {
                $results[] = $request->getResponseText();
            }
            unset($this->items[$request->getExtraInfo()['key']]);
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
    
    public function setMenu()
    {
        return null;
    }
}