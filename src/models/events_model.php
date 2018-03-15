<?php

class EventsModel extends Sung\Mvc\Model{
  public $table = 'daily_events';

  /**
   * Table Fields
   *
   * @var $schema
   */
  public $schema = array(
    'id' => array (
      'field' => 'id',
      'label' => 'ID',
      'where' => true,
      'pk' => true,
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'artist_id' => array (
      'field' => 'artist_id',
      'label' => 'Artist ID',
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'week_number' => array (
      'field' => 'week_number',
      'label' => 'Week Number',
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'event_type_id' => array (
      'field' => 'event_type_id',
      'label' => 'Event Type ID',
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'total_events' => array (
      'field' => 'total_events',
      'label' => 'Total Events',
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'report_date' => array (
      'field' => 'report_date',
      'label' => 'Event Date',
      'rules' => array (
        'numeric' => true,
        'max_length' => 11
      )
    ),
    'startDate' => array (
      'label' => 'Start Date',
      'rules' => array (
        'date' => true
      )
    ),
    'endDate' => array (
      'label' => 'End Date',
      'rules' => array (
        'date' => true
      )
    ),
    'artistIds' => array (
      'label' => 'Artist IDs'
    )
  );

  /**
   * Additional properties by Action
   *
   * @var $fields
   */
  public $fields = array(
    'getWeeklyEvents' => array(
      'artistId' => array (
        'required' => true,
      ),
      'startDate' => array (
        'required' => true,
      ),
      'endDate' => array (
        'required' => true,
      )
    ),
    'getDailyMatrix' => array(
      'artistIds' => array (
        'required' => true,
      ),
      'startDate' => array (
        'required' => true,
      ),
      'endDate' => array (
        'required' => true,
      )
    ),
    'getTotalEvents' => array(
      'artistIds' => array (
        'required' => true,
      ),
      'startDate' => array (
        'required' => true,
      ),
      'endDate' => array (
        'required' => true,
      )
    )
  );

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return array
   */
  public function countDailyEvents($artist_ids, $start_date, $end_date) {
    $artist_ids = explode(',', $artist_ids);

    // artist search condition & map binding artist ids 
    $where_artist_ids = ' (';
    for ($i = 0; $i<count($artist_ids); $i++) 
      $where_artist_ids .= ' '.$artist_ids[$i].',';
    $where_artist_ids = substr($where_artist_ids, 0, -1);
    $where_artist_ids .= ') ';

    $query = '
    select count(*) as count
    from (
      select report_date,
             count(*) as count
      from  daily_events
      where artist_id in '.$where_artist_ids.'
      and report_date >= "'.$start_date.'"
      and report_date <= "'.$end_date.'"
      group by report_date
    ) a';

    return $this->query($query);
  }

  /**
   * @param integer $artist_id
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return array
   */
  public function getDailyEvents($artist_id, $start_date, $end_date) {
    $options = array (
      'select' => array (
        'week_code',
        'event_type_id',
        'sum(total_events) as total_events'
      ),
      'from' => array(
        'daily_events'
      ),
      'where' => array (
        array('where', 'artist_id', '=',$artist_id),
        array('and', 'event_type_id',' is not ', null),
        array('and', 'total_events','>', 0),
        array('and', 'report_date','>=', $start_date),
        array('and', 'report_date','<=', $end_date),
      ),
      'groupby' => array ('week_code', 'event_type_id')
    );
    return $this->select($options);
  }

  /**
   * @param integer $artist_ids
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return array
   */
  public function getTotalEvents($artist_ids, $start_date, $end_date) {
    $options = array (
      'select' => array (
        'a.name as artist_name',
        'et.name as event_type_name',
        'sum(e.total_events) as total_events'
      ),
      'from' => array (
        'daily_events           e',
        'left join artists      a',
        'on e.artist_id = a.id',
        'left join event_types  et',
        'on e.event_type_id = et.id'
      ),
      'where' => array (
        array('where', 'e.artist_id', 'in', explode(',',$artist_ids)),
        array('and', 'e.event_type_id','is not', null),
        array('and', 'e.report_date','>=', $start_date),
        array('and', 'e.report_date','<=', $end_date),
      ),
      'groupby' => array ('e.artist_id', 'e.event_type_id')
    );
    return $this->select($options);
  }

  /**
   * @param integer $artist_ids
   * @param integer $start_days
   * @param integer $end_days
   *
   * @return array
   */
  public function getDailyEventsMatrix($artist_ids, $start_date, $end_date) {
    $options = array (
      'select' => array (
        'e.report_date',
        'e.artist_id',
        'a.name as artist_name',
        'LOWER(REPLACE(a.name, " ", "_")) as artist_slug',
        'sum(e.total_events) as total_events'
      ),
      'from' => array (
        'daily_events       e',
        'left join artists  a',
        'on e.artist_id = a.id'
      ),
      'where' => array (
        array('where', 'e.artist_id', 'in', explode(',', $artist_ids)),
        array('and', 'e.report_date','>=', $start_date),
        array('and', 'e.report_date','<=', $end_date),
      ),
      'groupby' => array ('e.report_date', 'e.artist_id')
    );
    return $this->select($options);
  }

  /**
   * @param object $data
   *
   * @return mixed
   */
  public function insertDailyEvent($data) {
    $options = array (
      'table' => 'daily_events',
      'id' => 'id',
      'fields' => array(
        'artist_id' => $data['artist_id'],
        'report_date' => $data['report_date'],
        'week_code' => $data['week_code'],
        'event_type_id' => $data['event_type_id'],
        'total_events' => $data['total_events']
      )
    );
    return $this->insert($options);
  }

}

