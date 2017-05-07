# Add Page By Form Plugin

The **Add Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows users to add a new page by filling in a form.

A new page can also be a new blog post by setting the appropriate template.   
`textarea` fields can be enabled to show a markdown editor so users can add markup to text.

## A note about Security

Allowing anonymous visitors to create pages is a potential website security risk. It is advised to use the [Grav Login Plugin](https://github.com/getgrav/grav-plugin-login) so only logged in users can create pages. This plugin does not provide any security measures. Please take this in consideration before using this plugin.

## Installation

Typically the plugin should be installed via [GPM](http://learn.getgrav.org/advanced/grav-gpm) (Grav Package Manager):

```
$ bin/gpm install add-page-by-form
```

Alternatively it can be installed via the [Admin Plugin](http://learn.getgrav.org/admin-panel/plugins).

A third option is to manualy install the plugin by [downloading](https://github.com/bleutzinn/grav-plugin-add-page-by-form/archive/master.zip) the plugin as a zip file. Copy the zip file to your `/user/plugins` directory, unzip it there and rename the folder to `add-page-by-form`.

## Configuration Defaults

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
dateformat: 'd-m-Y g:ia'
```
- `enabled: true|false` determines whether the plugin is active or not;
- `dateformat` sets a default date and time format.

## Configuration Changes

Before using this plugin, you should copy the provided file `add-page-by-form.yaml` to `user/config/plugins/add-page-by-form.yaml` and use that copy to change configuration settings.

## Usage

Using this plugin requires:

- a normal page containing a Grav Form;
- two blocks of extra page frontmatter variables in that page:
	-  a 'pageconfig' block with variables that are used in the new page creation process;
	-  a 'pagefrontmatter' block with variables that are simply passed on to the new page frontmatter.

Every frontmatter variable value can be changed by the user when an input field with the same name as the variable is included in the form.

### Page Headers / Frontmatter
This plugin makes extensive use of [Custom Page Headers](https://learn.getgrav.org/content/headers#custom-page-headers).   
BTW The Grav documentation uses "frontmatter" as well as "page headers" or simply "headers". This may be confusing at first. They [both](https://learn.getgrav.org/content/headers) refer to the optional top part of a Grav page which contains data in [YAML syntax](https://learn.getgrav.org/advanced/yaml).   
For pig-headedness reasons, this ReadMe uses the term frontmatter.

### Form page example 1: create a normal page

The goal of this example is to show how to let a user create a new page where uploaded images are saved along the page (in the same folder) and any uploaded PDF files are stored in a central repository. After the user clicked Submit he or she will be shown the new page.

Suppose this minimal Grav website structure in `user/pages/`:

```
01.home/
	default.md
02.add-new-page/
	default.md
03.assignments/
	draft/
		modular.md
	reviewed/
		modular.md
file-repository/
image-repository/
```

Then the 'Submit article for review' page full content (both frontmatter and content) should look like:

```
---
title: 'Submit article for review'
template: form
pageconfig:
    parent: 'assignments/draft'
pagefrontmatter:
    visible: true
    course:
        title: 'CMPT363 E100'
        assignment: 'Reading Quiz #1'
    instructor:
        name: 'John Doe'
form:
    name: add-page-form
    fields:
        -
            name: title
            label: 'Title'
            type: text
            validate:
                required: true
        -
            name: content
            label: 'Content'
            type: textarea
            size: long
            class: editor
            validate:
                required: true
        -
            name: main_image
            label: Main image
            type: file
            multiple: false
            destination: '@self'
            accept:
                - 'image/*'
        -
            name: additional_images
            label: Additional images
            type: file
            multiple: true
            destination: '@page:/image-repository'
            accept:
                - 'image/*'
        -
            name: attachments
            label: Attachments (PDF only)
            type: file
            multiple: true
            destination: '@page:/file-repository'
            accept:
                - application/pdf
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
            display: @self
---

Please write your assignment and attach any images and/or files.
```

### Form page example 2: create a blog post

In this example the user can add a blog post. To do so simply change the `template` variable to `item`:

```
---
title: 'Add Blog Post'
template: form
parent: '/blog'
pagefrontmatter:
    template: item
    title: My new Blog post
    taxonomy:
        category: blog
        tag: [journal, guest]
form:
    name: add-blog-post-form
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
            class: editor
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
            display: thank-you
---

You can add a new blog post by filling in the form below.
```

## Form page Frontmatter

The frontmatter isn the form page and the way it is handled by the plugin is where the flexibility orgininates.

The form page frontmatter is diveded into three sections or blocks:

1. So called 'root level' variables are intended to act upon the form page itself. They are not passed on to the new page;
2. the `pageconfig` block contains variables that are used by the plugin in the new page creation process and are also passed on to the new page frontmatter;
3. the `pagefrontmatter` block holds all variables that are to be passed on to the new page frontmatter.

### Root level frontmatter
In the examples above the root level configuration options are:

- `title` sets the title of the page containg the form.
- `template: form` activates the form on this page (not required when the form page is named `form.md`).
- `form` defines the form;

### pageconfig block frontmatter

- `parent` sets the parent page for the new page. This optional variable may be an absolute route (for example `parent: /user_contributions`) or a relative route (e.g. `parent: articles`. In case of an absolute route this route starts from the pages root. Relative routes are regarded to start from the form page, so new pages become child pages of the form page. If the parent page does not exist an error is logged in the Grav log file and the form page will be used as parent instead;
- `subroute` is an optional variable which defines a route from the (initial) parent value. The `subroute` value is a route. If one or more folders in the route do not exist they will be created;
- `username:  true|false` determines whether or not to include the username of a logged in user in the new page frontmatter.

Together the variables `parent` and `subroute` define the new page destination. Or, in other words, together they set the path or route of the new page folder in the page structure.

By passing on the username to the new page (by setting `username: true`) it is, for example, possible for users to edit their own pages later on. One way of allowing that is to use the [Editable Plugin](https://github.com/bleutzinn/grav-plugin-editable) with the [SimpleMDE editor add-on](https://github.com/bleutzinn/editable-simplemde-add-on).

The above variables which are defined and given a value in the `pageconfig` block may be 'overridden' by form input fields. The most foolproof way of giving users the ability to change these settings is to use [Select Fields](https://learn.getgrav.org/forms/forms/fields-available#the-select-field).

### pagefrontmatter block frontmatter
The content of the `pagefrontmatter` block will be included in the new page frontmatter and can be seen as a set of defaults. These default settings can be overridden by user input if you add a form field by the same name. For example in the `Add New Page` example, the default title is set to `My New Page`. The user is prompted to enter a title for the new page in the form but does not need to do so because filling in the title field is not mandatory.

The passing on of both the default settings and the form field values to the new page frontmatter makes for an extremely configurable solution. By configuring the page form settings you can to a large extent control the appearence and behaviour of the newly added page by using the frontmatter variables in a Twig template.

## Form page Form

### Grav Form issues
The form on the form page is a standard Grav form. Please note that the Grav Form Plugin currently has a few issues to take in consideration when using it:

- Form fields ot type file which have `required: true` will prevent the form to be submitted ([issue #116](https://github.com/getgrav/grav-plugin-form/issues/116));
- Pre filling form fields is not supported ([issue #123](https://github.com/getgrav/grav-plugin-form/issues/123)).

### Using an editor for textarea fields
When a `textarea` field is given the class `editor` it will use the [SimpleMDE Markdown Editor](https://github.com/NextStepWebs/simplemde-markdown-editor) and text entered in the textarea will be markdown.

## Credits

- Team Grav and everyone who contributes to Grav;
- Wes Cossick for [SimpleMDE Markdown Editor](https://github.com/NextStepWebs/simplemde-markdown-editor).
