# Drupal Calendar 8.x

## Introduction

The calendar module makes it possible to create calendars with views, based on
date fields on nodes and taxonomy terms.

## Simple setup

The easiest way to set up a calendar is by using the "add from template"
functionality provided by the Views Templates module. After enabling the module
and clearing the cache, a link "add from template" should appear on the views
overview page. This should list the different options to create a calendar
based on core fields (created and updated) or any other custom defined date
field.

## Currently unsupported

Due to limitations of the core DateTime module, and the lack of a contrib Date
module, some functionality is not available at this point. These include, but
are not limited to:

- end date support
- repeating date support
- Organic Groups support

Support for these will be added once they are made available through core
updates, a D8 version of the date module or stand-alone D8 modules.

## CACHING & PERFORMANCE

Calendars are very time-consuming to process, so caching is recommended.
You can set up caching options for your calendar in the Advanced section
of the View settings. Even setting a lifetime of 1 hour will provide some 
benefits if you can live with a calendar that is 1 hour out of date. 
Set the lifetime to the longest value possible. You will need to clear 
the caches manually or using custom code if the content of the calendar 
changes before the cache lifetime expires. 

The recommended settings for time-based caching are:
// @todo Update for 8.x Views cache settings
- Query results
Cache the query only when not using ajax. Do not cache the query
on any display that uses ajax for its results.

- Rendered output:
Always try to cache rendered output. Rendering the output is the most
time-consuming part of building a calendar. This is especially
important for the Year view, less important for the Day view.

As with all caching options, please test to be sure that caching
is working as desired.

If performance is a problem, or you have a lot of items in the calendar,
you may want to remove the Year view completely.
