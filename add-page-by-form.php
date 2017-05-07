<?php
namespace Grav\Plugin;

use Grav\Common\Filesystem\Folder;
use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AddPageByFormPlugin
 * @package Grav\Plugin
 */
class AddPageByFormPlugin extends Plugin
{

    public $newPageRoute = '?';

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
        ];
    }

    public function onFormValidationProcessed(Event $event)
    {
        $grav = Grav::instance();
        $config = $grav['config'];
        $form = $event['form'];
        $uri = $grav['uri']->url;
        $session = $grav['session'];

        /*  if files have been uploaded and destination is 'self'
            then prepare for moving the files to the new page
            else do nothing
        */
        $this->moveSelfFiles = false;
        $destination = $config->get('plugins.form.files.destination', '@self');
        // Get queue from session
        $queue = $session->getFlashObject('files-upload');
        $thisQueue = $queue[base64_encode($uri)];
        if ($thisQueue) {
            $formdata = $form->toArray();
            $process = isset($formdata['process']) ? $formdata['process'] : [];
            $formDef = $grav['page']->header()->form;
            $fields = $formDef['fields'];
            foreach ($fields as $array) {
                if (array_key_exists('destination', $array)) {
                    $destination = $array['destination'];
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
            // Save uploaded files properties to process in onFormProcessed() 
            $this->uploads[] = $thisQueue;
        }
        else {
            // Restore queue in session again
            $session->setFlashObject('files-upload', $queue);
            $this->moveSelfFiles = false;
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

                    $page = $this->grav['page'];
                    $header = $page->header();
                    $formPageRelativePagePath = $page->relativePagePath();

                    $parent = '';
                    $subroute = '';
                    $addUsername = false;
                    $addTimestamp = false;
                    // Get settings from pageconfig block
                    $positives = ['1','on','true'];
                    if ( isset($header->pageconfig) && is_array($header->pageconfig) ) {
                        $pageconfig = $header->pageconfig;
                        if ( isset($header->pageconfig['parent']) ) {
                            $parent = strtolower(trim($header->pageconfig['parent']));
                        }
                        if ( isset($header->pageconfig['subroute']) ) {
                            $subroute = strtolower(trim($header->pageconfig['subroute']));
                        }
                        if ( isset($header->pageconfig['overwrite']) ) {
                            $overwrite = in_array(strtolower(trim($header->pageconfig['overwrite'])), $positives);
                        }
                        if ( isset($header->pageconfig['username']) ) {
                            $addUsername = in_array(strtolower(trim($header->pageconfig['username'])), $positives);
                        }
                        if ( isset($header->pageconfig['timestamp']) ) {
                            $addTimestamp = in_array(strtolower(trim($header->pageconfig['timestamp'])), $positives);
                        }
                    }

                    // Get all 'normal' form fields
                    $formdata = $form->value()->toArray();

                    // Override parent
                    if (isset($formdata['parent']) ) {
                        $parent = strtolower(trim($formdata['parent']));
                    }
                    // Removes trailing slash if present
                    $parent = rtrim($parent, DS);

                    // Get parent page
                    if ($parent != '') {
                        if ($parent[0] != DS) {
                            $parent = $page->route() . DS . $parent;
                        }
                        $parent_page = $this->grav['page']->find($parent);
                        // Check whether the parent page exists
                        if (!$parent_page) {
                            $this->grav['log']->error('The parent "'.$parent.'" does not exist.');
                            // Gracefully continue by adding the new page as a child page of the form page
                            $parent_page = $page;
                        }
                    }
                    else {
                        $parent_page = $page;
                    }

/* >>>> BTW at this point $parent_page is a page <<<< */

                    // Get language and apply to the new page
                    $language = trim(basename($parent_page->extension(), 'md'), '.') ?: null;

                    // Override subroute
                    if (isset($formdata['subroute']) ) {
                        $subroute = strtolower(trim($formdata['subroute']));
                    }
                    // Removes preceding and trailing slashes if present
                    $subroute = trim($subroute, DS);

                    $path = $parent_page->path();
                    $route = $parent_page->route();

                    if ($subroute != '') {
                        // Create subroute path if it doesn't exist
                        $slugs = explode(DS, $subroute);
                        for ($i=0; $i < count($slugs); $i++) {
                            $route = $route . DS . $slugs[$i];
                            if ($page = $this->grav['page']->find($route)) {
                                $path = $path . DS . $page->folder();
                            }
                            else {
                                $path = $path . DS . $slugs[$i];
                            }
                            if (!file_exists($path)) {
                                Folder::create($path);
                            }
                        }
                    }
                    
                    $parentPagePath = $path;
                    $parentPageRoute = $route;

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

                    // Get plugin config settings
                    $dateFormat = $this->config->get('plugins.add-page-by-form.dateformat');

                    // Assemble the new page frontmatter from the pagefrontmatter block as set in
                    // the form page and the form field values. Form field values override values
                    // set in the pagefrontmatter block
                    if ( isset($header->pagefrontmatter) && is_array($header->pagefrontmatter) ) {
                        $pagefrontmatter = $header->pagefrontmatter;
                        if (isset($formdata)) {
                            $pagefrontmatter = array_merge($pagefrontmatter, $formdata);
                        }
                        
                        // Override overwrite mode (assume a checkbox field)
                        if (isset($formdata['overwrite']) ) {
                            $overwrite = ((string)$formdata['overwrite'] === '1');
                        }
                        
                        // Remove unwanted items
                        unset($pagefrontmatter['_json']);
                        unset($pagefrontmatter['content']);

                        // Add items from 'pageconfig' block
                        $pagefrontmatter['parent'] = $parentPageRoute;
                        $pagefrontmatter['overwrite'] = $overwrite;
                        if ($addUsername) {
                            $username = $this->grav['session']->user->username;
                            if (is_null($username)) {
                                $username ='';
                            }
                            $pagefrontmatter['username'] = $username;
                        }

                        /* Here you can insert anything else into the new page frontmatter

                            $result = 'Hello World';
                            $pagefrontmatter['result'] = $result;

                        */
                    }
                    
                    // Create a slug to be used as the page name (used publicly in URLs etc.)
                    $slug = $this->sanitizeFilename($pagefrontmatter['title']);
                    $newPageFolder = $parentPagePath . DS . $slug;
                    // Check overwrite mode
                    if ($overwrite) {
                        // Overwrite page; simply delete folder to remove existing media as well
                        if (file_exists($newPageFolder)) {
                            Folder::delete($newPageFolder);
                        }
                    }
                    else {
                        // Scan for the next available sequential suffix
                        $version = 0;
                        // Keep incrementing the page slug suffix to keep earlier versions / duplicates
                        while (file_exists($newPageFolder)) {
                            $version += 1;
                            $newPageFolder = $parentPagePath . DS . $slug . '-' . $version;
                        }
                        if ($version > 0) {
                            $slug = $slug . '-' . $version;
                        }
                    }

                    // Create and add the page to Grav
                    try {
                        /** @var Pages $pages */
                        $pages = $this->grav['pages'];
                        // Create new page
                        $page = new Page;
                        
                        if ($language != '') {
                          $page->name('default.' . $language . '.md');
                        }
                        else {
                            $page->name('default.md');
                        }
                        // Store route to new page
                        $this->newPageRoute = $parentPageRoute . DS . $slug;
                        $path = $parentPagePath . DS . $slug. DS . $page->name();
                        $page->filePath($path);
                        $page->route($this->newPageRoute);
                        $page->rawMarkdown((string) $content);
                        $page->content((string) $content);
                        $page->file()->markdown($page->rawMarkdown());

                        // Actual page file save
                        $page->save();

                        // Add page to Pages object with routing info
                        $pages->addPage($page, $path);

                        // Move uploaded files to the new page folder
                        // and prepare uploaded file properties
                        if ($this->moveSelfFiles) {
                            $uploads = $this->uploads;
                            foreach ($uploads as $key => $upload) {
                                foreach ($upload as $key => $files) {
                                    $filefields[$key] = array();
                                    $i = 0;
                                    foreach ($files as $destination => $file) {
                                        $foldername = substr($destination, strrpos($destination, DS) + 1);
                                        $destination = $file['path'];
                                        $tmp_name = $file['tmp_name'];
                                        if (strpos($destination, $formPageRelativePagePath) !== false) {
                                            // Destination points to this form page
                                            // Assume this is caused by "destination: @self"
                                            // Change destination to point to the new page
                                            $destinationFilePath = $parentPagePath . DS . $slug . DS . $foldername;
                                        }
                                        else {
                                            $destinationFilePath = ROOT_DIR . $destination;
                                        }
                                        rename($tmp_name, $destinationFilePath);
                                        $filefields[$key][$i] = '';
                                        foreach ($file as $property => $value) {
                                            if ($property != 'tmp_name') {
                                                $filefields[$key][$i][$property] = $value;
                                            }
                                        }
                                        // (Re)Set file path starting at the root folder
                                        $filefields[$key][$i]['path'] = str_replace(rtrim(ROOT_DIR, DS), '', $destinationFilePath);
                                        $i++;
                                    }
                                }
                            }
                        }

                        // Add uploaded file properties to frontmatter
                        if (isset($filefields) && isset($pagefrontmatter)) {
                            $pagefrontmatter = array_merge($pagefrontmatter, $filefields);
                        }

                        // Add frontmatter to the page header
                        $header = $pagefrontmatter;
                        $page->header((object)$header);
                        $page->frontmatter(Yaml::dump((array)$page->header()));

                        // Update the new page
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
                break;
            case 'display':
                $route = (string)$params;
                // The Form plugin does not know how to handle '@self' as a
                // display parameter, so do the redirect to the new page
                if (strtolower($route) == '@self') {
                    $route = $this->newPageRoute;
                    /** @var Twig $twig */
                    $twig = $this->grav['twig'];
                    $twig->twig_vars['form'] = $form;
                    /** @var Pages $pages */
                    $pages = $this->grav['pages'];
                    $page = $pages->dispatch($route, false);
                    // Redirect to the new page
                    unset($this->grav['page']);
                    $this->grav['page'] = $page;
                    $this->grav->redirect($route);
                }
                break;
        }
    }


    public function onPageInitialized()
    {
        $assets = $this->grav['assets'];

        // Add jQuery library
        $assets->add('jquery', 101);

        // Add SimpleMDE Markdown Editor
        $assets->addCss('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', 100);
        $assets->addJs('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', 100);
        
        // Add custom styles
        $assets->addCss('plugin://add-page-by-form/assets/css/customstyles.css', 110);

        // Load inline Javascript code from configuration file
        $assets->addInlineJs(file_get_contents('plugin://add-page-by-form/assets/js/simplemde_config.js'), 110);

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


    public function sanitizeFilename($f) {
        /* Source: http://www.house6.com/blog/?p=83
            a combination of various methods
            we don't want to convert html entities, or do any url encoding
            we want to retain the "essence" of the original file name, if possible
            char replace table found at:
            http://www.php.net/manual/en/function.strtr.php#98669
            Input:
            "Agnes Åström's _amazing!!_ photo (#2) of the house of Kjell Bækkelund [taken @ Stockholm 05-05-2003].jpg";
            Output:
            "agnes-astroms-_amazing_-photo-nr-2-of-the-house-of-kjell-bakkelund-taken-at-stockholm-05-05-2003.jpg"
        */
        $replace_chars = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
        );
        $f = strtr($f, $replace_chars);
        // convert & to "and", @ to "at", and # to "number"
        $f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-nr-'), $f);
        $f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
        $f = str_replace(' ', '-', $f); // convert space to hyphen 
        $f = str_replace('\'', '', $f); // removes apostrophes
        $f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
        $f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
        return strtolower($f);
    }

}
