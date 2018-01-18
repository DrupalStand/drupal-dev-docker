<?php

/**
 * @file
 * Theme functions for the Calendar module.
 */
use Drupal\calendar\CalendarHelper;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\views\Views;

/**
 * Display a month view.
 */
function template_preprocess_calendar_month(&$vars) {
  $view = $vars['view'];
  $rows = $vars['rows'];
  if (empty($rows)) {
    $rows = [];
    $day_names = [];
  }
  elseif (sizeof($rows) > 1) {
    $day_names = array_shift($rows);
  }
  else {
    $day_names = $rows;
    $rows = [];
  }

  $month_rows = $rows;
  foreach ($rows as $weekno => $row) {
    // If this row is already rendered, don't do anything.
    if (!isset($row['data'])) {
      foreach ($row as $day => $data) {
        $cell = $data['data'];

        // If this cell is already rendered, like the weekno column,
        // move to the next item.
        if (!is_array($cell)) {
          $month_rows[$weekno][$day]['data'] = $cell;
          continue;
        }
        $data = $cell['datebox'];
        if ($cell['empty']) {
          $data .= $cell['empty'];
        }
        else {
          $data .= implode($cell['all_day']);
          foreach ($cell['items'] as $hour => $item) {
            $data .= implode($item);
          }
          $data .= $cell['link'];
        }
        if ($view->styleInfo->isMini()) {
          $month_rows[$weekno][$day]['data'] = $data;
        }
        else {
          $month_rows[$weekno][$day]['data'] = '<div class="inner">' . $data . '</div>';
        }
      }
    }
  }

  $vars['rows'] = $month_rows;
  $vars['day_names'] = $day_names;

  $vars['display_type'] = $view->dateInfo->getGranularity();
  $vars['min_date_formatted'] = $view->dateInfo->getMinDate()->format(DATETIME_DATE_STORAGE_FORMAT);
  $vars['max_date_formatted'] = $view->dateInfo->getMaxDate()->format(DATETIME_DATE_STORAGE_FORMAT);
}

/**
 * Display a mini month view.
 */
function template_preprocess_calendar_mini(&$vars) {
  // Add in all the $vars added by the main calendar preprocessor.
  template_preprocess_calendar_month($vars);

  $view = $vars['view'];

  // Make sure that the calendar title links go to the month view,
  // not the year view (if this is embedded in a year display).
  $view->override_path =  calendar_granularity_path($view, 'month');

  $view->date_info->show_title = !empty($view->date_info->show_title) ? $view->date_info->show_title : FALSE;
  $vars['show_title'] = $view->date_info->show_title;
  $vars['view'] = $view;
}

/**
 * Display a year view.
 */
function template_preprocess_calendar_year(&$vars) {

  // Construct a calendar for each month, adjusting the $view passed to the
  // theme so it will produce the right results.
  $view = clone($vars['view']);
  $year = $view->dateInfo->getMinDate()->format('Y');
  $view->styleInfo->setShowWeekNumbers(FALSE);
  $rows = $vars['rows'];
  $months = [];
  foreach ($rows as $month => $month_rows) {
    // Set the month name.
    $month_format = ($vars['options']['name_size'] == 3) ? "M" : "F";
    $vars['options']['month_name'] = date($month_format, mktime(0, 0, 0, $month, 10));

    $view->dateInfo->setMinMonth($month);
    $view->dateInfo->setGranularity('month');
    $view->styleInfo->setMini(TRUE);
    $view->styleInfo->setShowNavigation(FALSE);
    $view->styleInfo->setShowTitle(TRUE);
    // TODO implement
//    $view->dateInfo->url = date_pager_url($view, NULL, \Drupal\Component\Datetime\DateTimePlus::datePad($year, 4) . '-' . \Drupal\Component\Datetime\DateTimePlus::datePad($month));
    // TODO handle timezone
    $view->dateInfo->setMinDate(new DateTime($view->dateInfo->getMinYear() . '-' . DateTimePlus::datePad($month) . '-01 00:00:00', new DateTimeZone('europe/brussels')));
    $maxDate = clone($view->dateInfo->getMinDate());
    $maxDate->modify('+1 month');
    $maxDate->modify('-1 second');
    $view->dateInfo->setMaxDate($maxDate);
    $months[$month] = [
      '#theme' => 'calendar_mini',
      '#view' => $view,
      '#options' => $vars['options'],
      '#rows' => $month_rows,
    ];
  }
  $view->styleInfo->setMini(FALSE);

  $vars['months'] = $months;
  $vars['view']->styleInfo->setShowNavigation(TRUE);
  $vars['view']->dateInfo->setGranularity('year');
  $vars['mini'] = FALSE;

}

