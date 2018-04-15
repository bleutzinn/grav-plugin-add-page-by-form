<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\File\YamlFile;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AddPageByFormPlugin
 * @package Grav\Plugin
 */
class AddPageByFormPlugin extends Plugin
{

    private $new_page_route = '';
    private $move_self_files = false;
    private $say_my_name = 'addpage';
    private $uploads = array();
    private $page_frontmatter = array();

    /**
     * Extends a path
     *
     * @param string $path
     * @param string $page
     * @param string $slug
     *
     * @return string $path
     */
    public function buildPath($path, $page, $slug)
    {
        if (!is_null($page)) {
            return $path . DS . $page->folder();
        }
        else {
            return $path . DS . $slug;
        }
    }

    /**
     * Extends a route
     *
     * @param string $route
     * @param string $page
     * @param string $slug
     *
     * @return string $route
     */
    public function buildRoute($route, $page, $slug)
    {
        if (!is_null($page)) {
            return $route . DS . $page->slug();
        }
        else {
            return $route . DS . $slug;
        }
    }

    /**
     *
     * @param string $route
     * @param boolean $create
     *
     * @return string $path
     *
     * Crawl a route, using modular page folder names as a fallback and
     * optionally creating non existing folders along the way
     */
    public function crawlRoute($route, $create = false)
    {
        $path = USER_DIR . 'pages';
        if (!is_null($route) && isset($route)) {
            if ($route != DS) {
                $slugs = explode(DS, $route);
                $route = '';
                for ($i=1; $i < count($slugs); $i++) {
                    $page = $this->pageExists($route, $slugs[$i]);
                    if (is_null($page)) {
                        if ($create) {
                            // Create folder if it doesn't exist
                            $slugs[$i] = $this->createFolder($path . DS . $slugs[$i]);
                        }
                        else {
                            return null;
                        }
                    }
                    $route = $this->buildRoute($route, $page, $slugs[$i]);
                    $path = $this->buildPath($path, $page, $slugs[$i]);
                }
            }
        }
        return ['route' => $route, 'path' => $path];
    }

    /**
     * Create a folder if it does not exist
     *
     * @param string $path
     *
     * @return string $folder_name
     */
    public function createFolder($path)
    {
        $folder_names = explode(DS, $path);
        $folder_name = $folder_names[count($folder_names) - 1];
        if (!file_exists($path)) {
            // split and sanitize leaf as a slug (disallowing periods)
            $folder_name = $this->sanitize($folder_name, 'slug');
            unset($folder_names[count($folder_names) - 1]);
            $path = implode(DS, $folder_names);
            Folder::create($path . DS . $folder_name);
        }
        return $folder_name;
    }

    /**
     * Get parent page
     *
     * @param string $parent
     * @param object $page
     *
     * @return object $parent_page
     */
    public function getParentPage($parent, $page)
    {
        if ($parent != '') {
            // Check for a relative parent
            if($parent[0] != DS) {
                // Make an absolute route starting from this form page
                $parent = $page->route() . DS . $parent;
            }

            // Check whether the parent page exists, allowing for modular pages
            // but disallowing empty folders in the route
            if (!is_null($this->crawlRoute($parent, false))) {
                $parent_page = $this->grav['page']->find($parent);
            }
            else {
                // Gracefully continue and falback to adding the
                // new page as a child page of the form page
                $parent_page = $page;
            }
        }
        else {
            $parent_page = $page;
        }

        return $parent_page; // can be a page or null if there is no page.
    }

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

