<?php
namespace vocalendar;

use Exception;

require_once __DIR__.'/functions.php';
require_once __DIR__.'/AmazonAPI.php';

// https://webservices.amazon.com/paapi5/documentation/locale-reference/japan.html
const CATEGORIES = [
    "Music",
    "Books",
    "Hobbies",
    "Toys",
    "All",
];

const DEFAULT_KEYWORD = "初音ミク";

try {
    $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? CATEGORIES[0];
    $keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) ?? DEFAULT_KEYWORD;
    $page = (int)(filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT) ?? 1);

    $api = new AmazonAPI();
    $results = $api->searchItems($category, $keyword, ['itemPage' => $page]);
} catch(Exception $e) {
    $results = [];
    echo "Error Type: ", $e->getCode(), PHP_EOL;
    echo "Error Message: ", $e->getMessage(), PHP_EOL;
}

?><!DOCTYPE html>
<html lang="ja">
<head>
    <title>Amazon API</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="amazon_api.css">
</head>
<body>

<section id="searchOptions">

<form method="GET">
<input type="text" name="keyword" value="<?= h($keyword) ?>">

<ul id="categorySelector">
<?php foreach(CATEGORIES as $caItem): ?>
    <li class="<?= ($category == $caItem) ? 'active' : '' ?>">
        <label><input type="radio" name="category" value="<?= h($caItem) ?>" <?= ($category == $caItem) ? 'checked' : '' ?> /><?= h($caItem) ?></label>
    </li>
<?php endforeach; ?>
</ul>
<input type="submit" value="検索">
</form>

<div class="pager">
<label>pager</label>
<?php $base_url = h('./?'.http_build_query(compact(['category', 'keyword',]))); ?>
<?php if ($page > 2): ?>
    <a href="<?= $base_url ?>&page=1">first</a>
<?php endif; ?>
<?php if (1 < $page && $page <= 10): ?>
    <a href="<?= $base_url ?>&page=<?= $page - 1 ?>">prev</a>
<?php endif; ?>
<?php if (0 < $page && $page < 10): ?>
    <a href="<?= $base_url ?>&page=<?= $page + 1 ?>">next</a>
<?php endif; ?>
<?php if ($page < 10): ?>
    <a href="<?= $base_url ?>&page=10">last</a>
<?php endif; ?>
    <p>※Amazon APIは100件まで（10ページまで）に制限されました。</p>
</div>

</section>
<h3>Page: <?= h($page) ?></h3>
<ul id='SearchResult'>
<?php foreach ($results as $result) : ?>
<?php
    $ASIN = $result->getASIN();
    $URL = $result->getDetailPageUrl();
    $Title = "";
    $Date = "";
    $itemInfo = $result->getItemInfo();
    if ($itemInfo != null) {
        $Title = $itemInfo->getTitle()->getDisplayValue();
        if ($category === "Books") {
            $ContentInfo = $itemInfo->getContentInfo();
            if ($ContentInfo != null) {
                $PublicationDate = $ContentInfo->getPublicationDate();
                if ($PublicationDate != null) {
                    $PublicationDate = $PublicationDate->getDisplayValue();
                    $Date = (new \DateTime($PublicationDate))->format('Y/m/d');
                }
            }
        } else {
            $ProductInfo = $itemInfo->getProductInfo();
            if ($ProductInfo != null) {
                $ReleaseDate = $ProductInfo->getReleaseDate();
                if ($ReleaseDate != null) {
                    $ReleaseDate = $ReleaseDate->getDisplayValue();
                    $Date = (new \DateTime($ReleaseDate))->format('Y/m/d');
                }
            }
        }
    }
    $Price = "";
    foreach($result->getOffers()->getListings() as $Offer) {
        $Price = $Offer->getPrice()->getDisplayAmount();
        break;
    }
?>
    <li>
        <p class='ReleaseDate'><?= h($Date) ?></p>
        <p class='Title'>
            [<a href='<?= h($URL) ?>' target='_blank'>Link</a>]
            <?= h($Title) ?>
        </p>
        <p class="Url"><?= h($URL) ?></p>
    </li>
<?php endforeach; ?>
</ul>

</body>
</html>