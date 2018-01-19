<?php

namespace Drupal\calendar;

use Datetime;
use DateTimeZone;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\filter\Broken;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\Component\Utility\Unicode;
use Drupal\views\Plugin\views\argument\Date as ViewsDateArg;

/**
 * Defines Gregorian Calendar date values.
 */
class CalendarHelper extends DateHelper {

  /**
   * Formats the weekday information into a table header format.
   *
   * @return array
   *   An array with weekday table header data.
   */
  public static function weekHeader($view) {
    $nameSize = $view->styleInfo->getNameSize();
    $len = isset($nameSize) ? $view->styleInfo->getNameSize() : (!empty($view->styleInfo->isMini()) ? 1 : 3);
    $with_week = !empty($view->styleInfo->isShowWeekNumbers());

    // create week header
    $untranslated_days = self::untranslatedDays();
    $full_translated_days = self::weekDaysOrdered(self::weekDays(TRUE));
    if ($len == 99) {
      $translated_days = $full_translated_days;
    }
    else {
      $translated_days = self::weekDaysOrdered(self::weekDaysAbbr(TRUE));
    }
    if ($with_week) {
      $row[] = ['header' => TRUE, 'class' => 'days week', 'data' => '', 'header_id' => 'Week'];
    }
    foreach ($untranslated_days as $delta => $day) {
      $label = $len < 3 ? Unicode::substr($translated_days[$delta], 0 , $len) : $translated_days[$delta];
      $row[] = ['header' => TRUE, 'class' => "days " . $day, 'data' => $label, 'header_id' => $full_translated_days[$delta]];
    }
    return $row;
  }


  /**
   * An array of untranslated day name abbreviations.
   *
   * The abbreviations are forced to lowercase and ordered appropriately for the
   * site setting for the first day of week.
   *
   * @return array
   *   The untranslated day abbreviation is used in css classes.
   */
  public static function untranslatedDays() {
    $untranslated_days = self::weekDaysOrdered(DateHelper::weekDaysUntranslated());
    foreach ($untranslated_days as $delta => $day) {
      $untranslated_days[$delta] = strtolower(substr($day, 0, 3));
    }
    return $untranslated_days;
  }

  /**
   * Return a list of all calendar views.
   *
   * @return array
   *   A list of all calendar views.
   */
  public static function listCalendarViews() {
    $calendar_views = [];
    $views = Views::getEnabledViews();
    foreach ($views as $view) {
      $ve = $view->getExecutable();
      $ve->initDisplay();
      foreach ($ve->displayHandlers->getConfiguration() as $display_id => $display) {
        if ($display_id != 'default' && $types = $ve->getStyle()->getPluginId() == 'calendar') {
          $index = $ve->id() . ':' . $display_id;
          $calendar_views[$index] = ucfirst($ve->id()) . ' ' . strtolower($display['display_title']) . ' [' . $ve->id() . ':' . $display['id'] . ']';
        }
      }
    }
    return $calendar_views;
  }

