<?php

namespace Drupal\calendar;

/**
 * Defines a calendar style info object.
 */
class CalendarStyleInfo {

  /**
   * Defines whether or not to show this calendar in a popup.
   *
   * @var boolean
   *   TRUE to show the calendar in a popup, FALSE otherwise.
   */
  protected $calendarPopup;

  /**
   * Defines whether or not this is a mini calendar.
   *
   * @var boolean
   *   TRUE if the calendar is shown in mini, FALSE otherwise.
   */
  protected $mini;

  /**
   * The size of the calendar name.
   *
   * @var int
   *   The size of the calendar name.
   */
  protected $nameSize;

  /**
   * Defines whether or not to display the title.
   *
   * @var boolean
   *   TRUE to display the title, FALSE otherwise.
   */
  protected $showTitle;

  /**
   * Defines whether or not to display the navigation.
   *
   * @var boolean
   *   TRUE to display the navigation, FALSE otherwise.
   */
  protected $showNavigation;

  /**
   * Defines whether or not to display the week numbers.
   *
   * @var boolean
   *   TRUE to display the week numbers, FAlSE otherwise.
   */
  protected $showWeekNumbers;

  /**
   * Defines whether or not to display empty times.
   *
   * @var boolean
   */
  protected $showEmptyTimes;

  /**
   * Defines the way of grouping items.
   *
   * @var string
   *   The way of grouping items (e.g. 'hour', 'half').
   */
  protected $groupByTimes;

  /**
   * Defines a custom way of grouping by times.
   *
   * @var string
   *   The grouping by times.
   */
  protected $customGroupByTimes;

  /**
   * Defines a custom group by field.
   *
   * @var string
   *   A field to group items by.
   */
  protected $customGroupByField;

  /**
   * The maximum amount of items to show.
   *
   * @var int
   *   The maximum amount.
   */
  protected $maxItems;

  /**
   * Defines what the maximum items style is.
   *
   * @var string
   *   The maximum items style (e.g. 'hide').
   */
  protected $maxItemsStyle;

  /**
   * Defines what the theme style is.
   *
   * @var int
   *   The index number of the theme style.
   */
  protected $themeStyle;

  /**
   * Defines what the multi day theme is.
   *
   * @var int
   *   The index number of the multiday theme.
   */
  protected $multiDayTheme;

  /**
   * Getter for the calendar popup variable.
   *
   * @return boolean
   *   TRUE to show the calendar in a popup, FALSE otherwise.
   */
  public function isCalendarPopup() {
    return $this->calendarPopup;
  }

  /**
   * Setter for the calendar popup variable.
   *
   * @param boolean $calendarPopup
   *   TRUE to show the calendar in a popup, FALSE otherwise.
   */
  public function setCalendarPopup($calendarPopup) {
    $this->calendarPopup = $calendarPopup;
  }

  /**
   * Getter for the mini format variable.
   *
   * @return boolean
   *   TRUE if the calendar is shown in mini, FALSE otherwise.
   */
  public function isMini() {
    return $this->mini;
  }

  /**
   * Setter for the mini format variable.
   *
   * @param boolean $mini
   *   TRUE if the calendar is shown in mini, FALSE otherwise.
   */
  public function setMini($mini) {
    $this->mini = $mini;
  }

  /**
   * Getter for the name size.
   *
   * @return int
   *   The name size.
   */
  public function getNameSize() {
    return $this->nameSize;
  }

  /**
   * Setter for the name size.
   *
   * @param int $nameSize
   *   The name size.
   */
  public function setNameSize($nameSize) {
    $this->nameSize = $nameSize;
  }

  /**
   * Getter for the show title variable.
   *
   * @return boolean
   *   TRUE to display the title, FALSE otherwise.
   */
  public function isShowTitle() {
    return $this->showTitle;
  }

  /**
   * Setter for the show title variable.
   *
   * @param boolean $showTitle
   *   TRUE to display the title, FALSE otherwise.
   */
  public function setShowTitle($showTitle) {
    $this->showTitle = $showTitle;
  }

  /**
   * Getter for the show navigation variable.
   *
   * @return boolean
   *   TRUE to show the navigation, FALSE otherwise.
   */
  public function isShowNavigation() {
    return $this->showNavigation;
  }

