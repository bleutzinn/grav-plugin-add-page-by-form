# Add Page By Form Plugin

The **Add Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows users to add a new page by filling in a form.

This plugin uses the possibilities of [custom frontmatter](https://learn.getgrav.org/content/headers#custom-page-headers). By setting your own variables in the form page frontmatter a priori and optionally letting users override these variable values by filling in corresponding form fields you can transport these data into the new page frontmatter.

The passing on of both the default settings and form field values entered by the end user to the new page frontmatter makes for an extremely configurable solution.   
By mixing default settings and configuring the page form you can to a large extent control the appearence and behaviour of the newly added page by using the frontmatter variables present in the new page in a Twig template.

For example, a new page can act as a new blog post simply by setting the appropriate template variable in the form page definition (with the AntiMatter theme, this is `template: item`). That template value is inserted in the new page frontmatter and, so, will be used by Grav to display the new page.


## Security Warning

Allowing anonymous visitors to create pages is a potential website security risk. It is **strongly advised** to use the [Grav Login Plugin](https://github.com/getgrav/grav-plugin-login) or the [Private Grav Plugin](https://github.com/Diyzzuf/grav-plugin-private) **to restrict the page creation to logged in users only**.

This plugin itself does not provide any security measures. Please take this in consideration before using this plugin.


## Installation and Configuration

Typically the plugin should be installed via [GPM](http://learn.getgrav.org/advanced/grav-gpm) (Grav Package Manager):

```
$ bin/gpm install add-page-by-form
```

Alternatively it can be installed via the [Admin Plugin](http://learn.getgrav.org/admin-panel/plugins).

Another option is to manualy install the plugin by [downloading](https://github.com/bleutzinn/grav-plugin-add-page-by-form/archive/master.zip) the plugin as a zip file. Copy the zip file to your `/user/plugins` directory, unzip it there and rename the folder to `add-page-by-form`.

### Configuration Defaults

Here is the default configuration in the configuration file `add-page-by-form.yaml` plus an explanation of the settings:

```yaml
enabled: true
date_display_format: 'd-m-Y g:ia'
default_title: 'My New Page'
default_content: 'No content.'
overwrite_mode: false
include_username: false
auto_taxonomy_types: false
use_editor_class: true
physical_template_name: true
```
- `enabled` determines whether the plugin is active or not;
- `date_display_format` sets a default date and time format;
- `default_title` will be used as a fallback for the new page title when no other value is set;

- `default_content` will be used as the page content for the new page when no other value is set;
- `include_username` sets whether or not the username of the currently logged in frontend user is included in the new page frontmatter;
- `overwrite_mode` determines how to act when a page with the same name or slug already exists;
- `auto_taxonomy_types` saves any new taxonomy types that were input by the user to the site configuration file `site.yaml`;
- `use_editor_class` determines whether or not to change a textarea field into a Markdown editor when the texture field includes the class "editor" (`classes: editor`);
- `physical_template_name` should normally not be used. For more information see the description in the section 'pageconfig' block variables.


### Customizing the default configuration

To keep your custom configuration when updating the plugin you need to use a configuration file which is stored in the `user/config/plugins` folder.

Simply edit the plugin options in the Admin panel and the changes will be saved to the configuration file in that location. If you don't use the Admin panel, copy the `add-page-by-form.yaml` default file to your `user/config/plugins` folder and use that copy to change configuration settings.   


## Usage

Using this plugin requires:

- a normal page containing a Grav Form with a unique name that starts with "add_page";
- optionally, but required for usefulness, one or two blocks of extra page frontmatter variables in that page being:
	-  a 'pageconfig' block with variables that are used in the new page creation process;
	-  a 'pagefrontmatter' block with variables that are passed on to the new page frontmatter and can be processed by Twig along the way. 

### Modifying frontmatter variables

Every frontmatter variable value can be changed by the user when an input field with the same name as the variable is included in the form.

The basic method of modifying is overriding or replacing an initial value. An extreme case of overriding a variable which is quite uncommon but illustrates the process well:

1. `overwrite_mode` is set in `add-page-by-form.yaml`
2. and can be changed in the Plugin configuration in the Admin Panel
3. can be set in the `pageconfig` block
4. and finally may be changed again by the end user when the form contains an `overwrite_mode` field.

### Page Headers / Frontmatter
This plugin makes extensive use of [Custom Page Headers](https://learn.getgrav.org/content/headers#custom-page-headers). Unfortunately the Grav documentation mixes the terms "frontmatter", "page headers" and simply "headers". This may be confusing at first. They [all](https://learn.getgrav.org/content/headers) refer to the optional top part of a Grav page which contains data in [YAML syntax](https://learn.getgrav.org/advanced/yaml).


## Form page Frontmatter

The frontmatter in the form page and the way it is handled by the plugin is where the flexibility of this plugin originates.

The form page frontmatter is divided into three sections or blocks:

1. So called 'root level' variables are intended to act upon the form page itself. They are not passed on to the new page;
2. the `pageconfig` block contains variables that are used by the plugin in the new page creation process and do get passed on to the new page frontmatter;
3. the `pagefrontmatter` block holds all other variables that must be passed on to the new page frontmatter.

### Root level variables

In the examples above the root level configuration options are:

- `title` sets the title of the page containing the form;
- `template: form` activates the form on this page (not required when the form page is named `form.md`);
- `form` defines the form.

From version 2, the use of `parent` in the, what is now called, root level block is deprecated. It is however still supported for backwards compatibility.

### 'pageconfig' block variables

In the optional pageconfig block you can set these, and only these, variables (other variables will be ignored):

- `parent` sets the parent page for the new page. This variable may be an absolute route (for example `parent: /user_contributions`) or a relative route (e.g. `parent: articles`. In case of an absolute route this route starts from the pages root. A relative route is regarded to start from the form page, so the new page will be a child page of the form page. The form page is also used as the parent page when the set parent page does not exist;
- `subroute` defines a route from the (initial) parent value. If one or more folders in the route do not exist they will be created; 
- `slug_field` tells the plugin what field to use as the new page's slug or folder name. When `slug_field` is missing the plugin tries to use the value of `title`;
- `overwrite_mode: true|false|edit` (default `false`) tells the plugin what to do when a page with the same name already exists. With `overwrite_mode: true` the existing page is overwritten. Any additional (media) files besides the page itself which are stored in the existing page folder are deleted as well. With `overwite_mode: false` the new page slug gets a sequential number attached at the end (for example "my-new-page-1" in case "my-new-page" exists).   
  Using `overwite_mode: edit` allows for the page being saved to it's existing folder respecting any already present uploaded files;
- `include_username: true|false` (default `false`) determines whether or not to include the username of a logged in frontend user in the new page frontmatter;
- `physical_template_name: true|false` (default `true`) does or does not cause the plugin to use the template name of the new page as that new page's filesystem filename. Defaults to "default" when no template is set in the 'pagefrontmatter' block. When set to `true` to avoid future confusion the frontmatter variable `template` is removed from the new page frontmatter.

#### A note on parent and subroute

Together the variables `parent` and `subroute` define the new page's destination. Or, in other words, together they set the path or route of the new page filesystem folder in the page structure.

The difference between parent and subroute worded in another way:

- Parent: works on a page level; when there is no page at the parent route, the form page is used as the parent;
- Subroute: works on a folder level; a subroute may consist of empty folders and if a folder in the subroute does not exist it gets created.

### 'pagefrontmatter' block variables

The content of the optional `pagefrontmatter` block will be included in the new page frontmatter.


## Form usage

The form page needs to use a [simple single form](https://learn.getgrav.org/forms/forms#create-a-simple-single-form).

Two examples are included at the end of this ReadMe file.

### Mandatory fields and values

#### Form Name
It is always a good thing to give each form a unique name, especially when multiple forms are used.

To pre fill form fields with default values the Form name must include the string "add_page". Valid names are for example `add_page.blogpost`, `add_page_profile`.

###Form Actions

**Custom Form processing**	 ( Important ! )

To let the plugin process the form after a Submit a custom process action must be set like so:

```
    process:
        -
            add_page: true
```

**Redirect to the new page**


To show the new page to the user set the `redirect` action to the custom value `@self` or `@self-admin`.

When using `redirect: '@self'` the page will be shown as a regular web page, for example:

```
    process:
        -
            add_page: true
        -
            redirect: '@self'
```

To open the new page in the Admin panel use `redirect: '@self-admin'`.  
Note that this plugin does not handle the admin user authentication. If the Admin plugin is not installed or is inactive redirection occurs as if `@self` was used.

> Tip: using `@self-admin` is a very convenient way to learn how to use this plugin as it is easy to view and examine the source of the resulting new page including it's frontmatter in the Admin panel.

### Using a Markdown editor in textarea fields
When a `textarea` field is given the class `editor` it will use the [SimpleMDE Markdown Editor](https://simplemde.com).


## Value overrides

The variables which are defined and given a value in the `pageconfig` and `pagefrontmatter` blocks may be 'overridden' or in other words replaced by form input fields. In that respect these variables can be seen to hold a set of default values.

There is only one exception to the default variable override behaviour and that is the handling of `taxonomy` types. Extra taxonomy types and values (for example tags) which are entered via form fields are added to the new page taxonomy.

To override a default value by user input is simply a matter of including a form field by the same name in the page form.   
For example in the example 2 - _create a new blog post_, the default title is set to "My new Blog post". The form contains a form field of type text with `name: title`. Thus the user is prompted to enter a title for the new page in the form but does not need to do so because filling in the title field is not mandatory. If the user enters a title that value is used as the title for the new page. If he or she does not, the default title "My new Blog post" will be used.


## Setting taxonomy categories and tags

The Add Blog Post example shows how to let the user add extra tags via the form.
Extra categories may be added in the same way.


## Handling extra taxonomy types

By default Grav 'knows' two taxonomy types, `category` and `tag`. Extra taxonomy types may be defined and added just like with any other variables you can include a form field. The new type is then added to the list of taxonomy types instead of replacing the existing types.

This can be done in the `pagefrontmatter` block. For example, to define a new taxonomy type named 'department':

```
pagefrontmatter:
    taxonomy:
        - department
```
And/or in the form:

```
form:
    name: my_form
    fields:
        -
            name: taxonomy
            label: Taxonomy type
            type: text
```

This is a feature which calls for a solid look-before-you-leap approach because of it's side effects. Using a new taxonomy type requires it to be included in the list of known taxonomy types. This list is in the site configuration file `site.yaml`.

By setting the plugin configuration option `auto_taxonomy_types: true` new types get automatically saved and can then be used in a collection.

The side effect and possibly downside is that every modification of the site configuration file causes Grav to rebuild the cache, so this may not be desirable with larger sites.   
Use with caution!

## Examples

The least error prone way to test and play with the examples is to set up a fresh Grav site and using it's default theme Antimatter.

### Form page example 1: create a normal page

The goal of this example is to show how to let a user create a new page where uploaded images and files are saved along the page (in the same folder). After the user clicked Submit he or she will be shown the new page.

Suppose this minimal Grav website structure in `user/pages/`:

```
03.submit-assignment/
    default.md
04.assignments/
    cmpt363-e100/
        default.md
        drafts/
            modular.md
        reviewed/
            modular.md
```

BTW both modular pages are not required but are mentioned as they could be used to display a collection of draft and reviewed assignments.

Then the 'Submit assignment for review' page (with slug `submit-assignment`) full content (both frontmatter and content) could look like:

```
---
routable: true
title: 'Submit assignment for review'
template: form
visible: true
pageconfig:
    parent: /submitted-assignments/cmpt363-e100/drafts
pagefrontmatter:
    visible: true
    status: draft
    template: default
    course:
        assignment: 'CMPT363 E100'
    instructor:
        name: 'Jane Doe'
form:
    name: addpage-assignment-cmpt363-e100
    fields:
        -
            name: name
            label: Name
            type: text
            validate:
                required: true
        -
            name: title
            label: Title
            type: text
            validate:
                required: true
        -
            name: content
            label: 'Assignment text'
            type: textarea
            size: long
            classes: editor
            validate:
                required: true
        -
            name: attachments
            label: 'Attachment (PDF only)'
            type: file
            multiple: true
            accept:
                - application/pdf
            validate:
                required: false
        -
            name: honeypot
            type: honeypot
    buttons:
        -
            type: submit
            value: Submit
    process:
        -
            addpage: null
        -
            redirect: '@self'
---

Please write your assignment and attach any images and/or files.
```

Supposing the user has not changed the pre filled title field, has entered his name "Paul Walker", has entered a simple "q.e.d." as the assignment content and uploaded one PDF document, then the full new page will be:

```
---
visible: true
status: draft
course:
  assignment: 'CMPT363 E100'
instructor:
  name: 'Jane Doe'
name: 'Paul Walker'
title: 'CMPT363 E100'
attachments:
  /Users/rwgc/devroot/repos/grav-test/htdocs/user/pages/assignments/cmpt363-e100/drafts/cmpt363-e100-1/scrum-guide-sept-2013.pdf
    name: scrum-guide-sept-2013.pdf
    type: application/pdf
    size: 273 KB
    path: /Users/rwgc/devroot/repos/grav-test/htdocs/user/pages/assignments/cmpt363-e100/drafts/cmpt363-e100-1/scrum-guide-sept-2013.pdf
---

q.e.d.
```

On the file system level the file structure will be: 

```
01.home/
  default.md
02.add-new-article/
  default.md
03.assignments/
  cmpt363-e100/
    default.md
    drafts/
      cmpt363-e100-1/
        default.md
        scrum-guide-sept-2013.pdf
      modular.md
    reviewed/
      modular.md
```


### Form page example 2: create a blog post

In this example the user can add a blog post. To ensure the new page will be treated as a blog post simply set the `template` variable to be used by the new page to `item`. BTW the active theme must include the corresponding template `item.html.twig`. This is why it is best to start with Grav's default theme Antimatter.

```
---
title: 'Add Blog Post'
template: form
pageconfig:
    parent: '/blog'
    include_username: true
    overwrite_mode: true
pagefrontmatter:
    template: item
    title: My new Blog post
    taxonomy:
        category: blog
        tag: [journal, guest]
form:
    name: add_page.blogpost
    fields:
        -
            name: author
            label: 'Author'
            type: text
        -
            name: title
            label: 'Post Title'
            type: text
        -
            name: taxonomy.tag
            label: 'Tags (comma separated)'
            type: text
        -
            name: content
            label: 'Post Content'
            type: textarea
            size: long
            classes: editor
        -
            name: images
            label: 'Images to upload'
            type: file
            multiple: true
            accept:
                - 'image/*'
        -
            name: honeypot
            type: honeypot
    buttons:
        -
            type: submit
            value: Submit
    process:
        -
            add_page: true
        -
            redirect: '/blog'
---

## New Blog Post

Write your blog post:
```
After the form has been submitted the user is taken to the blog main page where the new post should show up.


## Issues


### Grav Form issue

The form on the form page is a standard Grav form. Please note that the Grav Form Plugin currently (latest test using version 4.0.1) has an issue which prevents the form to be submitted when a form field of type `file` is set to `required: true`(see issue [#106](https://github.com/getgrav/grav-plugin-form/issues/106)).


## Credits

- Team Grav and everyone who contributes to Grav;
- Wes Cossick for [SimpleMDE Markdown Editor](https://simplemde.com);
- All [contributors](https://github.com/bleutzinn/grav-plugin-add-page-by-form/graphs/contributors) who've helped me out on things.