  /**
   * Computes difference between two days using a given measure.
   *
   * @param DateTime $start_date
   *   The start date.
   * @param DateTime $stop_date
   *   The stop date.
   * @param string $measure
   *   (optional) A granularity date part. Defaults to 'seconds'.
   * @param boolean $absolute
   *   (optional) Indicate whether the absolute value of the difference should
   *   be returned or if the sign should be retained. Defaults to TRUE.
   *
   * @return int
   *   The difference between the 2 dates in the given measure.
   */
  public static function difference(DateTime $start_date, DateTime $stop_date, $measure = 'seconds', $absolute = TRUE) {
    // Create cloned objects or original dates will be impacted by the
    // date_modify() operations done in this code.
    $date1 = clone($start_date);
    $date2 = clone($stop_date);
    if (is_object($date1) && is_object($date2)) {
      $diff = $date2->format('U') - $date1->format('U');
      if ($diff == 0) {
        return 0;
      }
      elseif ($diff < 0 && $absolute) {
        // Make sure $date1 is the smaller date.
        $temp = $date2;
        $date2 = $date1;
        $date1 = $temp;
        $diff = $date2->format('U') - $date1->format('U');
      }
      $year_diff = intval($date2->format('Y') - $date1->format('Y'));
      switch ($measure) {
        // The easy cases first.
        case 'seconds':
          return $diff;

        case 'minutes':
          return $diff / 60;

        case 'hours':
          return $diff / 3600;

        case 'years':
          return $year_diff;

        case 'months':
          $format = 'n';
          $item1 = $date1->format($format);
          $item2 = $date2->format($format);
          if ($year_diff == 0) {
            return intval($item2 - $item1);
          }
          elseif ($year_diff < 0) {
            $item_diff = 0 - $item1;
            $item_diff -= intval((abs($year_diff) - 1) * 12);
            return $item_diff - (12 - $item2);
          }
          else {
            $item_diff = 12 - $item1;
            $item_diff += intval(($year_diff - 1) * 12);
            return $item_diff + $item2;
          }
          break;

        case 'days':
          $format = 'z';
          $item1 = $date1->format($format);
          $item2 = $date2->format($format);
          if ($year_diff == 0) {
            return intval($item2 - $item1);
          }
          elseif ($year_diff < 0) {
            $item_diff = 0 - $item1;
            for ($i = 1; $i < abs($year_diff); $i++) {
              $date1->modify('-1 year');
              // @TODO self::daysInYear() throws a warning when used with a
              // \DateTime object. See https://www.drupal.org/node/2596043
//              $item_diff -= self::daysInYear($date1);
              $item_diff -= 365;
            }
//            return $item_diff - (self::daysInYear($date2) - $item2);
            return $item_diff - (365 - $item2);
          }
          else {
            // @TODO self::daysInYear() throws a warning when used with a
            // \DateTime object. See https://www.drupal.org/node/2596043
//            $item_diff = self::daysInYear($date1) - $item1;
            $item_diff = 365 - $item1;
            for ($i = 1; $i < $year_diff; $i++) {
              $date1->modify('+1 year');
//              $item_diff += self::daysInYear($date1);
              $item_diff += 365;
            }
            return $item_diff + $item2;
          }
          break;

        case 'weeks':
          $week_diff = $date2->format('W') - $date1->format('W');
          $year_diff = $date2->format('o') - $date1->format('o');

          $sign = ($year_diff < 0) ? -1 : 1;

          for ($i = 1; $i <= abs($year_diff); $i++) {
            $date1->modify((($sign > 0) ? '+': '-').'1 year');
            $week_diff += (self::isoWeeksInYear($date1) * $sign);
          }
          return $week_diff;
      }
    }
    return NULL;
  }

  /**
   * Identifies the number of ISO weeks in a year for a date.
   *
   * December 28 is always in the last ISO week of the year.
   *
   * @param mixed $date
   *   (optional) The current date object, or a date string. Defaults to NULL.
   *
   * @return integer
   *   The number of ISO weeks in a year.
   */
  public static function isoWeeksInYear($date = NULL) {
    if (empty($date)) {
      $date = new DateTime();
    }
    elseif (!is_object($date)) {
      $date = new DateTime($date);
    }

    if (is_object($date)) {
      date_date_set($date, $date->format('Y'), 12, 28);
      return $date->format('W');
    }
    return NULL;
  }