/**
 * Display a day overlap view.
 */
function template_preprocess_calendar_day_overlap(&$vars) {
  template_preprocess_calendar_day($vars);
}

/**
 * Display a day view.
 */
function template_preprocess_calendar_day(&$vars) {
  $vars['view']->styleInfo->setShowWeekNumbers(FALSE);
  $view = $vars['view'];
  $rows = $vars['rows'];

  $item_count = 0;
  $by_hour_count = 0;
  /** @var \Drupal\calendar\CalendarStyleInfo $styleInfo */
  $styleInfo = $view->styleInfo;
  $grouping_field = !empty($styleInfo->getCustomGroupByField()) ? ($styleInfo->getCustomGroupByField()) : NULL;
  $display_overlap = !empty($styleInfo->getThemeStyle()) && !empty($styleInfo->getGroupByTimes());
  $vars['scroll_content'] = !empty($styleInfo->getThemeStyle()) && $styleInfo->getThemeStyle() == 1;

  if ($display_overlap) {
    $overlapped_items = [];
    $vars['#attached']['library'][] = 'calendar/calendar.overlapcss';
    if ((empty($vars['view']->live_preview) || !$vars['view']->live_preview) && !empty($vars['scroll_content'])) {
      $vars['#attached']['library'][] = 'calendar/calendar.overlapjs';
    }
    if (empty($vars['scroll_content'])) {
      $vars['#attached']['library'][] = 'calendar/calendar.noscroll';
    }
  }

  // If we're not grouping by time, move all items into the 'all day' array.
  if (empty($view->styleInfo->getGroupByTimes())) {
    // Items are already grouped into times, so we need to process each time-group.
    foreach ($rows['items'] as $time => $items) {
      foreach ($items as $item) {
        $rows['all_day'][] = $item;
      }
    }
    $rows['items'] = [];
  }

  $columns = [];

  // Move all_day items into the right columns and render them.
  $grouped_items = [];
  foreach ($rows['all_day'] as $item) {
    if (!empty($item->rendered_fields[$grouping_field])) {
      $column = $item->rendered_fields[$grouping_field];
      if (!in_array($column, $columns)) {
        $columns[] = $column;
      }
    }
    else {
      $column = 'Items';
    }

    $grouped_items[$column][] = [
      '#theme' => 'calendar_item',
      '#view' => $view,
      '#rendered_fields' => $item->getRenderedFields(),
      '#item' => $item,
    ];
    $item_count++;
  }
  $vars['rows']['all_day'] = $grouped_items;

  // Moved timed items into the right columns and render them.
  $start_times = $view->styleInfo->getGroupByTimes();
  $show_empty_times = $view->styleInfo->isShowEmptyTimes();
  $end_start_time = '23:59:59';
  $start_time = array_shift($start_times);
  $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;

  $grouped_items = [];
  foreach ($rows['items'] as &$items) {
    /** @var \Drupal\calendar\CalendarEvent $item */
    foreach ($items as &$item) {
      // The hour "buckets" in the display don't have a timezone associated with them,
      // so effectively are UTC. We need to transform the event time to UTC in order
      // to place it into the correct bucket.
      $timezone = $item->getTimezone();
      $itemDate = $item->getStartDate();
      $timeOffset = $timezone->getOffset($itemDate);
      $itemTimestamp = $itemDate->getTimestamp();
      $itemTimestamp += $timeOffset;
      $time = date('H:i:s', $itemTimestamp);

      if (!empty($item->rendered_fields[$grouping_field])) {
        $column = $item->rendered_fields[$grouping_field];
        if (!in_array($column, $columns)) {
          $columns[] = $column;
        }
      }
      else {
        $column = 'Items';
      }
      // Find the next time slot and fill it. Populate the skipped
      // slots if the option to show empty times was chosen.
      while ($time >= $next_start_time && $time < $end_start_time) {
        if ((!empty($show_empty_times) || $display_overlap) && !array_key_exists($start_time, $grouped_items)) {
          $grouped_items[$start_time]['values'] = [];
        }
        $start_time = $next_start_time;
        $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;
      }
      $grouped_items[$start_time]['values'][$column][] = $item;
      if ($display_overlap) {
        $time_end = date_format($item->getEndDate(), 'H:i:s');
        $item->time_start = $time;
        $item->time_end = $time_end;
        _calc_indents($overlapped_items, $time, $time_end, $item);
      }
      $item_count++;
      $by_hour_count++;
    }
  }

  // Finish out the day's time values if we want to see empty times.
  if (!empty($show_empty_times) || $display_overlap) {
    while ($start_time < $end_start_time && (!empty($start_time) || $display_overlap)) {
      if (empty($start_time)) {
        $start_times = $view->date_info->style_groupby_times;
        $start_time = array_shift($start_times);
        $next_start_time = array_shift($start_times);
      }
      if (!array_key_exists($start_time, $grouped_items)) {
        $grouped_items[$start_time]['values'] = [];
      }
      $start_time = $next_start_time;
      $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;
    }
  }

  // Do the headers last, once we know what the actual values are.
  $i = 0;
  $start_times = array_keys($grouped_items);
  foreach ($start_times as $start_time) {
    $next_start_time = array_key_exists($i + 1, $start_times) ? $start_times[$i + 1] : '23:59:59';
    $variables = [
      'start_time' => $start_time,
      'next_start_time' => $next_start_time,
      'curday_date' => $rows['#date'],
    ];

    $heading = template_preprocess_calendar_time_row_heading($variables);

    $grouped_items[$start_time]['hour'] = $heading['hour'];
    $grouped_items[$start_time]['ampm'] = $heading['ampm'];
    foreach ($grouped_items[$start_time]['values'] as $column => &$items) {
      foreach ($items as $index => &$item) {
        $group_time = NULL;
        $divisor = NULL;
        if ($display_overlap) {
          if ($view->styleInfo->getGroupByTimes() == 'half') {
            $group_time = 30;
            $divisor = 7.5;
          }
          elseif ($view->styleInfo->getGroupByTimes() == 'hour') {
            $group_time = 60;
            $divisor = 15;
          }
          else {
            $item->class = '';
          }
          if (!empty($group_time) && !empty($divisor)) {
            $start_minute = intval(substr($start_time, 3, 2));
            $offset = round((date_format($item->date_start, 'i') - $start_minute) / $divisor);
            $duration = round(($item->date_end->format('U') - $item->date_start->format('U')) / 60 / $divisor);
            $item->class = 'd_' . $duration . ' o_' . $offset . ' i_' . $item->indent . ' md_' . min($item->max_depth, 5);
          }
        }

        $grouped_items[$start_time]['values'][$column][$index] = [
          '#theme' => 'calendar_item',
          '#view' => $view,
          '#rendered_fields' => $item->getRenderedFields(),
          '#item' => $item,
        ];

      }
    }
    $i++;
  }
  ksort($grouped_items);
  $vars['rows']['items'] = $grouped_items;

  if (empty($columns)) {
    $columns = ['Items'];
  }
  $vars['columns'] = $columns;

  $vars['agenda_hour_class'] = 'calendar-agenda-hour';
  $first_column_width = 10;

  if (empty($view->styleInfo->getCustomGroupByTimes())) {
    $vars['agenda_hour_class'] .= ' calendar-agenda-no-hours';
    $first_column_width = 1;
  }

  $vars['first_column_width'] = $first_column_width;
  if (count($columns)) {
    $vars['column_width'] = round((100 - $first_column_width)/count($columns));
  }
  else {
    $vars['column_width'] = (100 - $first_column_width);
  }
  $vars['item_count'] = $item_count;
  $vars['by_hour_count'] = $by_hour_count;
  $vars['start_times'] = $view->styleInfo->getGroupByTimes();
}

