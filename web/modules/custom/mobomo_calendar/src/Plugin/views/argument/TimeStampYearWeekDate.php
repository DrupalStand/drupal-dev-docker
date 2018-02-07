<?php

namespace Drupal\calendar\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a day.
 *
 * @ViewsArgument("date_year_week")
 */
class TimeStampYearWeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'YW';

}
