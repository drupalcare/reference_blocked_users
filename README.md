# Reference Blocked Users

## Contents

- Introduction
- Requirements
- Installation
- Configuration
- Permissions
- Maintainers

## Introduction

There are two places where you reference users through an entity reference
field:

1. The **Authored by** field found under *Authoring information* on any content
   type. To see it as a content editor you need the *Administer content*
   permission.
2. A custom **entity reference** field that points at the user entity. Its
   configuration form lets you choose a reference method, a "filter by" option
   and a "sort by" option.

In Drupal core, `UserSelection`
(`core/modules/user/src/Plugin/EntityReferenceSelection/UserSelection.php`)
provides the selection logic for user reference fields, and it only lets you
reference **blocked** users if you have the powerful *Administer users*
permission.

This module lets content editors reference **all** users, both active and
blocked, in the "Authored by" field and in any custom user reference field,
*without* granting them *Administer users*. It does this by adding a single,
assignable permission, **Reference blocked users**. Existing patches on
drupal.org only address the custom field's configuration form; because the
"Authored by" field has no configuration form, this permission-based approach
is the one solution that covers both cases.

For users who have the permission, the autocomplete also matches on the **email
address** as well as the username. Core only ever matches the username.

## Requirements

Drupal 11.3+ or Drupal 12. No other dependencies.

## Installation

Install as you would normally install a contributed Drupal module. See
[Installing Drupal modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.

## Configuration

This module has no settings page. Its behavior is controlled entirely by the
permission below.

## Permissions

Grant the **Reference blocked users** permission to any role that should be able
to reference active and blocked users in user reference fields (including
"Authored by"). Users who already have *Administer users* keep core's default
behavior.

## Maintainers

- Alaa Haddad (flashwebcenter) - https://www.drupal.org/u/flashwebcenter
