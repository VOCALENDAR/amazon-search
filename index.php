<?php

namespace vocalendar;

use Exception;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/AmazonAPI.php';
require_once __DIR__ . '/VocalendarCoreAPI.php';
require_once __DIR__ . '/VocalendarCoreEvent.php';

// https://webservices.amazon.com/paapi5/documentation/locale-reference/japan.html
const CATEGORY_MUSIC   = "Music";
const CATEGORY_BOOK    = "Books";
const CATEGORY_HOBBIES = "Hobbies";
const CATEGORY_TOYS    = "Toys";
const CATEGORY_ALL     = "All";

const CATEGORIES = [
    CATEGORY_MUSIC,
    CATEGORY_BOOK,
    CATEGORY_HOBBIES,
    CATEGORY_TOYS,
    CATEGORY_ALL,
];

# https://webservices.amazon.com/paapi5/documentation/search-items.html#merchant-parameter
const MERCHANT_ALL    = "All";
const MERCHANT_AMAZON = "Amazon";

const MERCHANTS = [
    MERCHANT_ALL,
    MERCHANT_AMAZON,
];

const DEFAULT_KEYWORDS = [
    '初音ミク',
    '鏡音リン',
    '鏡音レン',
    '巡音ルカ',
    '東北ずん子',
    '重音テト',
    'ボーカロイド',
    'vocaloid',
    'ボカロP',
];
const SEARCH_TYPE_AND = 'AND';
const SEARCH_TYPE_OR  = 'OR';
const SEARCH_TYPE_LIST = [
    SEARCH_TYPE_AND,
    SEARCH_TYPE_OR,
];
const GOOGLE_CALENDAR_URL = "http://www.google.com/calendar/event";
const CALENDAR_DEFAULT_PARAMETER = [
    'action' => 'TEMPLATE',
];