    /**
     * Build array of upload file 
     *
     * @param string $form_page_relative_page_path
     * @param string $parentpage_path
     * @param string $slug
     *
     * @return array $file_fields
     */
    public function moveFiles($form_page_relative_page_path, $parent_page_path, $slug)
    {
        // Move uploaded files to the new page folder
        // and prepare uploaded file properties
        $file_fields = array();
        if ($this->move_self_files) {
            $uploads = $this->uploads;
            foreach ($uploads as $key => $upload) {
                foreach ($upload as $key => $files) {
                    $file_fields[$key] = array();
                    $i = 0;
                    foreach ($files as $destination => $file) {
                        $destination = $file['path'];
                        if (strpos($destination, $form_page_relative_page_path) !== false) {
                            // Destination points to this form page
                            // Assume this is caused by "destination: @self"
                            // Sanitize filename and change destination to point
                            // to the new page
                            $file_name = $this->sanitize(substr($destination, strrpos($destination, DS) + 1));
                            $destination_file_path = $parent_page_path . DS . $slug . DS . $file_name;
                        }
                        else {
                            $destination_file_path = ROOT_DIR . $destination;
                        }
                        rename($file['tmp_name'], $destination_file_path);
                        $file_fields[$key][$i] = array();
                        foreach ($file as $property => $value) {
                            if ($property == 'name') {
                                $value = $this->sanitize($value);
                            }
                            if ($property != 'tmp_name') {
                                $file_fields[$key][$i][$property] = $value;
                            }
                        }
                        // (Re)Set file path starting at the root folder
                        $file_fields[$key][$i]['path'] = str_replace(rtrim(ROOT_DIR, DS), '', $destination_file_path);
                        $i++;
                    }
                }
            }
        }
        return $file_fields;
    }