  /**
   * Checks if an event covers all day.
   *
   * @param string $start
   *   The start date.
   * @param string $end
   *   The end date.
   * @param string $granularity
   *   Granularity to be used during the calculation, defaults to "second".
   * @param int $increment
   *   An integer value to increment the values. Defaults to 1.
   *
   * @return bool
   */
  public static function dateIsAllDay($start, $end, $granularity = 'second', $increment = 1) {
    if (empty($start) || empty($end)) {
      return FALSE;
    }
    elseif (!in_array($granularity, ['hour', 'minute', 'second'])) {
      return FALSE;
    }

    preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}) (([0-9]{2}):([0-9]{2}):([0-9]{2}))/', $start, $matches);
    $count = count($matches);
    $date1 = $count > 1 ? $matches[1] : '';
    $time1 = $count > 2 ? $matches[2] : '';
    $hour1 = $count > 3 ? intval($matches[3]) : 0;
    $min1 = $count > 4 ? intval($matches[4]) : 0;
    $sec1 = $count > 5 ? intval($matches[5]) : 0;
    preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}) (([0-9]{2}):([0-9]{2}):([0-9]{2}))/', $end, $matches);
    $count = count($matches);
    $date2 = $count > 1 ? $matches[1] : '';
    $time2 = $count > 2 ? $matches[2] : '';
    $hour2 = $count > 3 ? intval($matches[3]) : 0;
    $min2 = $count > 4 ? intval($matches[4]) : 0;
    $sec2 = $count > 5 ? intval($matches[5]) : 0;
    if (empty($date1) || empty($date2)) {
      return FALSE;
    }
    if (empty($time1) || empty($time2)) {
      return FALSE;
    }

    $tmp = self::seconds('s', TRUE, $increment);
    $max_seconds = intval(array_pop($tmp));
    $tmp = self::minutes('i', TRUE, $increment);
    $max_minutes = intval(array_pop($tmp));

    // See if minutes and seconds are the maximum allowed for an increment or
    // the maximum possible (59), or 0.
    switch ($granularity) {
      case 'second':
        $min_match = $time1 == '00:00:00'
          || ($hour1 == 0 && $min1 == 0 && $sec1 == 0);
        $max_match = $time2 == '00:00:00'
          || ($hour2 == 23 && in_array($min2, [$max_minutes, 59]) && in_array($sec2, [$max_seconds, 59]))
          || ($hour1 == 0 && $hour2 == 0 && $min1 == 0 && $min2 == 0 && $sec1 == 0 && $sec2 == 0);
        break;
      case 'minute':
        $min_match = $time1 == '00:00:00'
          || ($hour1 == 0 && $min1 == 0);
        $max_match = $time2 == '00:00:00'
          || ($hour2 == 23 && in_array($min2, [$max_minutes, 59]))
          || ($hour1 == 0 && $hour2 == 0 && $min1 == 0 && $min2 == 0);
        break;
      case 'hour':
        $min_match = $time1 == '00:00:00'
          || ($hour1 == 0);
        $max_match = $time2 == '00:00:00'
          || ($hour2 == 23)
          || ($hour1 == 0 && $hour2 == 0);
        break;
      default:
        $min_match = TRUE;
        $max_match = FALSE;
    }

    if ($min_match && $max_match) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Calendar display types.
   */
  public static function displayTypes() {
    return ['year' => t('Year'), 'month' => t('Month'), 'day' => t('Day'), 'week' => t('Week')];
  }

  /**
   * The calendar week number for a date.
   *
   * PHP week functions return the ISO week, not the calendar week.
   *
   * @param string $date
   *   A date string in the format Y-m-d.
   *
   * @return int
   *   The calendar week number.
   */
  public static function dateWeek($date) {
    $date = substr($date, 0, 10);
    $parts = explode('-', $date);

    $timezone = new DateTimeZone('UTC');
    $date = new DateTime($date . ' 12:00:00', $timezone);

    $year_date = new DateTime($parts[0] . '-01-01 12:00:00', $timezone);
    $week = intval($date->format('W'));
    $year_week = intval(date_format($year_date, 'W'));
    $date_year = intval($date->format('o'));

    // Remove the leap week if it's present.
    if ($date_year > intval($parts[0])) {
      $last_date = clone($date);
      date_modify($last_date, '-7 days');
      $week = date_format($last_date, 'W') + 1;
    }
    elseif ($date_year < intval($parts[0])) {
      $week = 0;
    }

    if ($year_week != 1) {
      $week++;
    }

    // Convert to ISO-8601 day number, to match weeks calculated above.
    $iso_first_day = 0;

    // If it's before the starting day, it's the previous week.
    if (intval($date->format('N')) < $iso_first_day) {
      $week--;
    }

    // If the year starts before, it's an extra week at the beginning.
    if (intval(date_format($year_date, 'N')) < $iso_first_day) {
      $week++;
    }

    return $week;
  }

  /**
   * Helper for identifying Date API fields for views.
   *
   * This is a ported version of  date_views_fields() in date_views module in
   * D7.
   *
   * @param string $base
   * @return array
   */
  public static function dateViewFields($base = 'node') {

    // Make sure $base is never empty.
    if (empty($base)) {
      $base = 'node';
    }

    $cid = 'date_views_fields_' . $base;
//    cache_clear_all($cid, 'cache_views');

    // We use fields that provide filter handlers as our universe of possible
    // fields of interest.
    $all_fields = self::viewsFetchFields($base, 'filter');

    // Iterate over all the fields that Views knows about.
    $fields = [];
    foreach ((array) $all_fields as $alias => $value) {
      // Set up some default values.
      $granularity = ['year', 'month', 'day', 'hour', 'minute', 'second'];
      $tz_handling = 'site';
      $related_fields = [];
      $timezone_field = '';
      $offset_field = '';
      $rrule_field = '';
      $delta_field = '';
//      $sql_type = DATE_UNIX;
      $sql_type = DATETIME_DATETIME_STORAGE_FORMAT;
      $type = '';

      $name = $alias;
      $tmp = explode('.', $name);
      $field_name = $tmp[1];
      $table_name = $tmp[0];

      // Unset the date filter to avoid ugly recursion and broken values.
      if ($field_name == 'date_filter') {
        continue;
      }

      $fromto = [$name, $name];


      // If we don't have a filter handler, we don't need to do anything more.
      $filterHandler = Views::handlerManager('filter');
      $handler = $filterHandler->getHandler(['table' => $table_name, 'field' => $field_name,]);
      if ($handler instanceof Broken) {
        continue;
      }

//      $handler = views_get_handler($table_name, $field_name, 'filter');
      $pluginDefinition = $handler->getPluginDefinition();

      // We don't care about anything but date handlers.
      if ($pluginDefinition['class'] != 'Drupal\views\Plugin\views\filter\Date'
        && !is_subclass_of($pluginDefinition['class'], 'Drupal\views\Plugin\views\filter\Date')) {
        continue;
      }
      $is_field = FALSE;

      // For Field module fields, get the date type.
      $custom = [];
      if ($field_name || isset($handler->definition['field_name'])) {
//        $field = FieldConfig::loadByName($field_name);
//        $field = field_info_field($handler->definition['field_name']);
        $is_field = TRUE;
//        switch ($field['type']) {
        switch ($handler->getBaseId()) {
          case 'date':
            $sql_type = DATETIME_DATETIME_STORAGE_FORMAT;
//            $sql_type = DATE_ISO;
            break;

          case 'datestamp':
            break;

          case 'datetime':
//            $sql_type = DATE_DATETIME;
            $sql_type = DATETIME_DATETIME_STORAGE_FORMAT;
            break;

          default:
            // If this is not a date field, nothing more to do.
            continue;
        }

//        $revision = in_array($base, array('node_revision')) ? FIELD_LOAD_REVISION : FIELD_LOAD_CURRENT;
        // @todo find database info
//        $db_info = date_api_database_info($field, $revision);
        $name = $table_name . "." . $field_name;
        $grans = ['year', 'month', 'day', 'hour', 'minute', 'second'];
        $granularity = !empty($field['granularity']) ? $field['granularity'] : $grans;

//        $fromto = [
//          $table_name . '.' . $db_info['columns'][$table_name]['value'],
//          $table_name . '.' . (!empty($field['settings']['todate']) ? $db_info['columns'][$table_name]['value2'] : $db_info['columns'][$table_name]['value']),
//        ];

//        if (isset($field['settings']['tz_handling'])) {
//          $tz_handling = $field['settings']['tz_handling'];
//          $db_info = date_api_database_info($field, $revision);
//          if ($tz_handling == 'date') {
//            $offset_field = $table_name . '.' . $db_info['columns'][$table_name]['offset'];
//          }
//          $related_fields = [
//            $table_name . '.' . $db_info['columns'][$table_name]['value'],
//          ];
//          if (isset($db_info['columns'][$table_name]['value2'])) {
//            $related_fields = array_merge($related_fields, [$table_name . '.' . $db_info['columns'][$table_name]['value2']]);
//          }
//          if (isset($db_info['columns'][$table_name]['timezone'])) {
//            $related_fields = array_merge($related_fields, [$table_name . '.' . $db_info['columns'][$table_name]['timezone']]);
//            $timezone_field = $table_name . '.' . $db_info['columns'][$table_name]['timezone'];
//          }
//          if (isset($db_info['columns'][$table_name]['rrule'])) {
//            $related_fields = array_merge($related_fields, [$table_name . '.' . $db_info['columns'][$table_name]['rrule']]);
//            $rrule_field = $table_name . '.' . $db_info['columns'][$table_name]['rrule'];
//          }
//        }
        // Get the delta value into the query.
//        if ($field['cardinality'] != 1) {
//          array_push($related_fields, "$table_name.delta");
//          $delta_field = $table_name . '_delta';
//        }
      }

      // Allow custom modules to provide date fields.
      else {
//        foreach (module_implements('date_views_fields') as $module) {
//          $function = $module . '_date_views_fields';
//          if ($custom = $function("$table_name.$field_name")) {
//            $type = 'custom';
//            break;
//          }
//        }
      }
      // Don't do anything if this is not a date field we can handle.
      if (!empty($type) || empty($custom)) {
        $alias = str_replace('.', '_', $alias);
        $fields['name'][$name] = [
          'is_field' => $is_field,
          'sql_type' => $sql_type,
//          'label' => $val['group'] . ': ' . $val['title'],
          'granularity' => $granularity,
          'fullname' => $name,
          'table_name' => $table_name,
          'field_name' => $field_name,
          'query_name' => $alias,
          'fromto' => $fromto,
          'tz_handling' => $tz_handling,
          'offset_field' => $offset_field,
          'timezone_field' => $timezone_field,
          'rrule_field' => $rrule_field,
          'related_fields' => $related_fields,
          'delta_field' => $delta_field,
        ];

        // Allow the custom fields to over-write values.
        if (!empty($custom)) {
          foreach ($custom as $key => $field_value) {
            $fields['name'][$name][$key] = $field_value;
          }
        }
        $fields['name'][$name]['real_field_name'] = $field_name;
        $fields['alias'][$alias] = $fields['name'][$name];
      }
    }
//    cache_set($cid, $fields, 'cache_views');
    return $fields;
  }

  /**
   * Fetch a list of all fields available for a given base type.
   *
   * This is a ported version of  views_fetch_fields() in date_views module in
   * D7.
   *
   * @param $base
   * @param $type
   * @param bool|FALSE $grouping
   * @return array
   */
  private static function viewsFetchFields($base, $type, $grouping = FALSE) {
    static $fields = [];
    if (empty($fields)) {
      $data = Views::viewsData()->get();
      $start = microtime(TRUE);
      // This constructs this ginormous multi dimensional array to
      // collect the important data about fields. In the end,
      // the structure looks a bit like this (using nid as an example)
      // $strings['nid']['filter']['title'] = 'string'.
      //
      // This is constructed this way because the above referenced strings
      // can appear in different places in the actual data structure so that
      // the data doesn't have to be repeated a lot. This essentially lets
      // each field have a cheap kind of inheritance.

      foreach ($data as $table => $table_data) {
        $bases = [];
        $strings = [];
        $skip_bases = [];
        foreach ($table_data as $field => $info) {
          // Collect table data from this table
          if ($field == 'table') {
            // calculate what tables this table can join to.
            if (!empty($info['join'])) {
              $bases = array_keys($info['join']);
            }
            // And it obviously joins to itself.
            $bases[] = $table;
            continue;
          }
          foreach (['field', 'sort', 'filter', 'argument', 'relationship', 'area'] as $key) {
            if (!empty($info[$key])) {
              if ($grouping && !empty($info[$key]['no group by'])) {
                continue;
              }
              if (!empty($info[$key]['skip base'])) {
                foreach ((array) $info[$key]['skip base'] as $base_name) {
                  $skip_bases[$field][$key][$base_name] = TRUE;
                }
              }
              elseif (!empty($info['skip base'])) {
                foreach ((array) $info['skip base'] as $base_name) {
                  $skip_bases[$field][$key][$base_name] = TRUE;
                }
              }
              // Don't show old fields. The real field will be added right.
              if (isset($info[$key]['moved to'])) {
                continue;
              }
              foreach (['title', 'group', 'help', 'base', 'aliases'] as $string) {
                // First, try the lowest possible level
                if (!empty($info[$key][$string])) {
                  $strings[$field][$key][$string] = $info[$key][$string];
                }
                // Then try the field level
                elseif (!empty($info[$string])) {
                  $strings[$field][$key][$string] = $info[$string];
                }
                // Finally, try the table level
                elseif (!empty($table_data['table'][$string])) {
                  $strings[$field][$key][$string] = $table_data['table'][$string];
                }
                else {
                  if ($string != 'base' && $string != 'base') {
                    $strings[$field][$key][$string] = t("Error: missing @component", ['@component' => $string]);
                  }
                }
              }
            }
          }
        }
        foreach ($bases as $base_name) {
          foreach ($strings as $field => $field_strings) {
            foreach ($field_strings as $type_name => $type_strings) {
              if (empty($skip_bases[$field][$type_name][$base_name])) {
                $fields[$base_name][$type_name]["$table.$field"] = $type_strings;
              }
            }
          }
        }
      }
//    vsm('Views UI data build time: ' . (views_microtime() - $start) * 1000 . ' ms');
    }

    // If we have an array of base tables available, go through them
    // all and add them together. Duplicate keys will be lost and that's
    // Just Fine.
    if (is_array($base)) {
      $strings = [];
      foreach ($base as $base_table) {
        if (isset($fields[$base_table][$type])) {
          $strings += $fields[$base_table][$type];
        }
      }
      uasort($strings, '_views_sort_types');
      return $strings;
    }

    // @todo find out if this hack is right
//    if (isset($fields[$base][$type])) {
//      uasort($fields[$base][$type], '_views_sort_types');
//      return $fields[$base][$type];
//    }
    $all_fields = [];
    foreach ($fields as $key => $field) {
      if ($base == substr($key, 0, 4)) {
        if (isset($fields[$key][$type])) {
//          uasort($fields[$key][$type], '_views_sort_types');
          $all_fields = array_merge($all_fields, $fields[$key][$type]);
        }
      }
    }
    return $all_fields;
//    return [];
  }

  /**
   * Argument can be used as calendar argument.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $arg
   *
   * @return bool
   */
  public static function isCalendarArgument(ArgumentPluginBase $arg) {
    return $arg instanceof ViewsDateArg;
  }

  /**
   * Helper function to find the first date argument handler for this view.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param null $display_id
   *
   * @return \Drupal\calendar\DateArgumentWrapper|FALSE
   *   Returns the Date handler if one is found, or FALSE otherwise.
   */
  public static function getDateArgumentHandler(ViewExecutable $view, $display_id = NULL) {
    $all_arguments = [];
    if ($display_id) {
      // If we aren't dealing with current display we have to load the argument handlers.
      /** @var \Drupal\views\Plugin\ViewsHandlerManager $argument_manager */
      $argument_manager = \Drupal::getContainer()->get('plugin.manager.views.argument');

      $argument_configs = $view->getHandlers('argument', $display_id);
      foreach ($argument_configs as $argument_config) {
        $all_arguments[] = $argument_manager->getHandler($argument_config);
      }
    }
    else {
      // $view->argument actually contains an array of current arguments.
      $all_arguments = $view->argument;
    }
    if ($all_arguments) {
      $current_position = 0;
      /**
       * @var  $name
       * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $handler
       */
      foreach ($all_arguments as $name => $handler) {
        if (static::isCalendarArgument($handler)) {
          $wrapper = new DateArgumentWrapper($handler);
          $wrapper->setPosition($current_position);
          return $wrapper;
        }
        $current_position++;
      }
    }
    return FALSE;
  }

  /**
   * Limits a date format to include only elements from a given granularity array.
   *
   * Example:
   *   DateGranularity::limitFormat('F j, Y - H:i', ['year', 'month', 'day']);
   *   returns 'F j, Y'
   *
   * @param string $format
   *   A date format string.
   * @param array $array
   *   An array of allowed date parts, all others will be removed.
   *
   * @return string
   *   The format string with all other elements removed.
   */
  public static function limitFormat($format, $array) {
    // If punctuation has been escaped, remove the escaping. Done using strtr()
    // because it is easier than getting the escape character extracted using
    // preg_replace().
    $replace = [
      '\-' => '-',
      '\:' => ':',
      "\'" => "'",
      '\. ' => ' . ',
      '\,' => ',',
    ];
    $format = strtr($format, $replace);

    $format = str_replace('\T', ' ', $format);
    $format = str_replace('T', ' ', $format);

    $regex = [];
    // Create regular expressions to remove selected values from string.
    // Use (?<!\\\\) to keep escaped letters from being removed.
    foreach ($array as $element) {
      switch ($element) {
        case 'year':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[Yy])';
          break;
        case 'day':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[l|D|d|dS|j|jS|N|w|W|z]{1,2})';
          break;
        case 'month':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[FMmn])';
          break;
        case 'hour':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[HhGg])';
          break;
        case 'minute':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[i])';
          break;
        case 'second':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[s])';
          break;
        case 'timezone':
          $regex[] = '([\-/\.,:]?\s?(?<!\\\\)[TOZPe])';
          break;

      }
    }
    // Remove empty parentheses, brackets, pipes.
    $regex[] = '(\(\))';
    $regex[] = '(\[\])';
    $regex[] = '(\|\|)';

    // Remove selected values from string.
    $format = trim(preg_replace($regex, [], $format));
    // Remove orphaned punctuation at the beginning of the string.
    $format = preg_replace('`^([\-/\.,:\'])`', '', $format);
    // Remove orphaned punctuation at the end of the string.
    $format = preg_replace('([\-/,:\']$)', '', $format);
    $format = preg_replace('(\\$)', '', $format);

    // Trim any whitespace from the result.
    $format = trim($format);

    // After removing the non-desired parts of the format, test if the only things
    // left are escaped, non-date, characters. If so, return nothing.
    // Using S instead of w to pick up non-ASCII characters.
    $test = trim(preg_replace('(\\\\\S{1,3})', '', $format));
    if (empty($test)) {
      $format = '';
    }

    return $format;
  }

  /**
   * Get the display that handles a given granularity.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param $granularity
   *
   * @return mixed
   */
  static function getDisplayForGranularity(ViewExecutable $view, $granularity) {
    $displays = &drupal_static(__FUNCTION__, []);
    $view_name = $view->id();
    if (!array_key_exists($view_name, $displays) || (isset($displays[$view->id()]) && !(array_key_exists($granularity, $displays[$view->id()])))) {
      $displays[$view_name][$granularity] = NULL;

      foreach ($view->displayHandlers->getConfiguration() as $id => $display) {
        $loaded_display = $view->displayHandlers->get($id);
        if (!$loaded_display || !$view->displayHandlers->get($id)->isEnabled()) {
          continue;
        }

        if ($display['display_plugin'] != 'feed' && !empty($display['display_options']['path']) && !empty($display['display_options']['arguments'])) {

          // Set to the default value, reset below if another value is found.
          $argument = static::getDateArgumentHandler($view, $id);

          if ($argument->getGranularity() == $granularity) {

            $displays[$view->id()][$granularity] = $display['id'];
          }
        }
      }
    }
    return $displays[$view->id()][$granularity];
  }

  /**
   * Get the Url object that will link to the view for the given granularity and arguments.
   *
   * @todo Allow a View to link to other Views by itself for a certain granularity.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param $granularity
   * @param $arguments
   *
   * @return \Drupal\Core\Url|null
   */
  static function getURLForGranularity(ViewExecutable $view, $granularity, $arguments) {
    $granularity_links = $view->getStyle()->options['granularity_links'];
    if ($granularity_links[$granularity]) {
      /** @var RouteProvider $router */
      $router = \Drupal::getContainer()->get('router.route_provider');
      $route_name = $granularity_links[$granularity];
      // Check if route exists. $router->getRoutesByName will throw error if no match.
      $routes =$router->getRoutesByNames([$route_name]);
      if ($routes) {
        return Url::fromRoute($route_name, static::getViewRouteParameters($arguments));
      }
    }
    if ($display_id = static::getDisplayForGranularity($view, $granularity)) {
      // @todo Handle arguments in different positions
      // @todo Handle query string parameters.
      return static::getViewsURL($view, $display_id, $arguments);
    }

    return NULL;
  }

  /**
   * Get the Url object to link to a View display with given arguments.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param $display_id
   * @param array $args
   *
   * @return \Drupal\Core\Url
   */
  static function getViewsURL(ViewExecutable $view, $display_id, $args = []) {
    $route_parameters = static::getViewRouteParameters($args);
    $route_name = static::getDisplayRouteName($view->id(), $display_id);
    return Url::fromRoute($route_name, $route_parameters);
  }

  /**
   * Get Route name for a display
   *
   * Not sure where is documented but the route names are made in \Drupal\views\EventSubscriber\RouteSubscriber
   * @param $view_id
   * @param $display_id
   *
   * @return string
   */
  static function getDisplayRouteName($view_id, $display_id) {
    return 'view.' . $view_id . '.' . $display_id;
  }

  /**
   * @param $args
   *
   * @return array
   */
  static function getViewRouteParameters($args) {
    $route_parameters = [];
    $arg_position = 0;
    foreach ($args as $arg) {
      $route_parameters['arg_' . $arg_position] = $arg;
      $arg_position++;
    }
    return $route_parameters;
  }
}