try {
    $category   = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? CATEGORIES[0];
    $keyword    = filter_input(INPUT_GET, 'keyword',  FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? implode('|', DEFAULT_KEYWORDS);
    $searchType = filter_input(INPUT_GET, 'search_type',  FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? SEARCH_TYPE_AND;
    $page       = (int)(filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT) ?? 1);
    $merchant   = filter_input(INPUT_GET, 'merchant',  FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? MERCHANTS[0];

    $searchKeyword = str_replace('　', ' ', $keyword);
    if ($searchType == SEARCH_TYPE_OR) {
        $searchKeyword = str_replace(' ', '|', $searchKeyword);
    }

    $api = new AmazonAPI();
    $results = $api->searchItems($category, $searchKeyword, ['itemPage' => $page, 'merchant' => $merchant]);

    $core = new VocalendarCoreAPI();
    foreach ($results as $result) {
        $asin = $result->getASIN();
        $result->event = $core->getBySearchQuery($asin);
    }
} catch (Exception $e) {
    $results = [];
    echo "Error Type: ", $e->getCode(), PHP_EOL;
    echo "Error Message: ", $e->getMessage(), PHP_EOL;
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <title>Amazon API</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="amazon_api.css">
</head>

<body>

    <section id="searchOptions">

        <form method="GET">
            <div>
                <input type="text" name="keyword" value="<?= h($keyword) ?>"><br>
                ※スペースをANDかORで検索するかを選べます。(AND検索を指定しても|はORで検索します。)
                <ul id="searchTypeSelector">
                    <?php foreach (SEARCH_TYPE_LIST as $searchTypeItem) : ?>
                        <li class="">
                            <label><input type="radio" name="search_type" value="<?= h($searchTypeItem) ?>" <?= ($searchType == $searchTypeItem) ? 'checked' : '' ?> /><?= h($searchTypeItem) ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <label>カテゴリ</label>
                <ul id="categorySelector">
                    <?php foreach (CATEGORIES as $caItem) : ?>
                        <li class="<?= ($category == $caItem) ? 'active' : '' ?>">
                            <label><input type="radio" name="category" value="<?= h($caItem) ?>" <?= ($category == $caItem) ? 'checked' : '' ?> /><?= h($caItem) ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <label>出品者</label>
                <ul id="merchantSelector">
                    <?php foreach (MERCHANTS as $mcItem) : ?>
                        <li class="<?= ($merchant == $mcItem) ? 'active' : '' ?>">
                            <label><input type="radio" name="merchant" value="<?= h($mcItem) ?>" <?= ($merchant == $mcItem) ? 'checked' : '' ?> /><?= h($mcItem) ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <input type="submit" value="検索">
            </div>
        </form>

        <div class="pager">
            <label>pager</label>
            <?php $base_url = h('./?' . http_build_query(compact(['category', 'keyword', 'merchant',]))); ?>
            <?php if ($page > 2) : ?>
                <a href="<?= $base_url ?>&page=1">first</a>
            <?php endif; ?>
            <?php if (1 < $page && $page <= 10) : ?>
                <a href="<?= $base_url ?>&page=<?= $page - 1 ?>">prev</a>
            <?php endif; ?>
            <?php if (0 < $page && $page < 10) : ?>
                <a href="<?= $base_url ?>&page=<?= $page + 1 ?>">next</a>
            <?php endif; ?>
            <?php if ($page < 10) : ?>
                <a href="<?= $base_url ?>&page=10">last</a>
            <?php endif; ?>
            <p>※Amazon APIは100件まで（10ページまで）に制限されました。</p>
        </div>

    </section>
    <h3>Page: <?= $page ?></h3>
    <ul id='SearchResult'>
        <?php foreach ($results as $result) : ?>
            <?php
            $ASIN = $result->getASIN();
            $URL = $result->getDetailPageUrl();
            $event = $result->event;
            $Title = "";
            $Date = null;
            $FormattedDate = "";
            $GoogleFormatDate = "";
            $Price = "";
            $FeatureText = "";
            $itemInfo = $result->getItemInfo();
            if ($itemInfo !== null) {
                $Title = $itemInfo->getTitle()->getDisplayValue();
                if ($category === "Books") {
                    $ContentInfo = $itemInfo->getContentInfo();
                    if ($ContentInfo !== null) {
                        $PublicationDate = $ContentInfo->getPublicationDate();
                        if ($PublicationDate !== null) {
                            $PublicationDate = $PublicationDate->getDisplayValue();
                            $Date = new \DateTimeImmutable($PublicationDate);
                        }
                    }
                } else {
                    $ProductInfo = $itemInfo->getProductInfo();
                    if ($ProductInfo !== null) {
                        $ReleaseDate = $ProductInfo->getReleaseDate();
                        if ($ReleaseDate !== null) {
                            $ReleaseDate = $ReleaseDate->getDisplayValue();
                            $Date = new \DateTimeImmutable($ReleaseDate);
                        }
                    }
                }
                // todo : 商品説明が取れない
                // $Features = $itemInfo->getFeatures();
                // if ($Features !== null) {
                //     $FeatureText = implode("\n", $Features->getDisplayValues());
                // }
            }
            if ($Date !== null) {
                $FormattedDate = $Date->format('Y/m/d');
                $GoogleFormatDate = $Date->format('Ymd') . '/' . $Date->modify('+1 day')->format('Ymd'); // 終日1日のみの予定
            }
            foreach ($result->getOffers()->getListings() as $Offer) {
                $Price = $Offer->getPrice()->getDisplayAmount();
                break;
            }
            $GoogleCalendarDetails = $Title . "\n" . $URL . "\n";
            // if ($FeatureText) {
            //     $GoogleCalendarDetails .= "\n".$FeatureText."\n";
            // }
            $CategoryText = "xxx";
            switch ($category) {
                case CATEGORY_MUSIC:
                    $CategoryText = "CD";
                    $Categories = [];
                    if (strpos($Title, "CD") !== false) {
                        $Categories[] = "CD";
                    }
                    if (
                        strpos($Title, "DVD") !== false &&
                        strpos($Title, "DVD付") === false
                    ) {
                        $Categories[] = "DVD";
                    }
                    if (
                        strpos($Title, "Blu-ray") !== false ||
                        strpos($Title, "BD") !== false
                    ) {
                        $Categories[] = "BD";
                    }
                    if (!empty($Categories)) {
                        $CategoryText = implode("/", $Categories);
                    }
                    break;
                case CATEGORY_BOOK:
                    $CategoryText = "書籍";
                    break;
                case CATEGORY_HOBBIES:
                    $CategoryText = "グッズ";
                    if (
                        strpos($Title, "フィギュア") !== false ||
                        strpos($Title, "ねんどろいど") !== false
                    ) {
                        $CategoryText = "FIG";
                    }
                    break;
                case CATEGORY_TOYS:
                    $CategoryText = "グッズ";
                    if (
                        strpos($Title, "フィギュア") !== false ||
                        strpos($Title, "ねんどろいど") !== false
                    ) {
                        $CategoryText = "FIG";
                    }
                    break;
                case CATEGORY_ALL:
                default:
                    $CategoryText = "xxx";
                    break;
            }
            $CategoryText = "【" . $CategoryText . "】";


            $GoogleCalendarParams = CALENDAR_DEFAULT_PARAMETER + [
                'text' => $CategoryText . $Title,
                'details' => $GoogleCalendarDetails,
            ];
            if ($GoogleFormatDate) {
                $GoogleCalendarParams['dates'] = $GoogleFormatDate;
            }
            $GoogleCalendarUrl = GOOGLE_CALENDAR_URL . '?' . http_build_query($GoogleCalendarParams);
            ?>
            <li>
                <p class='ReleaseDate'><?= h($FormattedDate) ?></p>
                <p class='Title'>
                    [<a href='<?= h($URL) ?>' target='_blank'>Link</a>]
                    [<a href='<?= h($GoogleCalendarUrl) ?>' target='_blank'>登録</a>]
                    [<?php if (is_null($event)): ?>未登録<?php else: ?>登録済み<?php endif; ?>]
                    <?= h($Title) ?>
                </p>
                <p class="Url"><?= h($URL) ?></p>
            </li>
        <?php endforeach; ?>
        <?php if (empty($results)) : ?>
            <?php $messages = $api->getMessages(); ?>
            <?php foreach ($messages as $message) : ?>
                <p><?= nl2br(h($message)) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

</body>

</html>