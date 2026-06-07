<?php
defined('MOODLE_INTERNAL') || die;

class mindatlas_plugin_helper_base
{
    protected $name;
    protected $path;
    public $url;
    public $settings;
    public $brand;

    function __construct()
    {
        global $CFG, $USER, $PAGE;

        $this->name = 'DFFH';
        $this->path = array(
            'classes' => "/theme/$this->name/classes",
            'templates' => "/theme/$this->name/templates",
            'pix' => "/theme/$this->name/pix",
            'js' => "/theme/$this->name/javascript",
            'css' => "/theme/$this->name/style",
        );
        $this->url  = array(
            'root' => $CFG->wwwroot,
            'pix' => $CFG->wwwroot . $this->path['pix'],
            'js' =>  $CFG->wwwroot . $this->path['js'],
            'css' => $CFG->wwwroot . $this->path['css'],

        );

        // Liberate 30/05 - removing anything theme-specific - will address erors if/where called

        /*$this->brand = array(
            'logo' => theme_mindatlas_file_from_setting('logo'),
            'copyright' => '&#169; ' . 'MindAtlas Learning Management System ' . date('Y'),

            // brand colors
            'primarycolor' => get_config('theme_mindatlas', 'primarycolor'),
            'brandcolor1' =>  get_config('theme_mindatlas', 'brandcolor1'),
            'brandcolor2' =>  get_config('theme_mindatlas', 'brandcolor2'),
            'brandcolor3' =>  get_config('theme_mindatlas', 'brandcolor3'),
            // 'brandcolor4'=>  get_config('theme_mindatlas', 'brandcolor4'),

            // login backgrounds
            'loginbackgroundimage' => theme_mindatlas_file_from_setting('loginbackgroundimage'),
            'logincarouselimage1' => theme_mindatlas_file_from_setting('logincarouselimage1'),
            'logincarouselimage2' => theme_mindatlas_file_from_setting('logincarouselimage2'),
            'logincarouselimage3' => theme_mindatlas_file_from_setting('logincarouselimage3'),
            'logincarouselimage4' => theme_mindatlas_file_from_setting('logincarouselimage4'),
            // other backgrounds
            'frontpagebackgroundimage' => theme_mindatlas_file_from_setting('frontpagebackgroundimage'),
            'dashboardbackgroundimage' => theme_mindatlas_file_from_setting('dashboardbackgroundimage'),

            // home page
            'home_introbg'          => theme_mindatlas_file_from_setting('introbg'),
            'home_banner1_img'      => theme_mindatlas_file_from_setting('banner1_img'),
            'home_banner1_title'    => get_config('theme_mindatlas', 'banner1_title'),
            'home_banner1_text'     => get_config('theme_mindatlas', 'banner1_text'),
            'home_banner1_btn'      => get_config('theme_mindatlas', 'banner1_btn'),
            'home_banner1_link'     => get_config('theme_mindatlas', 'banner1_link'),
            'home_welcome_title'      => get_config('theme_mindatlas', 'welcome_title'),
            'home_welcome_text'     => get_config('theme_mindatlas', 'welcome_text'),
            'home_welcome_text_2'     => get_config('theme_mindatlas', 'welcome_text_2'),

            //My Training
            'training_text'      => get_config('theme_mindatlas', 'training_text'),
            'training_textt'      => get_config('theme_mindatlas', 'training_textt'),
            // social meida links
            'youtube'   => get_config('theme_mindatlas', 'youtube'),
            'facebook'  => get_config('theme_mindatlas', 'facebook'),
            'twitter'   => get_config('theme_mindatlas', 'twitter'),
            'linkedin'  => get_config('theme_mindatlas', 'linkedin'),
            'instagram' => get_config('theme_mindatlas', 'instagram'),

            // banners
            'mycourses_banner' => theme_mindatlas_file_from_setting('mycourses_banner'),
            'my_training_banner' => theme_mindatlas_file_from_setting('my_training_banner'),
        );*/

        // $this->str = array(
        //     // frontpage
        //     'frontpage_welcome_title' => get_string('custom_frontpage_welcome_title', 'theme_mindatlas'),
        //     'frontpage_welcome_message' => get_string('custom_frontpage_welcome_message', 'theme_mindatlas'),
        // );

        // add data to M - Liberate 30/05 - leaving this for file path properties,which may be relevant to plugins
        $PAGE->requires->data_for_js('M.theme', $this, true);

        // Liberqte 30/05 - these plugins not installed

        /*$this->plugins = array(
            'coursepoints' => $this->is_plugin_installed('block', 'coursepoints'),
            'resources' => $this->is_plugin_installed('block', 'resources'),
            'courserating' => $this->is_plugin_installed('block', 'most_viewed_liked_course'),
        );*/

        // $PAGE->requires->data_for_js('M.page', $this->get_page(), true);

        // $this->get_page();
    }

