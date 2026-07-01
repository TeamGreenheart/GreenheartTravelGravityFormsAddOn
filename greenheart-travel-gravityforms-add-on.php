<?php

/*
 * Plugin Name:     Greenheart Travel Gravity Forms Add-On
 * Plugin URI:      https://greenheart.org
 * Description:     Gravity Forms customizations for Greenheart Travel. This plugin does the following enhancements:
 *                 - Allows admins to attach a Gravity Form to a job posting page,
 *                 - Allows admins to define criteria for job postings 
 *                 - Evaluates those criteria on form submission and dynamically routes Notifications and Confirmations based on the applicant's responses to the form

 * Version:         1.0.0
 * Author:          Greenheart International
 * License:         GPL2
 * Class:           greenheart_travel_gf_addon
 * Text Domain:     greenheart_travel_gf_addon
 */

 defined( 'ABSPATH' ) OR exit;

 if ( ! class_exists( 'GreenheartTravelGF' ) ) {

    add_action( 'plugins_loaded', array ( 'GreenheartTravelGF', 'get_instance' ), 1 );

    // register activation will not work from plugins_loaded ##
    register_activation_hook( __FILE__, array ( 'GreenheartTravelGF', 'register_activation_hook' ) );

    class GreenheartTravelGF {

        //Singleton ##
        private static $instance = null;

        // Plugin Settings
        const VERSION = '1.0.0';
        const TEXTDOMAIN = 'greenheart_travel_gf_addon'; // for translation ##
        public static $GFActive = false; #Start False
        public static $IsStarfront = false; #Start False
        public static $formId = 0; #Start at 0, will be set on construct
        /**
         * Creates or returns a singleton instance of this class.
         *
         * @return  GreenheartTravelGF - A single instance of this class.
         */
        public static function get_instance() : GreenheartTravelGF
        {

            if (  null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }

        /**
         * Instantiate Class
         *
         * @since       0.2
         * @return      void
         */
        private function __construct()
        {
            self::$formId = $this->getFormId();

            // deactivation ##
            register_deactivation_hook( __FILE__, array ( $this, 'register_deactivation_hook' ) );

            // load libraries ##
            self::load_libraries();

            // check Gravity Forms
            \add_action( 'gform_loaded', array(__CLASS__, 'activateClass'), 10, 0 );
            \add_action('after_setup_theme', array(__CLASS__, 'checkStarfront'), 10, 0);

            
        }

        public static function activateClass() : void
        {
            self::$GFActive = true;
        }

        public static function checkStarfront() : void
        {
            if(defined('rbt\Theme::VERSION')) {
                self::$IsStarfront = true;
            }
        }

        public static function register_activation_hook() : void
        {
            \update_option(self::TEXTDOMAIN, self::VERSION);
            #When plugin is activated, the feature is set to active
            #You can deactivate the feature in the settings pane of the Header/Footer theme options
            #if you deactivate GDPR but the plugin is still active, you can see your scripts in the Header/Footer theme options although they will not be served
            \update_option(self::TEXTDOMAIN . '_active', 1);
        }


        public function register_deactivation_hook() : void
        {

            \delete_option(self::TEXTDOMAIN);

        }

        /**
         * Get Plugin URL
         *
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_url( string $path = '' ) : string
        {

            return \plugins_url( $path, __FILE__ );

        }


        /**
         * Get Plugin Path
         *
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_path( string $path = '' ) : string
        {

            return \plugin_dir_path( __FILE__ ).$path;

        }

        /**
        * Load Libraries
        *
        * @since        2.0
        * @return       void
        * This needs to be skinny as it is loaded in the critical, pre-hydration website state
        */
		private static function load_libraries() : void
        {
            #General functions
            require __DIR__ . '/library/functions.php';

            #Register postmeta for job criteria
            require __DIR__ . '/library/postmeta.php';
            $Postmeta = new \rbt\gftravel\Postmeta();

            #Submission evaluation logic
            require __DIR__ . '/library/submission.php';
            $Submission = new \rbt\gftravel\Submission();

        }

        /*
        * Resolve form ID per environment
        */
        private function getFormId() : int {

			/* IS LOCAL */
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                return 4;
            }

			/* IS DEVELOPMENT */
            if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'dev.') {
                return 4;
            }
			/* IS PRODUCTION */
            return 6;

        }
    }
}