/**
 * Display a week overlap view.
 */
function template_preprocess_calendar_week_overlap(&$vars) {
  template_preprocess_calendar_week($vars);
}

/**
 * Display a week view.
 */
function template_preprocess_calendar_week(&$vars) {
  $vars['view']->styleInfo->setShowWeekNumbers(FALSE);

  $view = $vars['view'];
  $rows = $vars['rows'];
  $item_count = 0;
  $by_hour_count = 0;
  $start_time = NULL;
  $columns = [];

  if (sizeof($rows) > 1) {
    $day_names = array_shift($rows);
  }
  else {
    $day_names = $rows;
    $rows = [];
  }

  // Moved timed items into the right columns and render them.
  $show_empty_times = $view->styleInfo->isShowEmptyTimes();
  $end_start_time = '23:59:59';

  $grouped_items = [];

  // Pass the multiday buckets.
  $vars['all_day'] = $rows['multiday_buckets'];

  // Remove the count for singleday
  $vars['multiday_rows'] = max(0, $rows['total_rows'] - 1);
  $display_overlap = ($view->styleInfo->getMultiDayTheme() == '1' && !empty($view->styleInfo->getThemeStyle()));
  $vars['display_overlap'] = $display_overlap;
  $vars['scroll_content'] = !empty($view->styleInfo->getThemeStyle()) && $view->styleInfo->getThemeStyle() == 1;

  // Add optional css
  if ($display_overlap) {

    $vars['#attached']['library'][] = 'calendar/calendar.overlapcss';
    if ((empty($vars['view']->live_preview) || !$vars['view']->live_preview) && !empty($vars['scroll_content'])) {
      $vars['#attached']['library'][] = 'calendar/calendar.overlapjs';
    }
    if (empty($vars['scroll_content'])) {
      $vars['#attached']['library'][] = 'calendar/calendar.noscroll';
    }

    $overlapped_items = [[], [], [], [], [], [], []];

    // Locate the first item
    $first_time = '23:59:59';
    $first_time_index = -1;
    for ($i = 0; $i < 7; $i++) {
      if (count($rows['singleday_buckets'][$i]) > 0) {
        $time_slot = reset($rows['singleday_buckets'][$i]);
        // TODO item should not be empty at this point, find out why empty days
        // get to this point, fix that, and remove this hack
        if (isset($time_slot[0]['item'])) {
          $time = date_format($time_slot[0]['item']->getStartDate(), 'H:i:s');
          if ($time < $first_time) {
            $first_time = $time;
            $first_time_index = $i;
          }
        }
      }
    }
    if ($first_time_index > -1) {
      $rows['singleday_buckets'][$first_time_index][$first_time][0]['is_first'] = TRUE;
    }
  }

  // If we're not grouping by time, move all items into the 'all day' array.
  if (empty($view->styleInfo->getGroupByTimes())) {
    $add_row = FALSE;
    foreach ($vars['all_day'] as $index => &$day ) {
      foreach ($rows['singleday_buckets'][$index] as $item) {
        foreach ($item as $event) {
          $day[] = $event;
          $add_row = TRUE;
        }
      }
    }
    if ($add_row) {
      $vars['multiday_rows']++;
    }
  }
  else {
    foreach ($rows['singleday_buckets'] as $wday => $singleday_row) {
      $columns[] = $wday;
      foreach ($singleday_row as &$row) {
        $start_times = $view->styleInfo->getGroupByTimes();
        $start_time = array_shift($start_times);
        $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;
        foreach ($row as &$item) {
          // todo find out how empty days should be handled properly
          if ($item['entry']['#theme'] === 'calendar_empty_day') {
            continue;
          }
          // todo find out what to do when the item is no event
          if (!is_object($item['item'])) {
            continue;
          }
          
          // The hour "buckets" in the display don't have a timezone associated with them,
          // so effectively are UTC. We need to transform the event time to UTC in order 
          // to place it into the correct bucket.
          $timezone = $item['item']->getTimezone();
          $itemDate = $item['item']->getStartDate();
          $timeOffset = $timezone->getOffset($itemDate);
          $itemTimestamp = $itemDate->getTimestamp();
          $itemTimestamp += $timeOffset;
          $time = date('H:i:s', $itemTimestamp);

          if ($item['item']->isAllDay()) {
            $vars['all_day'][$item['wday']][] = $item;
            if ($vars['multiday_rows'] == 0) {
              $vars['multiday_rows']++;
            }
          }
          else {
            // Find the next time slot and fill it. Populate the skipped
            // slots if the option to show empty times was chosen.
            while ($time >= $next_start_time && $time < $end_start_time) {
              if (($show_empty_times || $display_overlap) && !array_key_exists($start_time, $grouped_items)) {
                $grouped_items[$start_time]['values'][$wday] = [];
              }
              $start_time = $next_start_time;
              $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;
            }
            $grouped_items[$start_time]['values'][$wday][] = &$item;
            if ($display_overlap) {
              $date_end = date_format($item['item']->getEndDate(), 'H:i:s');
              _calc_indents($overlapped_items[$wday], $time, $date_end, $item);
            }
            $item_count++;
            $by_hour_count++;
          }
        }
      }
      // Finish out the day's time values if we want to see empty times.
      if ($show_empty_times || $display_overlap) {
        while ($start_time < $end_start_time && ($start_time != NULL || $display_overlap)) {
          if ($start_time == NULL) {
            $start_times = $view->styleInfo->getGroupByTimes();
            $start_time = array_shift($start_times);
            $next_start_time = array_shift($start_times);
          }
          if (!array_key_exists($start_time, $grouped_items)) {
            $grouped_items[$start_time]['values'][$wday] = [];
          }
          $start_time = $next_start_time;
          $next_start_time = count($start_times) ? array_shift($start_times) : $end_start_time;
        }
      }
      ksort($grouped_items);
    }
  }

  // Do the headers last, once we know what the actual values are.
  $i = 0;
  $start_times = array_keys($grouped_items);
  foreach ($start_times as $start_time) {
    $next_start_time = array_key_exists($i + 1, $start_times) ? $start_times[$i + 1] : '23:59:59';
    $variables = [
      'start_time' => $start_time,
      'next_start_time' => $next_start_time,
      'curday_date' => (isset($row['data'])) ? $row['data']['date'] : NULL,
    ];

    $heading = template_preprocess_calendar_time_row_heading($variables);

    $grouped_items[$start_time]['hour'] = $heading['hour'];
    $grouped_items[$start_time]['ampm'] = $heading['ampm'];
    $grouped_items[$start_time]['time'] = $start_time;
    if ($display_overlap) {
      foreach ($grouped_items[$start_time]['values'] as $wday => &$items) {
        foreach ($items as &$item) {
          if ($display_overlap) {
            $group_time = NULL;
            $divisor = NULL;
            if ($view->styleInfo->getGroupByTimes() == 'half'){
              $group_time = 30;
              $divisor = 7.5;
            }
            elseif ($view->styleInfo->getGroupByTimes() == 'hour'){
              $group_time = 60;
              $divisor = 15;
            }
            else {
              $item['class'] = '';
            }
            if (!empty($group_time) && !empty($divisor)) {
              $start_minute = intval(substr($start_time, 3, 2));
              $offset = round((date_format($item['item']->date_start, 'i') - $start_minute) / $divisor);
              $duration = round(($item['item']->date_end->format('U') - $item['item']->date_start->format('U')) / 60 / $divisor);
              $item['class'] = 'd_' . $duration . ' o_' . $offset . ' i_' . $item['indent'] . ' md_' . min($item['max_depth'], 5);
            }
          }
        }
      }
    }
  }

  $vars['items'] = $grouped_items;

  $vars['day_names'] = $day_names;
  $vars['columns'] = $columns;
  $vars['start_times'] = $start_times;
  $vars['first_time'] = !empty($first_time) ? $first_time : '';

  $header_ids = [];
  foreach ($day_names as $key => $value) {
    $header_ids[$key] = $value['header_id'];
  }
  $vars['header_ids'] = $header_ids;

  $vars['agenda_hour_class'] = 'calendar-agenda-hour';
  $first_column_width = 10;

  if (empty($view->styleInfo->getCustomGroupByTimes())) {
    $vars['agenda_hour_class'] .= ' calendar-agenda-no-hours';
    $first_column_width = 1;
  }
  $vars['item_count'] = $item_count;
  $vars['by_hour_count'] = $by_hour_count;
}

