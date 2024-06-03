CONTENTS OF THIS FILE
---------------------

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Troubleshooting
  * FAQ
  * Maintainers

INTRODUCTION
------------

The Chathive Chatbot module embeds the chatbot widget from https://chathive.co and
allows you to configure it.

  * For a full description of the module, visit the project page:
   https://www.drupal.org/project/chathive

  * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/chathive


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

You do need to have a valid API key and have your website whitelisted
as an allowed domain to be able to embed the chatbot. More information
can be found under the section configuration.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit
    https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

  * Configure the user permissions in Administration » People » Permissions:

    - administer chathive chatbot

    Users with this permission will be able to change any of the configuration
    settings provided by the module.

  * Customize the Chathive settings in Administration » Configuration » System
    » Chathive.

    The module expects a valid API key from https://chathive.co.

    The pages (request path) option allows you to show the chatbot only on
    specific pages. The negate option allows you to reverse the logic. This
    logic is inherited from the Drupal core request path condition.

MAINTAINERS
-----------

Current maintainers:
* Peter Neyens (p-neyens) - https://www.drupal.org/user/1982856

This project has been sponsored by:
* Tobania
