<?php

namespace Drupal\calendar\Template;

/**
 * A class providing Calendar Twig extensions.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'calendar';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('calendar_stripe', [$this, 'getCalendarStripe'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Adds a striped background to the passed event.
   *
   * @param \Drupal\calendar\CalendarEvent $event
   *
   * @return string
   *   A HTML output string.
   */
  public function getCalendarStripe($event) {
    if (empty($event->getStripeHexes()) || (!count($event->getStripeHexes()))) {
      return;
    }
    $output = '';
    foreach ($event->getStripeLabels() as $k => $stripe_label) {
      if (!empty($event->getStripeHexes()[$k]) && !empty($stripe_label)) {
        $output .= '<div style="background-color:' . $event->getStripeHexes()[$k] . ';color:' . $event->getStripeHexes()[$k] . '" class="stripe" title="Key: ' . $event->getStripeLabels()[$k] . '">&nbsp;</div>' . "\n";
      }
    }
    return $output;
  }
}