/**
 * Implementation of hook_preprocess_calendar_item().
 */
function template_preprocess_calendar_item(&$vars) {
  // At the last possible minute we fix the values in rendered_fields so it
  // contains the correct rendered content for the type of item and item display.
  /** @var \Drupal\calendar\CalendarEvent $item */
  $item = $vars['item'];

  $multiday_hidden = !empty($vars['view']->style_options['multiday_hidden']) ? $vars['view']->style_options['multiday_hidden'] : [];

  if (!empty($item->getRenderedFields()) && empty($item->getIsMultiDay())) {
    $vars['rendered_fields'] = [$item->getRenderedFields()];
  }
  foreach ($vars['view']->field as $id => $field) {
    foreach ($vars['rendered_fields'] as $index => &$render_array) {

    }
    if ($field->options['exclude'] || (!empty($item->getIsMultiDay()) && in_array($id, $multiday_hidden))) {
      unset($render_array[$field->field]);
    }
  }

}

/**
 * Create the calendar date box.
 */
function template_preprocess_calendar_datebox(&$vars) {
  $date = $vars['date'];
  $view = $vars['view'];
  $vars['day'] = intval(substr($date, 8, 2));
//  $force_view_url = !empty($view->dateInfo->block) ? TRUE : FALSE;

  $full_date_arg = str_replace('-', '', $date);
  $day_url = CalendarHelper::getURLForGranularity($view, 'day', [$full_date_arg]);
//  $vars['url'] = str_replace([$month_path, $year_path], $day_path, date_pager_url($view, NULL, $date, $force_view_url));
  if ($day_url) {
    $vars['link'] = \Drupal::l($vars['day'],$day_url);
  }
  else {
    $vars['link'] = $vars['day'];
  }



  $vars['granularity'] = $view->dateInfo->getGranularity();
  $vars['mini'] = !empty($view->styleInfo->isMini());
  if ($vars['mini']) {
    if (!empty($vars['selected'])) {
      $vars['class'] = 'mini-day-on';
    }
    else {
      $vars['class'] = 'mini-day-off';
    }
  }
  else {
    $vars['class'] = 'day';
  }
}

