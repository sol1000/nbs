<?php

use Sung\Mvc\Http;

class EventsController extends Sung\Mvc\Controller{
  /**
   * Date helper
   *
   * @var $dh
   */
  private $dh;

  /**
   * Frequently accessed vars
   */
  private $artist_id;
  private $artist_ids;

  private $start_date;
  private $end_date;
  private $start_days;
  private $end_days;

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct() {
    parent::__construct();
    $this->dh = new DateHelper();
  }

  /**
   * @return boolean
   */
  public function initParams($mode = 'daily') {
    $this->artist_id = $this->getArgs('artistId');
    $this->artist_ids = $this->getArgs('artistIds');
    $this->start_date = $this->getArgs('startDate');
    $this->end_date = $this->getArgs('endDate');

    if (empty($this->artist_ids) && !empty($this->artist_id)) {
      $this->artist_ids = $this->artist_id;
    }

    // Date range error
    if ($this->end_date < $this->start_date) {
      $this->model->setError('startDate', 'ERROR_INVALID_DATE_RANGE');
      $this->model->setError('endDate', 'ERROR_INVALID_DATE_RANGE');
      $this->Error('INPUT_ERRORS');
      return false;
    }

    // Adjust start date to its monday and end date to its sunday
    if ($mode == 'weekly') {
      $this->start_date = $this->dh->getMondayOfTheWeek($this->start_date);
      $this->end_date = $this->dh->getSundayOfTheWeek($this->end_date);
    }

    $this->start_days = $this->dh->convertDateIntoDays($this->start_date);
    $this->end_days = $this->dh->convertDateIntoDays($this->end_date);
    return true;
  }

  /**
   * @return boolean
   */
  public function limitDateRange() {
    if ($this->dh->countDays($this->start_date, $this->end_date) > 400) {
      $this->model->setError('startDate', 'ERROR_TOO_LARGE_DATE_RANGE');
      $this->model->setError('endDate', 'ERROR_TOO_LARGE_DATE_RANGE');
      $this->Error('INPUT_ERRORS');
      return false;
    }
    return true;
  }

  /**
   * @return boolean
   */
  public function getWeeklyEvents() {
    $this->initParams('weekly');

    if ($this->getArgs('cache') == 1) {
      $this->limitDateRange();

      // Inject daily events to the database
      if (!$this->hasData($this->artist_id, $this->start_date, $this->end_date)) {
        $result = $this->injectNBSAPIData($this->artist_id, $this->start_days, $this->end_days);
        if ($result === true) {
          $result = $this->model->getDailyEvents($this->artist_id, $this->start_date, $this->end_date);
        }
      }else {
        // get data from DB
        $result = $this->model->getDailyEvents($this->artist_id, $this->start_date, $this->end_date);
      }
      if ($result === true) {
        $this->output = $this->countWeeklyEventsFromDBResult($this->model->data['items']);
        $this->Render();
        return true;    
      }else {
        $this->Error($result);
        return false;
      }      
    }else {
      // Just wrap the API result
      $result = $this->getEventsFromNBSAPI($this->artist_id, $this->start_days, $this->end_days);
      $result = $this->countWeeklyEventsFromAPIResult($result);

      if (!empty($result)) {
        $this->output = $result;
        $this->Render();
        return true;    
      }else {
        $this->Error($result);
        return false;
      }
    }

    return false;
  }

  /**
   * @return boolean
   */
  public function getTotalEvents() {
    $this->initParams();
    $this->limitDateRange();

    // inject daily events to the database
    if (!$this->hasData($this->artist_ids, $this->start_date, $this->end_date)) {
      $result = $this->injectNBSAPIData($this->artist_ids, $this->start_days, $this->end_days);
      if ($result !== true) {
        $this->Error($result);
        return false;
      }else {
        $this->model->clearDataResult();
      }
    }

    $result = $this->model->getTotalEvents($this->artist_ids, $this->start_date, $this->end_date);
    if ($result === true) {
      $this->output = $this->model->getDataItems();
      $this->Render();
      return true;    
    }else {
      $this->Error($result);
      return false;
    }
  }

  /**
   * @return boolean
   */
  public function getDailyMatrix() {
    $this->initParams('daily');
    $this->limitDateRange();

    if (!$this->hasData($this->artist_ids, $this->start_date, $this->end_date)) {
      $result = $this->injectNBSAPIData($this->artist_ids, $this->start_days, $this->end_days);
      if ($result !== true) {
        // cannot show the matrix without database
        $this->Error($result);
        return false;
      }
    }

    // get data from DB
    $result = $this->model->getDailyEventsMatrix($this->artist_ids, $this->start_date, $this->end_date);
    if ($result === true) {
      $this->output = $this->convertDailyEventsIntoMatrix($this->model->data['items']);
      $this->Render();
      return true;    
    }else {
      $this->Error($result);
      return false;
    }
  }

