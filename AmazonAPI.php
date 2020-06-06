<?php
namespace vocalendar;

define('ACCESS_KEY_ID', getenv('ACCESS_KEY_ID'));
define('SECRET_KEY', getenv('SECRET_KEY'));
define('PARTNER_TAG', 'vocalendar-22');

/*
 * PAAPI host and region to which you want to send request
 * For more details refer: https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
 */

define('API_HOST', 'webservices.amazon.co.jp');
define('API_REGION', 'us-west-2');

define('API_LIMIT', 10);

use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SortBy;
use Amazon\ProductAdvertisingAPI\v1\Configuration;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/AmazonAPIException.php';

class AmazonAPI {
    // API config
    private $config;
    private $instance;

    public function __construct() {
        $this->config = new Configuration();

        $this->config->setAccessKey(ACCESS_KEY_ID);
        $this->config->setSecretKey(SECRET_KEY);

        $this->config->setHost(API_HOST);
        $this->config->setRegion(API_REGION);
    }

    private function getApiInstance() {
        if ($this->instance === null) {
            $this->instance = new DefaultApi(new \GuzzleHttp\Client(), $this->config);
        }
        return $this->instance;
    }

    // https://webservices.amazon.com/paapi5/documentation/search-items.html#resources-parameter
    private function getSearchResources() {
        return [
            SearchItemsResource::ITEM_INFOTITLE,
            SearchItemsResource::ITEM_INFOPRODUCT_INFO,
            SearchItemsResource::ITEM_INFOCONTENT_INFO,
            SearchItemsResource::ITEM_INFOFEATURES,
            SearchItemsResource::OFFERSLISTINGSPRICE,
        ];
    }

    // https://webservices.amazon.com/paapi5/documentation/search-items.html
    private function createSearchRequest($category, $keyword, $itemCount, $itemPage) {
        // APIで取得する項目
        $resources = $this->getSearchResources();

        // リクエストパラメータ作成
        $request = new SearchItemsRequest();
        $request->setSearchIndex($category);
        $request->setKeywords($keyword);
        $request->setItemCount($itemCount);
        $request->setItemPage($itemPage);
        $request->setSortBy('NewestArrivals');
        $request->setPartnerTag(PARTNER_TAG);
        $request->setPartnerType(PartnerType::ASSOCIATES);
        $request->setResources($resources);

        // リクエストのバリデート
        $invalids = $request->listInvalidProperties();
        if (count($invalids) > 0) {
            $message = 'parameter '.implode(',', $invalids).' is invalid.';
            throw new AmazonAPIException($message);
        }

        return $request;
    }

    public function searchItems($category, $keyword, $options = []) {
        $itemCount = $options['itemCount'] ?? API_LIMIT;
        $itemPage = $options['itemPage'] ?? 1;

        // APIインスタンス
        $instance = $this->getApiInstance();

        // リクエストパラメータ作成
        $request = $this->createSearchRequest($category, $keyword, $itemCount, $itemPage);

        $response = $instance->searchItems($request);

        if ($response->getErrors() != null) {
            $errors = $response->getErrors();
            $error = reset($errors);
            throw new AmazonAPIException($error->getMessage(), $error->getCode());
        }

        $results = [];
        if ($response->getSearchResult() != null) {
            $results = $response->getSearchResult()->getItems();
        }

        return $results;
    }
}