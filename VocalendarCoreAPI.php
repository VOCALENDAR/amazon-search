<?php

namespace vocalendar;

define('API_URL', 'https://vocalendar.jp/core/events.json');
define('AMAZON_SEARCH_CORE_USER_AGENT', 'Vocalendar Amazon Search/1.0');

ini_set('user_agent', AMAZON_SEARCH_CORE_USER_AGENT);

/**
 * Vocalendar Core Api class
 *
 * Core API IF
 */
class VocalendarCoreAPI
{
    /**
     * request to core
     *
     * @param array $params query parameters
     * @return string responce body
     */
    public function request(array $params): string
    {
        $url = API_URL;
        if (!empty($params)) {
            $url .=  '?' . http_build_query($params);
        }

        $result = file_get_contents($url);
        if ($result === false) {
            throw new VocalendarCoreAPIException('Vocalendar Core APIのリクエストに失敗しました。');
        }

        return $result;
    }

    /**
     * Json to Event Object Array
     *
     * @param string $json json string (from core responce)
     * @return array event object array
     */
    public function jsonToEvents(string $json)
    {
        $jsonObject = json_decode($json, true);

        if (!is_array($jsonObject)) {
            throw new VocalendarCoreAPIException('イベントJSONからイベントオブジェクトへの変換で失敗しました。');
        }

        $events = [];
        foreach ($jsonObject as $index => $data) {
            $events[$index] = new VocalendarCoreEvent($data);
        }
        return $events;
    }

    /**
     * get events from core
     *
     * @param array $params query parameters
     * @return array responce events
     */
    public function get(array $params): array
    {
        $responce = $this->request($params);

        $result = $this->jsonToEvents($responce);

        return $result;
    }

    /**
     * get by search query string
     *
     * @param string $query search query string
     * @return VocalendarCoreEvent|null
     */
    public function getBySearchQuery(string $query, $limit = 1)
    {
        $params = [
            'q' => $query,
            'limit' => $limit,
        ];

        /** @var array<int, VocalendarCoreEvent> */
        $results = $this->get($params);

        if (count($results) >= 1) {
            return reset($results);
        }

        return null;
    }
}
