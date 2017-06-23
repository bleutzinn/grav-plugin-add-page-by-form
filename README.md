# Add Page By Form Plugin

The **Add Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows users to add a new page by filling in a form.

This plugin uses the possibilities of [custom frontmatter](https://learn.getgrav.org/content/headers#custom-page-headers). By setting your own variables in the form page frontmatter a priori and optionally letting users override these variable values by filling in corresponding form fields you can transport these data into the new page frontmatter.

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
```
- `enabled: true|false` determines whether the plugin is active or not;
- `date_display_format` sets a default date and time format

The next settings are also available from the Adminstration Panel:

- `default_title` will be used as a fallback for the new page title when no other value is set;
- `default_content` will be used as the page content for the new page when no other value is set;
- `include_username` when set to `true` the logged in frontend user username is added to the new page frontmatter;
- `overwrite_mode` if `true` the new page will replace a page by the same name or slug if it exists. Both page content and media will be overwritten.

### Configuration Changes

Simply edit the plugin options in the Admin panel, or, if you don't use the Admin panel, copy the `add-page-by-form.yaml` default file to your `user/config/plugins` folder and use that copy to change configuration settings.   
Read below for more help on what these fields do and how they can help you modify the plugin.

## Usage

Using this plugin requires:

- a normal page containing a Grav Form with a unique name that starts with "addpage";
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
This plugin makes extensive use of [Custom Page Headers](https://learn.getgrav.org/content/headers#custom-page-headers). The Grav documentation mixes the terms "frontmatter", "page headers" and simply "headers". This may be confusing at first. They [all](https://learn.getgrav.org/content/headers) refer to the optional top part of a Grav page which contains data in [YAML syntax](https://learn.getgrav.org/advanced/yaml).

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
    template: assignment
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
            destination: '@self'
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
    -
        name: scrum-guide-sept-2013.pdf
        type: application/pdf
        size: 273 KB
        path: /user/pages/assignments/cmpt363-e100/drafts/cmpt363-e100-1/scrum-guide-sept-2013.pdf
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

In this example the user can add a blog post. To ensure the new page will be treated as a blog post simply set the `template` variable to `item` (assuming the theme in use includes that template):

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
    name: addpage.blogpost
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
            name: content
            label: 'Post Content'
            type: textarea
            size: long
            classes: editor
            size: long
        -
            name: images
            label: 'Images to upload'
            type: file
            multiple: true
            accept:
                - 'image/*'
            destination: '@self'
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
            redirect: '/blog'
---

## New Blog Post

Write your blog post:
```
After the form has been submitted the user is taken to the blog main page where the new post should show up.

## Form page Frontmatter

The frontmatter isn the form page and the way it is handled by the plugin is where the flexibility orgininates.

The form page frontmatter is diveded into three sections or blocks:

1. So called 'root level' variables are intended to act upon the form page itself. They are not passed on to the new page;
2. the `pageconfig` block contains variables that are used by the plugin in the new page creation process and are also passed on to the new page frontmatter;
3. the `pagefrontmatter` block holds all variables that are to be passed on to the new page frontmatter.

### Root level frontmatter
In the examples above the root level configuration options are:

- `title` sets the title of the page containg the form;
- `template: form` activates the form on this page (not required when the form page is named `form.md`);
- `form` defines the form.

From version 2, the use of `parent` in the, what is now called, root level block is deprecated. It is however still supported for backwards compatibility.

### 'pageconfig' block frontmatter
In the optional pageconfig block you can set these, and only these, variables (other variables will be ignored):

- `parent` sets the parent page for the new page. This variable may be an absolute route (for example `parent: /user_contributions`) or a relative route (e.g. `parent: articles`. In case of an absolute route this route starts from the pages root. A relative route is regarded to start from the form page, so the new page will be a child page of the form page. The form page is also used as the parent page when the set parent page does not exist;
- `subroute` defines a route from the (initial) parent value. If one or more folders in the route do not exist they will be created;
- `slug_field` tells the plugin what field to use as the new page's slug or folder name. When `slug_field` is missing the plugin tries to use the value of `title`;
- `overwrite_mode: true|false` (default false) tells the plugin what to do when a page with the same name already exists. With `overwrite_mode: true` the existing page is overwritten. Any additional files besides the page itself which are stored in the existing page folder are deleted as well. With `overwite_mode: false` the new page slug gets a sequential number attached at the end (for example "my-new-page-1" in case "my-new-page" exists);
- `username: true|false` (default false) determines whether or not to include the username of a logged in frontend user in the new page frontmatter.

#### parent and subroute
Together the variables `parent` and `subroute` define the new page's destination. Or, in other words, together they set the path or route of the new page filesystem folder in the page structure.

The difference between parent and subroute worded in another way:

- Parent: works on a page level; when there is no page at the parent route, the form page is used as the parent;
- Subroute: works on a folder level; a subroute may consist of empty folders and if a folder in the subroute does not exist it gets created. 

#### username
The currently logged in frontend user's username can be included in the new page frontmatter by setting `username: true`.

### 'pagefrontmatter' block frontmatter
The content of the optional `pagefrontmatter` block will be included in the new page frontmatter.

## Form input overrides

The above variables which are defined and given a value in the `pageconfig` and `pagefrontmatter` blocks may be 'overridden' by form input fields. In that respect these variables can be seen to hold a set of default values.

To overridde a default value by user input is simply a matter of including a form field by the same name in the page form.   
For example in the example 2 - _create a new blog post_, the default title is set to "My new Blog post". The form contains a form field of type text with `name: title`. Thus the user is prompted to enter a title for the new page in the form but does not need to do so because filling in the title field is not mandatory. If the user enters a title that value is used as the title for the new page. If he or she does not, the default title "My new Blog post" will be used.

The passing on of both the default settings and the form field values to the new page frontmatter makes for an extremely configurable solution. By mixing default settings and configuring the page form you can to a large extent control the appearence and behaviour of the newly added page by using the frontmatter variables present in the new page in a Twig template.

## Form usage

The form needs to be a [simple single form](https://learn.getgrav.org/forms/forms#create-a-simple-single-form).

### Mandatory fields and values

#### Form Name
It is always a good thing to give each form a unique name, especially when multiple forms are used.

To pre fill form fields with default values the Form name must begin with "addpage":

```
form:
    name: addpage.blogpost
```

#### Form Actions

**Custom Form processing**	 ( Important ! )

To let the plugin process the form after a Submit the custom process action must be set to:

```
    process:
        -
            addpage: null
```

**Redirect to the new page**

To show the new page to the user set the `redirect` action to the custom value `@self`:

```
    process:
        -
            addpage: null
        -
            redirect: '@self'
``` 

### Using a Markdown editor in textarea fields
When a `textarea` field is given the class `editor` it will use the [SimpleMDE Markdown Editor](https://github.com/NextStepWebs/simplemde-markdown-editor).

## Grav Form issue

The form on the form page is a standard Grav form. Please note that the Grav Form Plugin currently (version 2.7.0) has an issue which prevents the form to be submitted when a form field ot type `file` is set to `required: true`(see issue [#106](https://github.com/getgrav/grav-plugin-form/issues/106)).

## Credits

- Team Grav and everyone who contributes to Grav;
- Wes Cossick for [SimpleMDE Markdown Editor](https://github.com/NextStepWebs/simplemde-markdown-editor).
