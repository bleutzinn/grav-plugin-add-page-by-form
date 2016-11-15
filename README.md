# Add Page By Form Plugin

The **Add Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows (anonymous) users to add a page by filling in a form.

## Security

This plugin is being developed for a specific use case where an anonymous user is allowed to add a page to a Grav website. The page is not visible in the menu by default but gets moderated by the Admin user. Upon approval the Admin sets 'published: true' so the page will be included in the menu.

The above use case does not require any security and so, this plugin does not provide any security measures.
Please take this in consideration before using this plugin.

## Installation

Installing the Add Page By Form plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred) (not implemented yet)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install add-page-by-form

This will install the Page Creator plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/add-page-by-form`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `add-page-by-form`. You can find these files on [GitHub](https://github.com/bleutzinn/grav-plugin-add-page-by-form) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/add-page-by-form
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/add-page-by-form/add-page-by-form.yaml` to `user/config/plugins/add-page-by-form.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
route: '01.home/blog'
template: 'page'
```
route sets the file location for the new page.
template specifies the Twig template to be used by the new page. This line will be added to the new page header (YAML Frontmatter).

## Usage

Create a page with a form. Make sure the 'form.md' file looks like:
```
---
title: Add Page
form:
    name: add-page-form
    fields:
         - name: author
          label: 'Author'
          placeholder: Identify yourself
          autocomplete: true
          type: text
          validate:
              required: true
       - name: title
          label: Page Title
          placeholder: 
          autocomplete: on
          type: text
          validate:
            required: true

        - name: content
          label: Page Content
          size: long
          placeholder: 
          type: textarea
          validate:
            required: true

    buttons:
        - type: submit
          value: Submit
          classes:

    process:
        - addpage:
        - display: thankyou
---
You can add a page by filling in the form below.

Please enter the Page Title and write your content to appear on the page.
```
The form fields 'author', title' and 'content' are mandatory as the plugin will use the entered values to add the page to Grav.
Optionally there can be a section named 'params' like:
```
params:
    published: false
    instructor:
        name: 'John Doe'
        title: 'dr.'
```
When this section is present it's content will be inserted in the new page's frontmatter.

Finally, create the required Twig template file in the 'templates' folder of your theme. The template file name must match the name as set in the configuration file. So when in your configuration you have: 'template: page' then the template file name must be: 'page.html.twig'.

## Credits

[Slug generator by Alex Garret](http://codereview.stackexchange.com/questions/44335/slug-url-generator) and of course to everyone who contibutes to Grav.

## To Do

- Improve feedback when an error occurs during page creation.

