<?php
require_once './src/lib/date_helper.php';

use PHPUnit\Framework\TestCase;

final class DateHelperTest extends TestCase {
  public function testConvertDateIntoDays() {
    $this->assertEquals(
      '17167',
      DateHelper::convertDateIntoDays('2017-01-01')
    );
  }
  public function testConvertDaysIntoDate() {
    $this->assertEquals(
      '2017-01-01',
      DateHelper::convertDaysIntoDate('17167')
    );
  }
  public function testGetWeekCode() {
    $this->assertEquals(
      '2016-W52',
      DateHelper::getWeekCode('2017-01-01')
    );
    $this->assertEquals(
      '2020-W01',
      DateHelper::getWeekCode('2019-12-30')
    );
    $this->assertEquals(
      '2020-W53',
      DateHelper::getWeekCode('2021-01-03')
    );
    $this->assertEquals(
      '2020-W53',
      DateHelper::getWeekCode('2020-12-28')
    );
    $this->assertEquals(
      '2025-W01',
      DateHelper::getWeekCode('2024-12-30')
    );
  }
  public function testGetMondayOfTheWeek() {
    $this->assertEquals(
      '2016-12-26',
      DateHelper::getMondayOfTheWeek('2017-01-01')
    );
    $this->assertEquals(
      '2017-01-02',
      DateHelper::getMondayOfTheWeek('2017-01-02')
    );
    $this->assertEquals(
      '2017-12-25',
      DateHelper::getMondayOfTheWeek('2017-12-28')
    );
  }
  public function testGetSundayOfTheWeek() {
    $this->assertEquals(
      '2017-01-01',
      DateHelper::getSundayOfTheWeek('2017-01-01')
    );
    $this->assertEquals(
      '2017-01-08',
      DateHelper::getSundayOfTheWeek('2017-01-02')
    );
    $this->assertEquals(
      '2017-12-31',
      DateHelper::getSundayOfTheWeek('2017-12-31')
    );
  }
  public function testGetISOWeekNo() {
    $this->assertEquals(
      6,
      DateHelper::getISOWeekNo('2017-01-01')
    );
  }
  public function testCountDays() {
    $this->assertEquals(
      14,
      DateHelper::countDays('2016-12-26', '2017-01-08')
    );
  }
}