/**
 * Format an calendar month node for display.
 */
function template_preprocess_calendar_month_multiple_entity(&$vars) {
  // @todo Fix when multiday events are supported again.
  $view = $vars['view'];
  $curday = $vars['curday'];
  $count = $vars['count'];
  $ids = $vars['ids'];

  // get the year month and date
  $parts = explode('-', substr($curday, 0, 10));
  $year = $parts[0];
  $month = intval($parts[1]);
  $day = intval($parts[2]);

  // create the link to the day
  $month_path = calendar_granularity_path($view, 'month');
  $day_path = calendar_granularity_path($view, 'day');
  $vars['link'] = str_replace($month_path, $day_path, date_pager_url($view, NULL, \Drupal\Component\Datetime\DateTimePlus::datePad($year, 4) . '-' . \Drupal\Component\Datetime\DateTimePlus::datePad($month) . '-' . \Drupal\Component\Datetime\DateTimePlus::datePad($day)));
}

/**
 * Theme function for rendering views fields as a calendar 'item'.
 *
 * $vars['rendered_fields'] = An array of the rendered display of each field in the View.
 * $vars['item'] = The source data for this item.
 * $vars['view'] = The view that this item is displayed on.
 *
 * @TODO We need some options about how to combine rendered fields.
 * Fields rendered in multiday containers need to be inline.
 */