  /**
   * Setter for the show navigation variable.
   *
   * @param boolean $showNavigation
   *   TRUE to show the navigation, FALSE otherwise.
   */
  public function setShowNavigation($showNavigation) {
    $this->showNavigation = $showNavigation;
  }

  /**
   * Getter for the show week numbers variable.
   *
   * @return boolean
   *   TRUE to display the week numbers, FAlSE otherwise.
   */
  public function isShowWeekNumbers() {
    return $this->showWeekNumbers;
  }

  /**
   * Setter for the show week numbers variable.
   *
   * @param boolean $showWeekNumbers
   *   TRUE to display the week numbers, FAlSE otherwise.
   */
  public function setShowWeekNumbers($showWeekNumbers) {
    $this->showWeekNumbers = $showWeekNumbers;
  }

  /**
   * Getter for the show empty times variable.
   *
   * @return boolean
   *   TRUE to show empty times, FALSE otherwise.
   */
  public function isShowEmptyTimes() {
    return $this->showEmptyTimes;
  }

  /**
   * Setter for the show empty times variable.
   *
   * @param boolean $showEmptyTimes
   *   TRUE to show empty times, FALSE otherwise.
   */
  public function setShowEmptyTimes($showEmptyTimes) {
    $this->showEmptyTimes = $showEmptyTimes;
  }

  /**
   * Getter for the group by times property.
   *
   * @return string
   *   The group by time property.
   */
  public function getGroupByTimes() {
    return $this->groupByTimes;
  }

  /**
   * Setter for the group by times property.
   *
   * @param string $groupByTimes
   *   The group by time property.
   */
  public function setGroupByTimes($groupByTimes) {
    $this->groupByTimes = $groupByTimes;
  }

  /**
   * Getter for the custom group by times variable.
   *
   * @return string
   *   The custom grouping by times.
   */
  public function getCustomGroupByTimes() {
    return $this->customGroupByTimes;
  }

  /**
   * Setter for the custom group by times variable.
   *
   * @param string $customGroupByTimes
   *   The custom grouping by times.
   */
  public function setCustomGroupByTimes($customGroupByTimes) {
    $this->customGroupByTimes = $customGroupByTimes;
  }

  /**
   * Getter for the custom group by field variable.
   *
   * @return string
   *   The custom group by field.
   */
  public function getCustomGroupByField() {
    return $this->customGroupByField;
  }

  /**
   * Setter for the custom group by field variable.
   *
   * @param string $customGroupByField
   *   The custom group field.
   */
  public function setCustomGroupByField($customGroupByField) {
    $this->customGroupByField = $customGroupByField;
  }

  /**
   * Getter for the max items variable.
   *
   * @return int
   *   The maximum amount of items to show.
   */
  public function getMaxItems() {
    return $this->maxItems;
  }

  /**
   * Setter for the max items variable.
   *
   * @param int $maxItems
   *   The maximum amount of items to show.
   */
  public function setMaxItems($maxItems) {
    $this->maxItems = $maxItems;
  }

  /**
   * Getter for the max items style.
   *
   * @return string
   *   The maximum items style.
   */
  public function getMaxItemsStyle() {
    return $this->maxItemsStyle;
  }

  /**
   * Setter for the maximum items style.
   *
   * @param string $maxItemsStyle
   *   The maximum items style.
   */
  public function setMaxItemsStyle($maxItemsStyle) {
    $this->maxItemsStyle = $maxItemsStyle;
  }

  /**
   * Getter for the multiday theme.
   *
   * @return int
   *   The index number of the multiday theme.
   */
  public function getMultiDayTheme() {
    return $this->multiDayTheme;
  }

  /**
   * Setter for the multi day theme variable.
   *
   * @param int $multiDayTheme
   *   The index number of the multiday theme.
   */
  public function setMultiDayTheme($multiDayTheme) {
    $this->multiDayTheme = $multiDayTheme;
  }

  /**
   * Getter for the theme style variable.
   *
   * @return int
   *   The index number of the theme style.
   */
  public function getThemeStyle() {
    return $this->themeStyle;
  }

  /**
   * Setter for the theme style variable.
   *
   * @param int $themeStyle
   *   The index number of the theme style.
   */
  public function setThemeStyle($themeStyle) {
    $this->themeStyle = $themeStyle;
  }
}
