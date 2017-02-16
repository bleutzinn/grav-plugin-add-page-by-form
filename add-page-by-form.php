<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;


/**
 * Class AddPageByFormPlugin
 * @package Grav\Plugin
 */
class AddPageByFormPlugin extends Plugin
{

    private $uploads = array();
    private $moveSelfFiles = false;

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
            'onFormProcessed' => ['onFormProcessed', 0],
            'onFormValidationProcessed' => ['onFormValidationProcessed', 0]
            // ALS onFormValidationProcessed WORDT AANGEROEPEN FAALT DE FILE MOVE VAN DE FORM PLUGIN
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
            'onPageInitialized' => ['onPageInitialized', 0],
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

    public function onFormValidationProcessed(Event $event)
    {
        $grav = Grav::instance();
        $config = $grav['config'];
        $form = $event['form'];
        $uri = $grav['uri']->url;
        $session = $grav['session'];

        // if files have been uploaded
        // and
        // destination is 'self'
        // then prepare for moving the files to the new page
        // else do nothing

        //$this->uploads[] = [];
        $this->moveSelfFiles = false;
        $destination = $config->get('plugins.form.files.destination', '@self');
        // Get queue from session
        $queue = $session->getFlashObject('files-upload');
        //dump('queue is '.serialize($queue));
        $thisQueue = $queue[base64_encode($uri)];
        $this->grav['log']->notice('This queue: '.serialize($thisQueue));
        if ($thisQueue) {
            //dump('This queue is filled');

            $formdata = $form->toArray();
            //dump($formdata);
            $process = isset($formdata['process']) ? $formdata['process'] : [];

            $formDef = $grav['page']->header()->form;
            //dump($formDef);
            //dump($formDef['fields']);
            $fields = $formDef['fields'];
            foreach ($fields as $array) {
                //dump($array);
                //dump(array_key_exists('destination', $array) );
                if (array_key_exists('destination', $array)) {
                    $destination = $array['destination'];
                    //dump($destination);
                    break;
                }
            }

            if (is_array($process)) {
                foreach ($process as $action => $data) {
                    if (isset($action)) {
                        $action = \key($data);
                        if ($action === 'addpage') {
                            $this->moveSelfFiles = true;
                            break;
                        }
                    }
                }
            }
        }

        if (($destination === '@self' || $destination === 'self@') && $this->moveSelfFiles) {
            $this->grav['log']->notice('Will move uploaded files once form gets submitted');
            // Save uploaded files properties to process in onFormProcessed() 
            $this->uploads[] = $thisQueue;
            //dump($thisQueue);
        }
        else {
            // Restore queue in session again
            $session->setFlashObject('files-upload', $queue);
            $this->moveSelfFiles = false;
            $this->grav['log']->notice('Will not move uploaded files; handing over back to Form plugin');
        }
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

                    $page = $this->grav['page'];

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
                    $folder = $page->folder();
                    $header = $page->header();

                    $yaml_str = '';
                    if ( isset($header->pagefrontmatter) && is_array($header->pagefrontmatter) ) {
                        $pagefrontmatter = $header->pagefrontmatter;
                        if (isset($formdata)) {
                            $pagefrontmatter = array_merge($pagefrontmatter, $formdata);
                        }
                        // Remove content from array
                        unset($pagefrontmatter['content']);
                        unset($pagefrontmatter['_json']);
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
                        $page->header((object)$header);
                        $page->frontmatter(Yaml::dump((array)$page->header()));
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

                    // Move uploaded files to the new page folder
                    if ($this->moveSelfFiles) {
                        $uploads = $this->uploads;
                        //dump($uploads);
                        foreach ($uploads as $key => $upload) {
                            foreach ($upload as $key => $files) {
                                foreach ($files as $destination => $file) {
                                    foreach ($file as $properties => $value) {
                                        $this->grav['log']->notice('properties: ' . $properties . '; value: ' . $value);
                                    }
                                    $destination = $file['path']; 
                                    $tmp_name = $file['tmp_name'];
                                    if (strpos($destination, 'user/pages/' . $folder) !== false) {
                                        $filename = substr($destination, strrpos($destination, DS) + 1);
                                        $originFilePath = ROOT_DIR . $destination;
                                        $destinationFilePath = $parent_page->path() . DS . $slug . DS . $filename;
                                        rename($tmp_name, $destinationFilePath);
                                    }
                                }
                            }
                        }
                    }
                }
        }
    }
}