/**
 * Format the time row headings in the week and day view.
 *
 * TODO find out if this should be helper function instead of a preprocessor
 */
function template_preprocess_calendar_time_row_heading($vars) {
  $start_time = $vars['start_time'];
  $next_start_time = $vars['next_start_time'];
  $curday_date = $vars['curday_date'];
  static $format_hour, $format_ampm;
  if (empty($format_hour)) {
    $format = DateFormat::load('short')->getPattern();
    if (substr($start_time, -5) == '00:00' && substr($next_start_time, -5) == '00:00') {
      $limit = ['hour'];
    }
    else {
      $limit = ['hour', 'minute'];
    }

    $granularities = [
      'year',
      'month',
      'day',
      'hour',
      'minute',
      'second',
      'timezone',
    ];
    $reversedLimit = array_diff($granularities, $limit);

    $format_hour = str_replace(['a', 'A'], '', CalendarHelper::limitFormat($format, $reversedLimit));
    $format_ampm = strstr($format, 'a') ? 'a' : (strstr($format, 'A') ? 'A' : '');
  }
  if ($start_time == '00:00:00' && $next_start_time == '23:59:59') {
    $hour = t('All times');
  }
  elseif ($start_time == '00:00:00') {
    $date = date_create($curday_date . ' ' . $next_start_time);
    $hour = t('Before @time', ['@time' => date_format($date, $format_hour)]);
  }
  else {
    $date = date_create($curday_date . ' ' . $start_time);
    $hour = date_format($date, $format_hour);
  }
  if (!empty($date)) {
    $ampm = date_format($date, $format_ampm);
  }
  else {
    $ampm = '';
  }
  return ['hour' => $hour, 'ampm' => $ampm];
}

