# v3.0.3
##  05/29/2020

1. [](#bugfix)
   - Fixed not handling overwrite_mode setting properly, issue [#54](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/54), thanks to mooomooo for reporting and testing
   
# v3.0.2
##  05/17/2020

1. [](#improved)
   - Yet another release. This time just to synchronize the versioning number in 'bleuprints.yaml' and this Changelog. For interesting changes see v3.0.0.

# v3.0.1
##  05/17/2020

1. [](#improved)
   - Removed a newline between the version number and the date in this Changelog file in an attempt to restore the correct display of this file in the Grav repository

# v3.0.0
##  05/03/2020

1. [](#new)
   - New 'overwrite_mode' option 'edit' allows for editing a page. Note: yet undocumented.
   - Removing upload files is now handled
1. [](#bugfix)
   - Switched to Laravel str_slug function to remedy problems with hyphens on some Windows systems
1. [](#improved)
   * (Possibly breaking change:) Changed uploaded files data structure from numeric array to associative array
   * (Possibly breaking change:) Changed default setting of 'physical_template_name' to 'true'
   * Minimum Grav version is set to 1.6

# v2.4.1
##  04/12/2020

1. [](#bugfix)
   * Fixed a bug ([issue #52](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/52)) where an empty value for the 'slug' variable would delete folder(s). Thanks to [anton-mellit](https://github.com/anton-mellit) for reporting this.

# v2.4.0
##  01/28/2020

1. [](#new)
    * Added a new config variable 'physical_template_name' to make using the new page template name as the new page's file name optional.
1. [](#improved)
    * Removed the Changelog entry that was included in [https://github.com/bleutzinn/grav-plugin-add-page-by-form/pull/47](PR #47) from this file as it was not in the Grav Changelog format and prevented changes showing up correctly in the Grav Plugins download section.

# v2.3.5
## 01/25/2020

1. [](#new)
    * Add option to suppress loading of simpleMDE assets (reduces overhead if its known it will not be used) 

# v2.3.4
##  01/20/2020

1. [](#bugfix)
    * Prepared a new release to mainly consolidate the fix "Use moveTo method not native copy to move uploaded files to final destination" and to bring this changelog format back in line with Grav requirements. Thanks to Dave Nichols (pd-giz-dave).
1. [](#improved)
    * Also included the ability to use the new page template name as the new page's folder name. Thanks to Dave Nichols (pd-giz-dave).

# v2.3.3
##  12/05/2019

1. [](#bugfix)
    * Fixed inconsistancies in version numbering which prevented the addition of the latest updates in the Grav Plugin repository. 

# v2.3.2
##  11/24/2019

1. [](#bugfix)
    * Prepared a new release to fix a bug in version numbering. The letter "v" appears to be case sensitive. The versions 2.3.0 and 2.3.1 were tagged with a capital "V" as V2.3.0 and V2.3.1 respectively. Previous versions were tagged using a lowercase "v". This difference causes the Grav Repository to think these are two different plugins.

# v2.3.1
##  10/10/2019

1. [](#bugfix)
    * Fixed a subsequent failure to save file uploads to new page folder (form field File with `destination: @self`) introduced with Grav version 1.6.11 ([issue #44](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/44)). Thanks goes to mahagr for tips and to tranduyhung for the fix itself.

# v2.3.0
##  06/06/2019

1. [](#bugfix)
    * Fixed the failure to save file uploads to new page folder (form field File with `destination: @self`) introduced with Grav version 1.6 ([issue #40](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/40))

# v2.2.0
##  04/15/2018

1. [](#new)
    * Added support for `process.redirect: @self-admin` ([issue #13](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/31))
1. [](#improved)
    * Fixed a problem with uploading files

# v2.1.0
##  09/18/2017

1. [](#new)
    * Added support for taxonomy types and tags

# v2.0.0
##  06/18/2017

1. [](#new)
    * Added support for multiple textarea editors ([issue #21](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/21))
    * Added support for `process.redirect: @self` ([issue #23](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/23))
    * Added the `overwrite_mode` configuration frontmatter variable
    * Added the `subroute` configuration frontmatter variable
    * Added the `slug_field` configuration frontmatter variable 
    * Added filename sanitizing of uploaded files
1. [](#improved)
    * In the form page frontmatter configuration variables are separated from variables which main purpose it is to get passed on to the new page
    * Uploaded file properties are now included in the new page frontmatter
    * Improved safe slug generator
    * Removed "use editor" option from `blueprints.yaml` (to allow [issue #21](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/21))
    * Extended `blueprints.yaml` to set "fallback" configuration values
1. [](#bugfix)
    * Fixed an issue with form pages outside the web root ([issue #20](https://github.com/bleutzinn/grav-plugin-add-page-by-form/issues/20))
    * Fixed a problem that prevented having different destinations for file uploads

# v1.4.2
##  03/22/2017

1. [](#new)
    * Cleaned up code for release.

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