    public function require_user()
    {
        global $PAGE;
        $PAGE->requires->data_for_js('M.user', $this->get_user(), true);
    }
    
    /**
     * PHP7.0, Moodle 3.7
     * require js file on page
     *
     * @param string $file, relative path to dirroot and filename, example 'theme/mindatlas/javascript/_theme.js' 
     * @return bool $inhead, require before <header>
     */
    function require_js(string $file, $inhead = false)
    {
        global $PAGE, $CFG;
        if (file_exists($CFG->dirroot . $file)) {
            $PAGE->requires->js($file . '?' . filemtime($CFG->dirroot . $file), $inhead);
        } else {
            print_error('filenotfound', 'error', '', null, $CFG->dirroot . $file);
        }
    }
    
    /**
     * PHP7.0, Moodle 3.7
     * require css file on page
     *
     * @param string $file, 
     */
    function require_css(string $file)
    {
        global $PAGE, $CFG;
        if (file_exists($CFG->dirroot . $file)) {
            $PAGE->requires->css($file . '?' . filemtime($CFG->dirroot . $file));
        } else {
            print_error('filenotfound', 'error', '', null, $CFG->dirroot . $file);
        }
    }    

    /**
     * PHP7.0, Moodle 3.7
     * require single js/css file from theme
     *
     * @param string $filename, 
     * @return bool $inhead, require on the top if it's a js file, css always reuqired in header
     */
     
     
     // Liberate 30/05 - commenting out as hardwired to Mindatlas theme
     
    /*public function require(string $filename, $inhead = false)
    {
        global $PAGE, $CFG;
        $filename_array = explode('.', $filename);
        $filetype = end($filename_array);

        if ($filetype == 'js') {
            $file = '/theme/mindatlas/javascript/' . $filename;
            $this->require_js($file, $inhead);
        } elseif ($filetype == 'css') {
            $file = '/theme/mindatlas/style/' . $filename;
            $this->require_css($file);
        }
    }

    public function requires_default()
    {
        global $PAGE, $USER;

        // require Moodle's embedded jquery
        $PAGE->requires->jquery();
        // require theme_mindatlas js and css
        $this->require('theme.bundle.js');
        $this->require('theme.bundle.css');
    }*/

    /**
     * requires default js css and extra js/css based on param
     * example:
     * $THEME->requires(['preload_1.js' ], ['sub/preload_2.js'], '_home.js','_home.css', 'sub/css_1.css');
     * 
     * css is always required in <head>, 
     * js is required in <footer> by default, if you want js to be required before <header>, put it in to an array.
     */
    /*public function requires()
    {
        global $PAGE, $CFG;

        // require theme default js css
        $this->requires_default();

        $args = func_get_args();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                // if filename is in an array, set $inhead to true
                foreach ($arg as $filename) {
                    $this->require($filename, true);
                }
            } else {
                $this->require($arg);
            }
        }
    }*/


    public function get_user($userid = null)
    {
        global $USER, $OUTPUT, $DB, $PAGE;
        $user = new stdClass();

        $user->id = $userid ? $userid : $USER->id;
        if ($user->id < 1) {
            $user->sesskey = $USER->sesskey;
            return $user;
        }

        $record = $DB->get_record('user', array('id' => $user->id));

        $user->auth = $record->auth;
        $user->email = $record->email;
        $user->firstname = $record->firstname;
        $user->lastname = $record->lastname;
        // var_dump($OUTPUT);
        // var_dump($PAGE->course );
        // var_dump($PAGE->context);
        // var_dump();
        try {
            $user->avatar = $OUTPUT->user_picture($record, array('size' => 100));
            $user->avatarL = $OUTPUT->user_picture($record, array('size' => 150));
        } catch (Exception $e) {
        }

        if ($user->id == $USER->id) {
            $user->profile = $USER->profile;
            $user->sesskey = $USER->sesskey;
            $user->isSiteAdmin = is_siteAdmin($USER);
        }

        return $user;
    }

    protected function get_page()
    {
        global $PAGE;
        $page = new stdClass();
        $page->bodyid = $PAGE->bodyid;
        $page->categories = $PAGE->categories;
        $page->category = $PAGE->category;
        $page->cm = $PAGE->cm;
        $page->course = $PAGE->course;
        $page->devicetypeinuse = $PAGE->devicetypeinuse;
        $page->heading = $PAGE->heading;
        $page->navigation = $PAGE->navigation;
        $page->title = $PAGE->title;
        // $page->url = $PAGE->url->__toString(); // error when page hasn't set url
        return $page;
    }

    protected function is_plugin_installed($type, $name)
    {
        global $DB;
        $record = $DB->get_record($type, array('name' => $name));
        return ($record && $record->visible);
    }

    public function collection_statement()
    {
        return get_config('theme_mindatlas', 'collection_statement');
    }
}
