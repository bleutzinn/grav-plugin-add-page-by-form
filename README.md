# Add Page By Form Plugin

The **Add Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows users to add a new page by filling in a form. A new page can also be a new blog post by setting the appropriate template.

## Security

This plugin does not provide any security measures. Please take this in consideration before using this plugin.

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
```
- `enabled: true|false` determines whether the plugin is active or not

## Configuration Modifications

Before using this plugin, you should copy the `user/plugins/add-page-by-form/add-page-by-form.yaml` to `user/config/plugins/add-page-by-form.yaml` and use that file to change configuration settings.

## Usage

The first step is to create a page with the form possibly using this example as a starting point:
```
---
title: 'Add New Page'
template: form
parent: '/'
pagefrontmatter:
    title: 'My New Page'
    template: page
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
            label: 'Page Title'
            placeholder: 'Enter your page title here'
            autocomplete: true
            type: text
            validate:
                required: false
        -
            name: content
            label: 'Page Content'
            size: long
            placeholder: 'Write the content here'
            type: textarea
            validate:
                required: true
    buttons:
        -
            type: submit
            value: Submit
            classes: null
    process:
        -
            addpage: null
        -
            display: thank-you
---

You can add a new page by filling in the form below.

Please enter a title (optional) and write some content to appear on the new page.
```

To allow a user to add a blog post simply change the `template` variable to `item` and set some other blog related variables in the pagefrontmatter block:

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
            placeholder: 'Enter your name here'
            autocomplete: true
            type: text
            validate:
                required: true
        -
            name: title
            label: 'Blog Post Title'
            placeholder: 'Enter your page title here'
            autocomplete: true
            type: text
            validate:
                required: true
        -
            name: content
            label: 'Page Content'
            size: long
            placeholder: 'Write the content here'
            type: textarea
            validate:
                required: true
    buttons:
        -
            type: submit
            value: Submit
            classes: null
    process:
        -
            addpage: null
        -
            display: thank-you
---

You can add a new blog post by filling in the form below.

Please enter your name, a title and write something nice.
```
In the examples above the root level configuration options are:
- `title` sets the title of the page containg the form
- `template: form` activates the form on this page (not required when the form page is named `form.md`)
- `parent` sets the parent page for the new page. `parent` must be the path from the pages root, for example `/user_contributions`. The parent page must exist.
- `template` specifies the Twig template to be used by the new page. Use `page` for a regular page and `item` for a blog post item or use your own custom template.
- `pagefrontmatter` is a block of frontmatter that gets inserted in the new page header.
- `form` specifies the form.

The content of the `pagefrontmatter` block must be seen as default settings for the new page. These default settings can be overridden by user input if you add a form field by the same name. For example in the `Add New Page` example, the default title is set to `My New Page`. The user is prompted to enter a title for the new page in the form but does not need to do so because filling in the title field is not mandatory (`required` is false for that field).

The passing on of both the default settings and the form field values to the new page frontmatter makes for an extremely configurable solution. By configuring the page form settings you can to a large extent control the appearence and behaviour of the newly added page. For instance the standard `blog_item.html.twig` template will to display the new blog post`s author name you can use `{{ page.header.author }}` in a Twig template e.g. .

Finally, create a "Thank You"-page (slug: `thank-you`) as a child page of the form page.


## Credits

[Slug generator by Alex Garret](http://codereview.stackexchange.com/questions/44335/slug-url-generator) and of course to everyone who contibutes to Grav.
