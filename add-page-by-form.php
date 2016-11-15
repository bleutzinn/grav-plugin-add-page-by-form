<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Uri;
use RocketTheme\Toolbox\Event\Event;

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


    public function onFormProcessed(Event $event)
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];
    
        switch ($action) {
            case 'addpage':
                //do what you want
                if(isset($_POST)) {
                    // Get plugin config settings
                    $newPageRoute = $this->config->get('plugins.add-page-by-form.route');
                    $newPageTemplate = $this->config->get('plugins.add-page-by-form.template');
                    $dateFormat = $this->config->get('plugins.add-page-by-form.dateformat');

                    // Get the entire params block from the form page frontmatter
                    $page = $this->grav['page'];
                    $header = $page->header();
                    $yaml_str = '';
                    if ( isset($header->params) && is_array($header->params) ) {
                        $yaml_str = yaml_emit($header->params);
                        // Remove YAML wrapper
                        $yaml_str = substr( $yaml_str, strpos($yaml_str, "\n")+1 );
                        $yaml_str = str_replace( "\r\n", "\n", $yaml_str );
                        $yaml_str = substr( $yaml_str, 0, strrpos(rtrim($yaml_str), "\n")+1 );
                        $this->grav['debugger']->addMessage('The frontmatter \'params\' block is: ' . $yaml_str );
                    }
                    
                    // Get all form fields
                    $formdata = $form->value()->toArray();
                    $author = $formdata['author'];
                    $title = $formdata['title'];
                    $content = $formdata['content'];

                    // Create s slug to be used as the page filename
                    // Credits: Alex Garrett
                    $slug = $title;
                    $lettersNumbersSpacesHyphens = '/[^\-\s\pN\pL]+/u';
                    $spacesDuplicateHypens = '/[\-\s]+/';
                    $slug = preg_replace($lettersNumbersSpacesHyphens, '', $slug);
                    $slug = preg_replace($spacesDuplicateHypens, '-', $slug);
                    $slug = trim($slug, '-');
                    $slug = mb_strtolower($slug, 'UTF-8');

                    // Assume this is the first submission of the page, so set $version to 1
                    $version = 1;
                    $newPageDir = PAGES_DIR . $newPageRoute . '/' . $slug . '_' . $version;

                    // Keep incrementing the page slug suffix to keep previous versions
                    while (file_exists($newPageDir)) {
                        $version += 1;
                        $newPageDir = PAGES_DIR . $newPageRoute . '/' . $slug . '_' . $version;
                    }

                    // Add the page
                    try {
                        // Create the directory
                        $pageDir = mkdir($newPageDir, 0775, true);
                        if (!$pageDir) {
                            throw new \Exception('Unable to add page; can not create directory "' . $newPageDir . '"');
                        }
                        // Create the page file
                        $pageFile = fopen($newPageDir . '/default.md', "w");
                        // test case $pageFile = false;
                        if (!$pageFile) {
                            throw new \Exception('Unable to add page; can not create file "default.md"');
                        }
                        // Include the page frontmatter
                        $txt = "---\n";
                        fwrite($pageFile, $txt);
                        $txt = "title: " . $title . "\n";
                        fwrite($pageFile, $txt);
                        $txt = "author: " . $author . "\n";
                        fwrite($pageFile, $txt);
                        $txt = "template: " . $newPageTemplate . "\n";
                        fwrite($pageFile, $txt);
                        $txt = "date: " . date($dateFormat) . "\n";
                        fwrite($pageFile, $txt);
                        if ($yaml_str != '') {
                            $txt = $yaml_str;
                            fwrite($pageFile, $txt);
                        }
                        $txt = "---\n";
                        fwrite($pageFile, $txt);

                        // Include the page content
                        $txt = $content . "\n";
                        fwrite($pageFile, $txt);

                        // Close and save the file
                        fclose($pageFile);
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

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

    }

}
