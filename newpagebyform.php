<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class NewPageByFormPlugin
 * @package Grav\Plugin
 */
class NewPageByFormPlugin extends Plugin
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

        // Enable the main event we are interested in
        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0]
        ]);
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onPageContentRaw(Event $e)
    {
        // Get a variable from the plugin configuration
        $text = $this->grav['config']->get('plugins.newpagebyform.text_var');

        // Get the current raw content
        $content = $e['page']->getRawContent();

        // Prepend the output with the custom text and set back on the page
        $e['page']->setRawContent($text . "\n\n" . $content);
    }
}
