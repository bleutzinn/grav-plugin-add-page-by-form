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

    
        $this->grav['debugger']->addMessage('Form submit action is: '.$action);
        

        switch ($action) {
            case 'createpage':
                //do what you want
                if(isset($_POST)) {  
                    $newPageRoute = $this->config->get('plugins.newpagebyform.route');
                    $this->grav['debugger']->addMessage('New page route is: '.$newPageRoute);
                    $newPageTemplate = $this->config->get('plugins.newpagebyform.template');
                    $this->grav['debugger']->addMessage('Template to be used is: '.$newPageTemplate);

                    // store all form fields in an array
                    $formdata = $form->value()->toArray();
                    $this->grav['debugger']->addMessage('Submitted Page Title: '.$formdata['title']);
                    $this->grav['debugger']->addMessage('Submitted Page Content: '.$formdata['content']);

                    // Create s slug to be used as the page filename
                    // Credits: Alex Garrett
                    $slug = $formdata['title'];
                    $lettersNumbersSpacesHyphens = '/[^\-\s\pN\pL]+/u';
                    $spacesDuplicateHypens = '/[\-\s]+/';
                    $slug = preg_replace($lettersNumbersSpacesHyphens, '', $slug);
                    $slug = preg_replace($spacesDuplicateHypens, '-', $slug);
                    $slug = trim($slug, '-');
                    $slug = mb_strtolower($slug, 'UTF-8');
                    $this->grav['debugger']->addMessage('Slug is: "'.$slug.'"');

                    $newPageDir = PAGES_DIR . $newPageRoute . '/' . $slug;

                    if (!file_exists($newPageDir)) {
                        $this->grav['debugger']->addMessage('Yep create dir! '.$newPageDir);
                        mkdir($newPageDir, 0777, true);
                        $pageFile = fopen($newPageDir . '/default.md', "w") or die("Unable to open file!");
                        $txt = "---\n";
                        fwrite($pageFile, $txt);
                        $txt = "title: " . $formdata['title'] . "\n";
                        fwrite($pageFile, $txt);
                        $txt = "template: " . $newPageTemplate . "\n";
                        fwrite($pageFile, $txt);
                        $txt = "visible: false\n";
                        fwrite($pageFile, $txt);
                        $txt = "---\n";
                        fwrite($pageFile, $txt);
                        $txt = $formdata['content'] . "\n";
                        fwrite($pageFile, $txt);
                        fclose($pageFile);
                    }
                    else {
                        $this->grav['debugger']->addMessage('Nope, dir "' . $newPageDir . '" already exists! ');
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