    /**
     * Handle form action
     *
     * @param $event
     *
     */
    public function onFormProcessed(Event $event)
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];

        switch ($action) {
            case $this->say_my_name:
                if(isset($_POST)) {
                    $page = $this->grav['page'];
                    $header = $page->header();
                    $form_page_relative_page_path = $page->relativePagePath();

                    // Get default settings form plugin config
                    $include_username = $this->config->get('plugins.add-page-by-form.include_username');
                    $overwrite_mode = $this->config->get('plugins.add-page-by-form.overwrite_mode');
                    $date_format = $this->config->get('plugins.add-page-by-form.date_display_format');
                    $auto_taxonomy_types = $this->config->get('plugins.add-page-by-form.auto_taxonomy_types');
                    $slug_field = '';

                    // For next plugin version
                    $include_timestamp = false;

                    // Initialize default subroute
                    $sub_route = '';

                    // Get settings from pageconfig block and override values via form fields
                    if (isset($header->pageconfig) && is_array($header->pageconfig) ) {
                        $pageconfig = $header->pageconfig;
                        $positives = ['1','on','true'];

                        if ( isset($pageconfig['parent']) ) {
                            $parent = strtolower(trim($pageconfig['parent']));
                        }
                        if ( isset($pageconfig['subroute']) ) {
                            $sub_route = strtolower(trim($pageconfig['subroute']));
                        }
                        if ( isset($pageconfig['include_username']) ) {
                            $include_username = in_array(strtolower(trim($pageconfig['include_username'])), $positives);
                        }
                        if ( isset($pageconfig['overwrite_mode']) ) {
                            $overwrite_mode = in_array(strtolower(trim($pageconfig['overwrite_mode'])), $positives);
                        }
                        if ( isset($pageconfig['slug_field']) ) {
                            $slug_field = strtolower(trim($pageconfig['slug_field']));
                        }
                    }

                    // Assemble the new page frontmatter from the page_frontmatter block as set in
                    // the form page
                    if ( isset($header->pagefrontmatter) && is_array($header->pagefrontmatter) ) {
                        $page_frontmatter = $header->pagefrontmatter;
                    }
                    else {
                        $page_frontmatter = array();
                    }

                    // Add username (or not)
                    if ($include_username) {
                        $username = null;
                        if (!is_null($this->grav['session']->user)) {
                            $username = $this->grav['session']->user->username;
                        }
                        if (is_null($username)) {
                            $username ='';
                        }
                        $page_frontmatter['username'] = $username;
                    }

                    // Get all form field values
                    $form_data = $form->value()->toArray();
                    if (isset($form_data)) {

                        // Append taxonomy
                        if (isset($form_data['taxonomy']) && is_array($form_data['taxonomy'])) {
                            // Convert comma separated list into array assuming double quoted items
                            foreach ($form_data['taxonomy'] as $key => $value) {
                                $values = str_getcsv($value, ',', '"');
                                foreach ($values as $k => $v) {
                                    $values[$k] = trim($v);
                                }
                                $form_data['taxonomy'][$key] = $values;
                            }
                            if (isset($page_frontmatter['taxonomy'])) {
                                // Append type/values
                                $page_frontmatter['taxonomy'] = array_merge_recursive($page_frontmatter['taxonomy'], $form_data['taxonomy']);
                                // Remove duplicate values
                                foreach ($page_frontmatter['taxonomy'] as $key => $value) {
                                    if (is_array($page_frontmatter['taxonomy'][$key])) {
                                        $page_frontmatter['taxonomy'][$key] = array_keys(array_flip($page_frontmatter['taxonomy'][$key]));
                                    }
                                }
                            }
                            else {
                                // Add taxonomy, types and values
                                $page_frontmatter['taxonomy'] = $form_data['taxonomy'];
                            }
                            // Remove taxonomy from form data (to prevent merging raw data)
                            unset($form_data['taxonomy']);
                        }

                        // Merge variables from pagefrontmatter block and form fields;
                        // Values that have been through a Twig Processor are in the
                        // page_frontmatter and take precedence over the form values
                        $page_frontmatter = array_merge($page_frontmatter, $form_data);
                        //dump($page_frontmatter);exit;
                    }


                    // Here you can insert anything else into the new page frontmatter
                    /*

                        $result = 'Hello World';
                        $page_frontmatter['result'] = $result;

                    */


                    // If content is not included as a form value then fallback to config default
                    if (isset($page_frontmatter['content']) ) {
                        $content = $page_frontmatter['content'];
                    }
                    else {
                        $content = $this->config->get('plugins.add-page-by-form.default_content');
                    }

                    // Remove unwanted items from new page frontmatter
                    unset($page_frontmatter['_json']);
                    unset($page_frontmatter['content']);
                    unset($page_frontmatter['parent']);
                    unset($page_frontmatter['subroute']);

                    // Initialize default page parent
                    if (isset($header->parent)) {
                        // For backwards compatibility
                        $parent = $header->parent;
                    }
                    else {
                        $parent = '';
                    }

                    // Override parent if set in pageconfig block
                    if (isset($pageconfig['parent']) ) {
                        $parent = strtolower(trim($pageconfig['parent']));
                    }
                    // Override parent if set in the form
                    if (isset($form_data['parent']) ) {
                        $parent = strtolower(trim($form_data['parent']));
                    }

                    // Removes multiple concatenated slashes plus a trailing slash if present
                    if (strlen($parent) > 1) {
                        $parent = preg_replace('/[\/]+/', DS, $parent);
                        $parent = rtrim($parent, DS);
                    }

                    $parent_page = $this->getParentPage($parent, $page);

                    $parent_page_path = $parent_page->path();
                    $parent_page_route = $parent_page->route();

                    // Override subroute
                    if (isset($form_data['subroute']) ) {
                        $sub_route = mb_strtolower(trim($form_data['subroute']));
                    }
                        if ($sub_route != '') {
                            // Remove any multiple concatenated slashes
                            $sub_route = preg_replace('/[\/]+/', DS, $sub_route);
                            // Remove preceding and trailing slashes if present
                            $sub_route = trim($sub_route, DS);
                            // Prepare for crawling
                            $parent_page_route = $parent_page->route() . DS . $sub_route;
                            // Create subroute path if it doesn't exist
                            $parent_destination = $this->crawlRoute($parent_page_route, true);
                            $parent_page_route = $parent_destination['route'];
                            $parent_page_path = $parent_destination['path'];
                    }
                    // Create a slug to be used as the page name (used publicly in URLs etc.)
                    if ($slug_field != '') {
                        if (isset($page_frontmatter[$slug_field])) {
                            $slug = $this->sanitize($page_frontmatter[$slug_field], 'slug');
                        }
                    }
                    if (!isset($slug)) {
                        if (isset($page_frontmatter['title'])) {
                            $slug = $this->sanitize($page_frontmatter['title'], 'slug');
                        }
                        else {
                            $slug = $this->config->get('plugins.add-page-by-form.default_title');
                            $slug = $this->sanitize($slug, 'slug');
                        }
                    }

                    $new_page_folder = $parent_page_path . DS . $slug;

                    // Check overwrite mode
                    if ($overwrite_mode) {
                        // Overwrite page; simply delete folder to remove existing media as well
                        if (file_exists($new_page_folder)) {
                            Folder::delete($new_page_folder);
                        }
                    }
                    else {
                        // Scan for the next available sequential suffix
                        $version = 0;
                        // Keep incrementing the page slug suffix to keep earlier versions / duplicates
                        while (file_exists($new_page_folder)) {
                            $version += 1;
                            $new_page_folder = $parent_page_path . DS . $slug . '-' . $version;
                        }
                        if ($version > 0) {
                            $slug = $slug . '-' . $version;
                        }
                    }

                    // Store the route so it can be used to redirect to if needed
                    $this->new_page_route = $parent_page_route . DS . $slug;

                    // Create and add the page to Grav
                    try {
                        /** @var Pages $pages */
                        $pages = $this->grav['pages'];
                        // Create new page
                        $new_page = new Page;

                        // Get active or default language for page filename
                        // (e.g. 'nl' -> 'default.nl.md') 
                        $language = Grav::instance()['language']->getLanguage() ?: null;

                        if ($language != '') {
                          $new_page->name('default.' . $language . '.md');
                        }
                        else {
                            $new_page->name('default.md');
                        }

                        $path = $parent_page_path . DS . $slug. DS . $new_page->name();
                        $new_page->filePath($path);
                        $new_page->rawMarkdown((string) $content);
                        $new_page->file()->markdown($new_page->rawMarkdown());

                        // First page save (required to have an existing new page folder
                        // to store any files with destination '@self' in)
                        $new_page->save();

                        // Add page to Pages object with routing info
                        $pages->addPage($new_page, $path);

                        $file_fields = $this->moveFiles($form_page_relative_page_path, $parent_page_path, $slug);

                        // Add uploaded file properties to frontmatter
                        if (isset($file_fields) && isset($page_frontmatter)) {
                            $page_frontmatter = array_merge($page_frontmatter, $file_fields);
                        }

                        // Add frontmatter to the page header
                        $header = $page_frontmatter;
                        $this->page_frontmatter = $page_frontmatter;
                        $new_page->header((object)$header);

                        // Update the new page
                        $new_page->save();

                        // Process any new taxonomy types
                        if ($auto_taxonomy_types && isset($page_frontmatter['taxonomy'])) {

                            // Read site configuration
                            $grav = Grav::instance();
                            $locator = $grav['locator'];
                            $filename = 'config://site.yaml';
                            $file = YamlFile::instance($locator->findResource($filename, true, true));
                            $site_config = Yaml::parse($file->load());

                            // Merge taxonomy types
                            $taxonomies = (array)$this->config->get('site.taxonomies');
                            foreach(array_keys($page_frontmatter['taxonomy']) as $type) {
                                $taxonomies = array_merge($taxonomies, (array)$type);
                            }

                            // Don't bother if there are no new taxonomy types
                            if (count(array_unique($taxonomies)) > count($site_config['taxonomies'])) {
                                $this->config->set('site.taxonomies', $taxonomies);
                                $taxonomies_merged = array();
                                $taxonomies_merged['taxonomies'] = array_values(array_unique($taxonomies));
                                $site_config = array_merge($site_config, $taxonomies_merged);

                                // Update taxonomy types in site.yaml
                                $file->save($site_config);
                                $file->free();
                            }
                        }

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
            case 'redirect':  // look at Form plugin and mimic behaviour
                // The Form plugin does not know how to handle '@self' as a redirect
                // or display parameter, so prepare the redirect to the new page
                switch (strtolower((string)$params)) {
                    case '@self':
                        $route = $this->new_page_route;
                        break;
                    case '@self-admin':
                        $admin_route = $this->config->get('plugins.admin.route');
                        if ($admin_route && $this->config->get('plugins.admin.enabled')) {
                            $base = DS . trim($admin_route, DS);
                            $route = $base . DS . 'pages' . $this->new_page_route;
                        }
                        else {
                            // Admin not installed or inactive
                            // Fall back to @self
                            $route = $this->new_page_route;
                        }
                        break;
                    default:
                        // No valid redirect to self parameter
                        $route = '';
                }

                // Do the redirect
                // BTW if there is no route the redirect is handed over to the Form plugin
                if ($route) {

                    /** @var Twig $twig */
                    $twig = $this->grav['twig'];
                    $twig->twig_vars['form'] = $form;
                    $twig->twig_vars['pagefrontmatter'] = $this->page_frontmatter;

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

    /**
     * Process form after validation
     *
     * @param $event
     *
     */
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
        $this->move_self_files = false;
        $destination = $config->get('plugins.form.files.destination', '@self');
        // Get queue from session
        $queue = $session->getFlashObject('files-upload');
        $this_queue = $queue[base64_encode($uri)];
        if ($this_queue) {
            $form_data = $form->toArray();
            $process = isset($form_data['process']) ? $form_data['process'] : [];
            $form_def = $grav['page']->header()->form;
            $fields = $form_def['fields'];
            // Just a single destination self will trigger the handling by this plugin
            // otherwise, the files move is handed over to the Form Plugin
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
                        if ($action == $this->say_my_name) {
                            $this->move_self_files = true;
                            break;
                        }
                    }
                }
            }
        }
        if (($destination === '@self' || $destination === 'self@') && $this->move_self_files) {
            // Save uploaded files properties to process in onFormProcessed() 
            $this->uploads[] = $this_queue;
        }
        else {
            // Restore queue in session again and let form plugin do the upload
            $session->setFlashObject('files-upload', $queue);
            $this->move_self_files = false;
        }
    }

    /**
     * Add assets
     *
     */
    public function onPageInitialized()
    {
        $data = Yaml::parse($this->grav['page']->frontmatter());
        // Only act upon forms which are intended to be processed by this plugin
        if (isset($data['form']) && isset($data['form']['name']) &&
            strtolower(substr($data['form']['name'], 0, 7)) == $this->say_my_name) {

            $assets = $this->grav['assets'];
            // Add jQuery library (no harm done when already present)
            $assets->add('jquery', 101);
            // Add SimpleMDE Markdown Editor
            $assets->addCss('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', 100);
            $assets->addJs('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', 100);
            // Add custom styles
            $assets->addCss('plugin://add-page-by-form/assets/css/customstyles.css', 110);
            // Load inline Javascript code from configuration file
            $assets->addInlineJs(file_get_contents('plugin://add-page-by-form/assets/js/simplemde_config.js'), 110);
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
        // Enable the events we are interested in
        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0],
        ]);
    }

    /**
     * Check whether a page exists at the specified route irrespective of page type
     *
     * @param string $route
     * @param string $slug
     *
     * @return object $page
     */
    public function pageExists($route, $slug)
    {
        $page = $this->grav['page']->find($route . DS . $slug);
        if (is_null($page) && !empty($slug) && $slug[0] != '_') {
            $page = $this->grav['page']->find($route . DS . '_' . $slug);
        }
        return $page;
    }

    /**
     * Sanitize a string into a safe filename or slug
     *
     * @param string $f
     *
     * @return string
     */
    public function sanitize($f, $type = 'file') {
        /*  A combination of various methods to sanitize a string while retaining
            the "essence" of the original file name as much as possible.
            Note: unsuitable for file paths as '/' and '\' are filtered out.
            Sources:
                http://www.house6.com/blog/?p=83
            and
                http://stackoverflow.com/a/24984010
        */
        $replace_chars = array(
            '&amp;' => '-and-', '@' => '-at-', '©' => 'c', '®' => 'r', 'À' => 'a',
            'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'ae','Ç' => 'c',
            'È' => 'e', 'É' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
            'Ï' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y',
            'ß' => 'ss','à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae','ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'p', 'ÿ' => 'y', 'Ā' => 'a',
            'ā' => 'a', 'Ă' => 'a', 'ă' => 'a', 'Ą' => 'a', 'ą' => 'a', 'Ć' => 'c',
            'ć' => 'c', 'Ĉ' => 'c', 'ĉ' => 'c', 'Ċ' => 'c', 'ċ' => 'c', 'Č' => 'c',
            'č' => 'c', 'Ď' => 'd', 'ď' => 'd', 'Đ' => 'd', 'đ' => 'd', 'Ē' => 'e',
            'ē' => 'e', 'Ĕ' => 'e', 'ĕ' => 'e', 'Ė' => 'e', 'ė' => 'e', 'Ę' => 'e',
            'ę' => 'e', 'Ě' => 'e', 'ě' => 'e', 'Ĝ' => 'g', 'ĝ' => 'g', 'Ğ' => 'g',
            'ğ' => 'g', 'Ġ' => 'g', 'ġ' => 'g', 'Ģ' => 'g', 'ģ' => 'g', 'Ĥ' => 'h',
            'ĥ' => 'h', 'Ħ' => 'h', 'ħ' => 'h', 'Ĩ' => 'i', 'ĩ' => 'i', 'Ī' => 'i',
            'ī' => 'i', 'Ĭ' => 'i', 'ĭ' => 'i', 'Į' => 'i', 'į' => 'i', 'İ' => 'i',
            'ı' => 'i', 'Ĳ' => 'ij','ĳ' => 'ij','Ĵ' => 'j', 'ĵ' => 'j', 'Ķ' => 'k',
            'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'l', 'ĺ' => 'l', 'Ļ' => 'l', 'ļ' => 'l',
            'Ľ' => 'l', 'ľ' => 'l', 'Ŀ' => 'l', 'ŀ' => 'l', 'Ł' => 'l', 'ł' => 'l',
            'Ń' => 'n', 'ń' => 'n', 'Ņ' => 'n', 'ņ' => 'n', 'Ň' => 'n', 'ň' => 'n',
            'ŉ' => 'n', 'Ŋ' => 'n', 'ŋ' => 'n', 'Ō' => 'o', 'ō' => 'o', 'Ŏ' => 'o',
            'ŏ' => 'o', 'Ő' => 'o', 'ő' => 'o', 'Œ' => 'oe','œ' => 'oe','Ŕ' => 'r',
            'ŕ' => 'r', 'Ŗ' => 'r', 'ŗ' => 'r', 'Ř' => 'r', 'ř' => 'r', 'Ś' => 's',
            'ś' => 's', 'Ŝ' => 's', 'ŝ' => 's', 'Ş' => 's', 'ş' => 's', 'Š' => 's',
            'š' => 's', 'Ţ' => 't', 'ţ' => 't', 'Ť' => 't', 'ť' => 't', 'Ŧ' => 't',
            'ŧ' => 't', 'Ũ' => 'u', 'ũ' => 'u', 'Ū' => 'u', 'ū' => 'u', 'Ŭ' => 'u',
            'ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'Ű' => 'u', 'ű' => 'u', 'Ų' => 'u',
            'ų' => 'u', 'Ŵ' => 'w', 'ŵ' => 'w', 'Ŷ' => 'y', 'ŷ' => 'y', 'Ÿ' => 'y',
            'Ź' => 'z', 'ź' => 'z', 'Ż' => 'z', 'ż' => 'z', 'Ž' => 'z', 'ž' => 'z',
            'ſ' => 'z', 'Ə' => 'e', 'ƒ' => 'f', 'Ơ' => 'o', 'ơ' => 'o', 'Ư' => 'u',
            'ư' => 'u', 'Ǎ' => 'a', 'ǎ' => 'a', 'Ǐ' => 'i', 'ǐ' => 'i', 'Ǒ' => 'o',
            'ǒ' => 'o', 'Ǔ' => 'u', 'ǔ' => 'u', 'Ǖ' => 'u', 'ǖ' => 'u', 'Ǘ' => 'u',
            'ǘ' => 'u', 'Ǚ' => 'u', 'ǚ' => 'u', 'Ǜ' => 'u', 'ǜ' => 'u', 'Ǻ' => 'a',
            'ǻ' => 'a', 'Ǽ' => 'ae','ǽ' => 'ae','Ǿ' => 'o', 'ǿ' => 'o', 'ə' => 'e',
            'Ё' => 'jo','Є' => 'e', 'І' => 'i', 'Ї' => 'i', 'А' => 'a', 'Б' => 'b',
            'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ж' => 'zh','З' => 'z',
            'И' => 'i', 'Й' => 'j', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
            'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u',
            'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch','Ш' => 'sh','Щ' => 'sch',
            'Ъ' => '-', 'Ы' => 'y', 'Ь' => '-', 'Э' => 'je','Ю' => 'ju','Я' => 'ja',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ж' => 'zh','з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh','щ' => 'sch','ъ' => '-','ы' => 'y', 'ь' => '-', 'э' => 'je',
            'ю' => 'ju','я' => 'ja','ё' => 'jo','є' => 'e', 'і' => 'i', 'ї' => 'i',
            'Ґ' => 'g', 'ґ' => 'g', 'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd',
            'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'h', 'ט' => 't', 'י' => 'i',
            'ך' => 'k', 'כ' => 'k', 'ל' => 'l', 'ם' => 'm', 'מ' => 'm', 'ן' => 'n',
            'נ' => 'n', 'ס' => 's', 'ע' => 'e', 'ף' => 'p', 'פ' => 'p', 'ץ' => 'C',
            'צ' => 'c', 'ק' => 'q', 'ר' => 'r', 'ש' => 'w', 'ת' => 't', '™' => 'tm',
            'Ã' => 'A', 'Ð' => 'Dj', 'Ê' => 'E', 'Ñ' => 'N', 'Þ' => 'B', 'ã' => 'a',
            'ð' => 'o', 'ñ' => 'n', '#' => '-nr-' );
        // "Translate" multi byte characters to 'corresponding' ASCII characters
        $f = strtr($f, $replace_chars);
        // Convert special characters to a hyphen
        $f = str_replace(array(
            ' ', '!', '\\', '/', '\'', '`', '"', '~', '%', '|',
            '*', '$', '^', '(' ,')', '[', ']', '{', '}',
            '+', ',', ':' ,';', '<', '=', '>', '?', '|'), '-', $f); 
        // Remove any non ASCII characters
        $f = preg_replace('/[^(\x20-\x7F)]*/','', $f);
        if ($type == 'file') {
            // Remove non-word chars (leaving hyphens and periods)
            $f = preg_replace('/[^\w\-\.]+/', '', $f);
            // Convert multiple adjacent dots into a single one
            $f = preg_replace('/[\.]+/', '.', $f);
        }
        else { // Do not allow periods, for instance for a Grav slug
            // Convert period to hyphen
            $f = str_replace('.', '-', $f);
            // Remove non-word chars (leaving hyphens)
            $f = preg_replace('/[^\w\-]+/', '', $f);
        }
        // Convert multiple adjacent hyphens into a single one
        $f = preg_replace('/[\-]+/', '-', $f);
        // Change into a lowercase string; BTW no need to use mb_strtolower() here ;)
        $f = strtolower($f);
        return $f;
    }

}