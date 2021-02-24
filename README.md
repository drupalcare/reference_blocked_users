# INTRODUCTION
There are two places you'll be looking for entity reference users.

The first field is "authored by", found under authoring information. It's
available in any content type you create. In order to see that field as a
content editor, you must have the permission to "administer content".

The second place where you will need to reference users is to add a custom
field in a content type as entity reference user. This field has a configuration
form that asks you to fill out "reference method","filter by" and "sort by".

In Drupal core,
../core/modules/user/src/Plugin/EntityReferenceSelection/UserSelection.php the
user selection class will provide the entity selection for the user and in order
to view all users, blocked/active, you must have the permission "administer
users".


Why did I make this module? I want the content editors to have the ability to be
able to reference all users (blocked and active) in authored by field or in any
custom entity reference user field.

There is some work available on Drupal to create a patch that gives you the
ability to include the blocked users in the configuration form for the custom
field. But, there is no solution for the "authored by" field, because the
authored by field does not have a configuration form.

I wanted one solution for both scenarios (the "authored by" and custom field).
The solution is to create a custom permission that can be assigned to any role
in the system, that allows that role to reference all users (blocked and active)
That's what this module does.

# DEPENDENCIES
None

# INSTALLATION
Install as any other contributed module.

# REQUIREMENTS
 None

# CONFIGURATION
None


# PERMISSIONS
 You must to assign the permission to the role.
