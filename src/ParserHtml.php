<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 14.02.2017
 * Time: 13:23
 */

namespace Parser;

use RollingCurl\Request;
use RollingCurl\RollingCurl;

abstract class ParserHtml extends ParserBase
{
    protected $cookiePath;

    protected function setCookiePath($value)
    {
        $this->cookiePath = $value;
    }

    protected function getCookiePath()
    {
        return $this->cookiePath;
    }

    /*
    *  Этап 1
    *  Получение ссылок для парсинга и сохранение в бд
    * */
    public function getDonorLinks()
    {
        $items = $this->findDonorList();
        if (isset($items)) {
            $this->items = $items;
        }
        unset($items);

        foreach ($this->items as $returnItem) {
            $this->provider->save(1, $returnItem['link'], $returnItem['categoryList'], $returnItem['data']);
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
        $this->thisStatus = 0;
        if (!$this->items = $this->provider->find(static::SEARCH_STRING, 1, self::STATUS_ACTIVE, $limit)) {
            $this->thisStatus = 1;
            return $this;
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

    /* @TODO Этап 2 и 3 можно совместить подумать как!
     *  Этап 3
     *  Получение списка продуктов и сохранение в бд
     * */
    public function getProductList($limit = null)
    {
        $this->thisStatus = 0;
        if (!$this->items = $this->provider->find(static::SEARCH_STRING, 2, self::STATUS_ACTIVE, $limit)) {
            $this->thisStatus = 1;
            return $this;
        }

        switch (static::PARSER_TYPE) {
            case self::PARSER_TYPE_DEFAULT:
                /*   code */
                break;
            case self::PARSER_TYPE_CURL:
                $this->getCurlPages(false, $this->getCurlOptions(),
                    function(Request $request) {
                        $item = $request->getExtraInfo();

                        if (!trim($request->getResponseText())) {
                            $this->provider->update($item['id'], self::STATUS_ERROR);
                            unset($this->items[$request->getExtraInfo()['key']]);
                            throw new \RuntimeException(sprintf('Request URL:%s' . PHP_EOL . 'Status Code:%s' . PHP_EOL . 'Error text:%s', $request->getUrl(),  $request->getResponseInfo()['http_code'], $request->getResponseError()));
                        }

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

    public function setCookie($url, $options = [])
    {
        $results = false;

        $rollingCurl = new RollingCurl();
        if (!is_string($url)) {
            throw new \Exception('Need string url');
        }
        $request = new Request($url, 'POST');
        $options = $this->getCurlOptions() + [
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_COOKIEJAR => $this->getCookiePath()
        ] + $options;
        unset($options[CURLOPT_COOKIEFILE]);
        $request->setPostData($options[CURLOPT_POSTFIELDS]);
        $request->setOptions($options);
        $rollingCurl->add($request);
        unset($request);
        try {
            $rollingCurl->execute();
            $results = true;
        } catch (\Exception $e) {

        }
        unset($rollingCurl);
        return $results;
    }
}