  /**
   * @param integer $artist_ids
   * @param date $start_date
   * @param date $end_date
   *
   * @return boolean
   */
  private function hasData($artist_ids, $start_date, $end_date) {
    $total_days = $this->dh->countDays($start_date, $end_date);
    foreach (explode(',', $artist_ids) as $artist_id) {
      $result = $this->model->countDailyEvents($artist_id, $start_date, $end_date);
      if ($result !== true || $total_days != $this->model->data['items'][0]['count']) {
        return false;
      }
    }
    return true;
  }

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return boolean
   */
  private function injectNBSAPIData($artist_ids, $start_days, $end_days) {
    $artist_ids = explode(',', $artist_ids);
    foreach ($artist_ids as $artist_id) {
      if (!$this->injectNBSAPIDataByArtist($artist_id, $start_days, $end_days)) return false;
    }
    return true;
  }

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return boolean
   */
  private function injectNBSAPIDataByArtist($artist_id, $start_days, $end_days) {
    $api_result = $this->getEventsFromNBSAPI($artist_id, $start_days, $end_days);

    for($days = $start_days; $days <= $end_days; $days++) {
      $date =  $this->dh->convertDaysIntoDate($days);
      $week_code = $this->dh->getWeekCode($date);

      if (isset($api_result->{$days}) 
        && isset($api_result->{$days}->event_types)) {
        $obj = $api_result->{$days};
        foreach($obj->event_types as $event_type_id => $event) {
          $data = array(
            'artist_id' => $artist_id,
            'report_date' => $date,
            'week_code' => $week_code,
            'event_type_id' => $event_type_id,
            'total_events' => count($event->events)
          );
          $insert_result = $this->model->insertDailyEvent($data);
          if($insert_result !== true && $insert_result !== 'ERROR_DB_NO_AFFECTED') {
            return false;
          }
        }
      }else {
        // insert empty data as 0 not to try to insert again
        $data = array(
          'artist_id' => $artist_id,
          'report_date' => $date,
          'week_code' => $week_code,
          'event_type_id' => null,
          'total_events' => 0
        );
        $insert_result = $this->model->insertDailyEvent($data);
        if($insert_result !== true && $insert_result !== 'ERROR_DB_NO_AFFECTED') {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return json
   */
  private function getEventsFromNBSAPI($artist_id, $start_days, $end_days) {
    $http = new Sung\Mvc\Http();
    $url = $this->makeNBSAPIURL($artist_id, $start_days, $end_days);
    $result = $http->getJson($url);
    return $result;
  }

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return string
   */
  private function makeNBSAPIURL($artist_id, $start_days, $end_days) {
    return NEXTBIGSOUND_API.$artist_id.'?start='.$start_days.'&end='.$end_days.'&access_token='.ACCESS_TOKEN;
  }

  /**
   * @param array $events
   *
   * @return array
   */
  private function countWeeklyEventsFromAPIResult($events) {
    $counts = array();
    foreach($events as $days => $event_types) {
      $date =  $this->dh->convertDaysIntoDate($days);
      $week_code =  $this->dh->getWeekCode($date);
      if (empty($counts[$week_code])) $counts[$week_code] = array();
      foreach($event_types->event_types as $event_type => $event_info) {
        if (empty($counts[$week_code][$event_type])) 
          $counts[$week_code][$event_type] = 0;
        if (!empty($event_info->events) && count($event_info->events) > 0) 
          $counts[$week_code][$event_type] += count($event_info->events);
      }
    }
    return array('counts' => $counts);
  }

  /**
   * @param array $events
   *
   * @return array
   */
  private function countWeeklyEventsFromDBResult($events) {
    $counts = array();
    foreach($events as $event) {
      $week_code = $event['week_code'];
      if (!isset($counts[$week_code])) $counts[$week_code] = array();
      $counts[$week_code][$event['event_type_id']] = intval($event['total_events']);
    }
    return array('counts' => $counts);
  }

  /**
   * @param array $events
   *
   * @return array
   */
  private function convertDailyEventsIntoMatrix($events) {
    $result = array();
    $item = array();
    $report_date = '';
    foreach($events as $event) {
      if (!isset($event['artist_name']) || empty($event['artist_name'])) continue;
      if ($report_date != $event['report_date']) {
        if (!empty($item)) $result[] = $item;
        $report_date = $event['report_date'];
        $item = array('date' => $report_date);
      }
      $item[$event['artist_name']] = intval($event['total_events']);
    }
    return $result;
  }


}

