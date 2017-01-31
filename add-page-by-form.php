<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use RocketTheme\Toolbox\Event\Event;
//use Grav\Common\Filesystem\Folder;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AddPageByFormPlugin
 * @package Grav\Plugin
 */
class AddPageByFormPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onFormProcessed' => ['onFormProcessed', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the events we are interested in
        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0]
        ]);
    }

    public function onPageInitialized()
    {
        $assets = $this->grav['assets'];

        // Add jQuery library
        $assets->add('jquery', 101);

        // Add SimpleMDE Markdown Editor
        $assets->addCss('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', 1);
        $assets->addJs('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', 1);

        // Load inline Javascript code from configuration file
        $assets->addInlineJs(file_get_contents('plugin://add-page-by-form/assets/js/simplemde_config.js'), 1);
    }

    public function onFormProcessed(Event $event)
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];
    
        switch ($action) {
            case 'addpage':
                if(isset($_POST)) {
                    // Get plugin config settings
                    $dateFormat = $this->config->get('plugins.add-page-by-form.dateformat');

                    // Get all form fields
                    $formdata = $form->value()->toArray();
                    // Extract the content; if not present as a form value then fallback to frontmatter
                    $content = 'No content set';
                    if (isset($formdata['content']) ) {
                        $content = $formdata['content'];
                    }
                    else {
                        if (isset($header->pagefrontmatter->content) ) {
                            $content = $header->pagefrontmatter->content;
                        }
                    }
                    // Assemble the new page frontmatter from the pagefrontmatter block as set in
                    // the form page and the form field values. Field values override values set
                    // in the pagefrontmatter block
                    $page = $this->grav['page'];
                    $header = $page->header();
                    $yaml_str = '';
                    if ( isset($header->pagefrontmatter) && is_array($header->pagefrontmatter) ) {
                        $pagefrontmatter = $header->pagefrontmatter;
                        $formdata = $form->value()->toArray();
                        if (isset($formdata)) {
                            $pagefrontmatter = array_merge($pagefrontmatter, $form->value()->toArray());
                        }
                        // Remove content from array
                        unset($pagefrontmatter['content']);
                    }

                    // Create s slug to be used as the page filename
                    // Credits: Alex Garrett
                    $slug = $pagefrontmatter['title'];
                    $lettersNumbersSpacesHyphens = '/[^\-\s\pN\pL]+/u';
                    $spacesDuplicateHypens = '/[\-\s]+/';
                    $slug = preg_replace($lettersNumbersSpacesHyphens, '', $slug);
                    $slug = preg_replace($spacesDuplicateHypens, '-', $slug);
                    $slug = trim($slug, '-');
                    $slug = mb_strtolower($slug, 'UTF-8');

                    if ( isset($header->parent) ) {
                        $parent_page = $this->grav['page']->find($header->parent);
                        // Check whether the parent page exists
                        if (!$parent_page) {
                            throw new \Exception('Unable to add page; the parent "'.$header->parent.'" does not exist');
                        }
                    }
                    else {
                        throw new \Exception('Missing "parent" variable in form page header');
                    }

                    $newPageDir = $parent_page->path() . '/' . $slug;
                    // Assume this is the first submission of the page, so set $version to 1
                    $version = 0;
                    // Keep incrementing the page slug suffix to keep earlier versions / duplicates
                    while (file_exists($newPageDir)) {
                        $version += 1;
                        $newPageDir = $parent_page->path() . '/' . $slug . '-' . $version;

                    }
                    if ($version > 0) {
                        $slug = $slug . '-' . $version;
                    }

                    // Add the page
                    try {
                        /** @var Pages $pages */
                        $pages = $this->grav['pages'];
                        // Create page.
                        $page = new Page;
                        $language = trim(basename($parent_page->extension(), 'md'), '.') ?: null;
                        if ($language != '') {
                          $page->name('default.' . $language . '.md');
                        }
                        else {
                            $page->name('default.md');
                        }
                        $path = $parent_page->path() . DS . $slug. DS . $page->name();
                        $page->filePath($path);
                        $header = $pagefrontmatter;
                        $page->header((object) $header);
                        $page->frontmatter(Yaml::dump((array) $page->header(), 10, 2, false));
                        $page->rawMarkdown((string) $content);
                        $page->content((string) $content);
                        $page->file()->markdown($page->rawMarkdown());
                        $page->save();
                    }
                    catch (\Exception $e) {
                        $this->grav['debugger']->addMessage($e->getMessage());
                        $this->grav->fireEvent('onFormValidationError', new Event([
                            'form'    => $form,
                            'message' => '<strong>ERROR:</strong> ' . $e->getMessage() ]));
                        $event->stopPropagation();
                        return;
                    }
                }
        }
    }
}


