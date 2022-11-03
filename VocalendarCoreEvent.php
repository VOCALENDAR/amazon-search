<?php

namespace vocalendar;

/**
 * Vocalendar Core Event class
 *
 * Coreから取得するイベントスケジュールのクラス
 */
class VocalendarCoreEvent
{
    /** @var array event data  */
    protected $data;

    protected $allday;
    protected $country;
    protected $createdAt;
    protected $description;
    protected $startDate;
    protected $startDatetime;
    protected $endDate;
    protected $endDatetime;
    protected $gCalendarId;
    protected $gCreatorEmail;
    protected $gEid;
    protected $gHtmlLink;
    protected $gId;
    protected $gRecurringEventId;
    protected $icalUid;
    protected $id;
    protected $lang;
    protected $location;
    protected $primaryLinkId;
    protected $recurOrigStartDate;
    protected $recurOrigStartDatetime;
    protected $recurString;
    protected $status;
    protected $summary;
    protected $timezone;
    protected $tzinfo;
    protected $currentPeriod;
    protected $twitterHash;
    protected $tzMin;
    protected $updatedAt;
    protected $tags;
    protected $relatedLinks;
    protected $favoriteCount;
    protected $favorited;

    /**
     * コンストラクタ
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        // パラメータ
        $this->allday                 = $data['allday']                    ?? null;
        $this->country                = $data['country']                   ?? null;
        $this->createdAt              = $data['created_at']                ?? null;
        $this->description            = $data['description']               ?? null;
        $this->startDate              = $data['start_date']                ?? null;
        $this->startDatetime          = $data['start_datetime']            ?? null;
        $this->endDate                = $data['end_date']                  ?? null;
        $this->endDatetime            = $data['end_datetime']              ?? null;
        $this->gCalendarId            = $data['g_calendar_id']             ?? null;
        $this->gCreatorEmail          = $data['g_creator_email']           ?? null;
        $this->gEid                   = $data['g_eid']                     ?? null;
        $this->gHtmlLink              = $data['g_html_link']               ?? null;
        $this->gId                    = $data['g_id']                      ?? null;
        $this->gRecurringEventId      = $data['g_recurring_event_id']      ?? null;
        $this->icalUid                = $data['ical_uid']                  ?? null;
        $this->id                     = $data['id']                        ?? null;
        $this->lang                   = $data['lang']                      ?? null;
        $this->location               = $data['location']                  ?? null;
        $this->primaryLinkId          = $data['primary_link_id']           ?? null;
        $this->recurOrigStartDate     = $data['recur_orig_start_date']     ?? null;
        $this->recurOrigStartDatetime = $data['recur_orig_start_datetime'] ?? null;
        $this->recurString            = $data['recur_string']              ?? null;
        $this->status                 = $data['status']                    ?? null;
        $this->summary                = $data['summary']                   ?? null;
        $this->timezone               = $data['timezone']                  ?? null;
        $this->tzinfo                 = $data['tzinfo']                    ?? null;
        $this->currentPeriod          = $data['current_period']            ?? null;
        $this->twitterHash            = $data['twitter_hash']              ?? null;
        $this->tzMin                  = $data['tz_min']                    ?? null;
        $this->updatedAt              = $data['updated_at']                ?? null;
        $this->tags                   = $data['tags']                      ?? null;
        $this->relatedLinks           = $data['related_links']             ?? null;
        $this->favoriteCount          = $data['favorite_count']            ?? null;
        $this->favorited              = $data['favorited']                 ?? null;
    }

    /**
     * get
     */
    public function __get($key)
    {
        if (!is_null($this->$key)) {
            return $this->$key;
        }

        return null;
    }
}
