# New Page By Form Plugin

The **New Page By Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It allows (anonymous) users to create a new page by filling in a form.

**IMPORTANT: this version is far from ready. Do not use or fork unless you really need to**

## Security

This plugin is being developed for a specific use case where an anonymous user is allowed to create a new page. The page is not visible by default but gets moderated by the Admin user. Upon approval the Admin adds the new page to the website by making the page visible.

The above use case does not require any security and so, this plugin does not provide any security.
Please take this in consideration before using this plugin, especially on the web.

## Installation

Installing the New Page By Form plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install newpagebyform

This will install the Page Creator plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/newpagebyform`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `newpagebyform`. You can find these files on [GitHub](https://github.com/bleutzinn/grav-plugin-newpagebyform) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/newpagebyform
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/newpagebyform/newpagebyform.yaml` to `user/config/plugins/newpagebyform.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

## Usage

Create a page with a form. Make sure the 'form.md' file look like:
```
---
title: Create New Page
form:
    name: new-page-form
    fields:
        - name: template
          type: hidden
          default: page.md

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
        - save:
            fileprefix: feedback-
            dateformat: Ymd-His-u
            extension: txt
            body: "{% include 'forms/data.txt.twig' %}"
        - display: thankyou
---
You can create a new page by filling in the form below.

Please enter the Page Title and write some content to appear on the new page.
```

## Credits

To be amended

## To Do

- Add code to create the page