/**
 * Format a node stripe legend.
 *
 * @param $vars array
 *   An array with variables.
 *
 * @return string
 *   The HTML output of the legend.
 */
function template_preprocess_calendar_stripe_legend(&$vars) {

  if (empty($vars) || !$view = $vars['view_and_display_id']) {
    return '';
  }

  list($view_name, $display_id) = explode(':', $view);

  $view = Views::getView($view_name);
  $view->setDisplay($display_id);
  $row_options = $view->display_handler->getOption('row')['options'];

  $legend_type = $row_options['colors']['legend'];
  $display_options = $row_options['colors']['calendar_colors_' . $legend_type];
  $options = [];
  switch ($legend_type) {

    case 'type':
      $options = node_type_get_names();
      break;

    // @todo handle taxonomy legends
    case 'taxonomy':
      $vocabularies = (array) $row_options['colors']['calendar_colors_vocabulary'];
      $term_colors = $row_options['colors']['calendar_colors_taxonomy'];
      foreach ($vocabularies as $field_name => $vid) {
        $vocab = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadTree($vid);
        foreach ($vocab as $key => $term) {
          $options[$term->tid] = $term->name;
        }
      }
      break;
  }

  $headers = [
    ['label' => t('Item')],
    ['label' => t('Key')]
  ];

  $rows = [];
  foreach ($options as $key => $label) {
    $stripe = array_key_exists($key, $display_options) ? $display_options[$key] : CALENDAR_EMPTY_STRIPE;
    if ($stripe != CALENDAR_EMPTY_STRIPE) {
      $rows[] = [
        'label' => $label,
        'stripe' => $stripe,
      ];
    }
  }

  if (!empty($rows)) {
    $vars['headers'] = $headers;
    $vars['rows'] = $rows;
  }
}

