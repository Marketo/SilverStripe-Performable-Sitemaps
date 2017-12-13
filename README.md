# Performable Site Map Module
=======

## Documentation

Creates a Sitemap PageType and generates sitemap.xml files specific to each domain
Also uses SQL Queries instead of the ORM for performance

Add the following to you config if you want the sitemap to follow the ShowInMenus or ShowInSearch Page setting.
~~~
SiteMap:
  ObserveShowInMenus: true
  ObserveShowInSearch: true
~~~

## Maintainer Contact

Kirk Mayo

<kmayo (at) marketo (dot) com>

## Requirements

* SilverStripe 3.2
* SilverStripe Queued Job Module

## Composer Installation

  composer require Marketo/SilverStripe-Performable-Sitemaps

## TODO

Tests
