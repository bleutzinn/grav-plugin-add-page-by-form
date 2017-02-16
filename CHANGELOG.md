# v1.4.1
##  02/16/2017

1. [](#improved)
    * Simplified YAML frontmatter formatting as suggested in
https://github.com/getgrav/grav/issues/1287#issuecomment-279965492

# v1.4.0
##  02/12/2017

1. [](#new)
    * Added the ability to include the File field in the form. When `destination` is `@self` uploaded files are stored in the new page folder.

# v1.3.2
##  01/31/2017

1. [](#improved)
    * Removed the spyc.php class dependency; the page creation and YAML frontmatter handling is now done "the Grav way".

# v1.3.1
##  01/15/2017

1. [](#improved)
    * Added jQuery as an asset.

# v1.3.0
##  12/31/2016

1. [](#new)
    * Added the SimpleMDE Markdown Editor.

# v1.2.2
##  12/29/2016

1. [](#improved)
    * Removed note about the (previous) test release in the ReadMe.

# v1.2.1
##  12/29/2016

1. [](#improved)
    * Improved the usage explanation in the ReadMe.
    * Removed debug messages.

# v1.2.0
##  12/23/2016

1. [](#improved)
    * Improved new page route handling.

# v1.1.1
##  11/17/2016

1. [](#improved)
    * Removed dependency of PECL YAML function yaml_emit() in favor of using vendor/spyc.php class.

# v1.1.0
##  11/16/2016

1. [](#new)
    * Settings in the pagefrontmatter block in the form page frontmatter now are merged with values from form fields. Form field values ovverride the pagefrontmatter settings.

# v1.0.0
##  11/15/2016

1. [](#new)
    * Added an extra form field: 'author'
    * Added copying an (optional) frontmatter block from the form page frontmatter to the newly added page's frontmatter

# v0.2.0
##  11/13/2016

1. [](#new)
    * Plugin name changed to Add Page By Form (add-page-by-form)
    * Added timestamp as date in page header (date format is taken from plugin config)
    * Pages with identical titles are saved by adding a incremental number to the page slug (e.g. 'my-page\_2', 'my-page\_2', etc.)
    * Added error handling

# v0.1.0
##  11/08/2016

1. [](#new)
    * ChangeLog started...