/**
 * Indent items based off a nested tree structure of overlapping items.
 *
 * @param array $overlapped_items
 *   Tree of overlapped items
 * @param date $start
 *   Start time of the event
 * @param date $end
 *   End tiem of the event
 * @param array $item
 *   The event to add to the tree
 * @param int $depth
 *   Current depth of the tree
 * @return rc
 *   Returns an array with the max depth of the branch and whether an overlap
 *   occurred.
 */
function _calc_indents(&$overlapped_items, $start, $end, &$item, $depth = 0) {

  // Are there any items at this depth?
  if (!empty($overlapped_items)) {

    // Iterate for each item as this depth and see if we overlap
    foreach ($overlapped_items as $index => &$entry) {

      // We search depth-first, so if there are children for this item, recurse into
      // each child tree looking for an overlap
      if (!empty($entry['children'])) {
        $rc = _calc_indents($entry['children'], $start, $end, $item, $depth + 1);

        // Was there an overlap in the child tree?
        if ($rc['overlap']) {
          if (is_object($entry['item'])) {
            $entry['item']->indent = _calc_indent($entry['depth'], $rc['max_depth']);
            $entry['item']->max_depth = $rc['max_depth'];
          }
          else {
            $entry['item']['indent'] = _calc_indent($entry['depth'], $rc['max_depth']);
            $entry['item']['max_depth'] = $rc['max_depth'];
          }

          // There was an overlap, pop out of this depth
          return $rc;
        }
      }
      // No, child overlap, so check if we overlap this item
      if ($start >= $entry['start'] && $start < $entry['end']) {

        // We overlap, create an overlapping entry
        $entry['children'][] = ['item' => &$item, 'depth' => $depth + 1, 'start' => $start, 'end' => $end, 'children' => []];
        if (is_object($entry['item'])) {
          $max_depth = max($entry['item']->max_depth, $depth + 1);
          $entry['item']->indent = _calc_indent($depth, $max_depth);
          $entry['item']->max_depth = $max_depth;
        }
        else {
          $max_depth = max($entry['item']['max_depth'], $depth + 1);
          $entry['item']['indent'] = _calc_indent($depth, $max_depth);
          $entry['item']['max_depth'] = $max_depth;
        }
        if (is_object($item)) {
          $item->indent = _calc_indent($depth + 1, $max_depth);
          $item->max_depth = $max_depth;
        }
        else {
          $item['indent'] = _calc_indent($depth + 1, $max_depth);
          $item['max_depth'] = $max_depth;
        }

        // We overlap, so pop out of this depth
        return ['overlap' => TRUE, 'max_depth' => $max_depth];
      }
    }

    // If there are items at this depth, but no overlap, then return no overlap and pop
    // out of this depth
    if ($depth > 0) {
      return ['overlap' => FALSE, 'max_depth' => 0];
    }
  }

  // No overlap at any depth, reset the array of overlaps
  if ($depth == 0) {
    reset($overlapped_items);
    $overlapped_items[0] = ['item' => &$item, 'depth' => $depth, 'start' => $start, 'end' => $end, 'children' => []];
  }
  else {
    $overlapped_items[] = ['item' => &$item, 'depth' => $depth, 'start' => $start, 'end' => $end, 'children' => []];
  }

  if (is_object($item)) {
    $item->indent = _calc_indent($depth, $depth);
    $item->max_depth = $depth;
  }
  else {
    $item['indent'] = _calc_indent($depth, $depth);
    $item['max_depth'] = $depth;
  }
  return ['overlap' => FALSE, 'max_depth' => $depth];
}

/**
 * Calculates the indent based of the current depth and the depth of this branch
 * in the tree.
 *
 * @param int $cur_depth
 * @param int $depth
 * @return number
 */
function _calc_indent( $cur_depth, $depth ) {
  return round(10 * $cur_depth / ($depth + 1));
}

/** @} End of addtogroup themeable */
