<?php
namespace Parser;


use RollingCurl\Request;
use RollingCurl\RollingCurl;

class CurlTransport implements TransportInterface
{
    public $options = [];
    private $cookiePath;

    /**
     * @param ParserItem $item
     * @return TransportValue
     */
    public function send(ParserItem $item)
    {
        $result = null;
        $postData = [];

        if (isset($this->options[CURLOPT_POSTFIELDS])) {
            $postData = $this->options[CURLOPT_POSTFIELDS];
            unset($this->options[CURLOPT_POSTFIELDS]);
        }

        $rollingCurl = new RollingCurl();

        $request = new Request($item->link, 'POST');
        $request->setPostData($postData);
        $request->setExtraInfo($item);
        $rollingCurl->add(
            $request->addOptions($this->options)
        );

        $rollingCurl->setCallback(function(Request $request, RollingCurl $rollingCurl) use (&$result) {
            if ($request->getResponseInfo()['http_code'] >= 400) {
                throw new \DomainException(sprintf('Request URL:%s' . PHP_EOL . 'Status Code:%s', $request->getUrl(),  $request->getResponseInfo()['http_code']));
            }
            $result = TransportValue::create($request->getExtraInfo(), $request->getResponseText());
            $rollingCurl->clearCompleted();
            $rollingCurl->prunePendingRequestQueue();
        })->execute();

        return $result;
    }

    /**
     * @param ParserItem[] $items
     * @return TransportValue[]
     */
    public function bathSend(array $items)
    {
        $results = [];
        $postData = [];

        if (isset($this->options[CURLOPT_POSTFIELDS])) {
            $postData = $this->options[CURLOPT_POSTFIELDS];
            unset($this->options[CURLOPT_POSTFIELDS]);
        }

        $rollingCurl = new RollingCurl();

        foreach ($items as $key => $item) {
            $request = new Request($item->link, 'POST');
            $request->setPostData($postData);
            $item['key'] = $key;
            $request->setExtraInfo($item);
            $rollingCurl->add(
                $request->addOptions($this->options)
            );
        }

        $rollingCurl->setCallback(function(Request $request, RollingCurl $rollingCurl) use (&$results) {
            if ($request->getResponseInfo()['http_code'] >= 400) {
                throw new \DomainException(sprintf('Request URL:%s' . PHP_EOL . 'Status Code:%s', $request->getUrl(),  $request->getResponseInfo()['http_code']));
            }

            $results[] = TransportValue::create($request->getExtraInfo(), $request->getResponseText());
            $rollingCurl->clearCompleted();
            $rollingCurl->prunePendingRequestQueue();
        });

        $rollingCurl->execute();

        return $results;
    }

    public function setCookie($url, $options = [])
    {
        $rollingCurl = new RollingCurl();
        if (!is_string($url)) {
            throw new \Exception('Need string url');
        }
        $request = new Request($url, 'POST');
        $options = $this->options + [
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
            throw new \RuntimeException($e->getMessage());
        }
        unset($rollingCurl);
        return $results;
    }

    public function setCookiePath($value)
    {
        $this->cookiePath = $value;
    }

    public function getCookiePath()
    {
        return $this->cookiePath;
    }
}