<?php            
/**            
 * Plugin Name: Event Registration Form            
 * Description: A simple event registration form with payment status, QR code generation, and CAPTCHA.            
 * Version: 1.0            
 * Author: Your Name            
 */            
      
// Define constants for paths        
define('ERF_PLUGIN_DIR', plugin_dir_path(__FILE__));        
define('ERF_PLUGIN_URL', plugin_dir_url(__FILE__));        
      
// Include necessary files        
require_once ERF_PLUGIN_DIR . 'includes/dashboard.php';        
require_once ERF_PLUGIN_DIR . 'includes/email-templates.php';        
require_once ERF_PLUGIN_DIR . 'includes/event-pricing.php';      
require_once ERF_PLUGIN_DIR . 'includes/ticket-templates.php';      
require_once ERF_PLUGIN_DIR . 'includes/price-dashboard.php';        
require_once ERF_PLUGIN_DIR . 'includes/event-form-national.php'; // Include the national event form file        
require_once ERF_PLUGIN_DIR . 'includes/event-form-international.php'; // Include the international event form file        
require_once ERF_PLUGIN_DIR . 'includes/event-form-sponsor.php'; // Include the sponsor event form file        
      
// Create the database table on plugin activation        
function erf_create_table() {            
    global $wpdb;            
    $table_name = $wpdb->prefix . 'event_registrations'; // Corrected table name to avoid conflicts            
    $charset_collate = $wpdb->get_charset_collate();            
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (            
        id mediumint(9) NOT NULL AUTO_INCREMENT,            
        name tinytext NOT NULL,            
        email varchar(100) NOT NULL UNIQUE,            
        ticket_name text NOT NULL, -- New to save the ticket name            
        ticket_price varchar(10) NOT NULL, -- New to save the ticket price            
        phone varchar(15) NOT NULL,            
        institution text NOT NULL,            
        participant_type tinytext NOT NULL,            
        nik varchar(16) DEFAULT NULL, -- Changed to DEFAULT NULL to allow NULL values for international registrations            
        payment_method tinytext NOT NULL,                    
        status varchar(20) DEFAULT 'pending',            
        participant_id varchar(20) DEFAULT NULL,            
        qr_code_url text DEFAULT NULL,            
        checkin_time datetime DEFAULT NULL,          
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,            
        sponsor_name tinytext DEFAULT NULL,            
        sponsor_email varchar(100) DEFAULT NULL,            
        sponsor_phone varchar(15) DEFAULT NULL,            
        tax varchar(10) DEFAULT NULL, -- This is the value of the ticket_price x 11%            
        total_invoice varchar(10) DEFAULT NULL, -- This is ticket_price + tax            
        participant_title varchar(50) DEFAULT NULL, -- New field for participant title            
        address text DEFAULT NULL, -- New field for address            
        city varchar(100) DEFAULT NULL, -- New field for city            
        country varchar(100) DEFAULT NULL, -- New field for country            
        PRIMARY KEY  (id)            
    ) $charset_collate;";            
          
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');            
    dbDelta($sql);            
}            
register_activation_hook(__FILE__, 'erf_create_table');            
register_activation_hook(__FILE__, 'erf_register_pricing_types');            
      
// Add submenu for Event Form Templates      
add_action('admin_menu', 'erf_add_event_form_submenu');      
      
function erf_add_event_form_submenu() {      
    add_submenu_page(      
        'event_registration_form', // Parent slug (the main plugin menu)        
        'Event Form Templates', // Page title        
        'Event Form Templates', // Menu title        
        'manage_options', // Capability        
        'event-form', // Menu slug        
        'erf_event_form_page' // Callback function to display the page    
    );      
}      
  
// Function to display the event form page in the admin menu  
function erf_event_form_page() {  
    ?>  
    <div class="wrap">  
        <h1>Event Form Templates</h1>  
        <h2>National Event Registration Form</h2>  
        <?php echo erf_national_event_form(); ?>  
        <h2>International Event Registration Form</h2>  
        <?php echo erf_international_event_form(); ?>  
        <h2>Sponsor Event Registration Form</h2>  
        <?php echo erf_sponsor_event_form(); ?>  
    </div>  
    <?php  
}  
  
// Function to generate CAPTCHA    
if (!function_exists('erf_generate_captcha')) {  
    function erf_generate_captcha() {    
        $num1 = rand(1, 10);    
        $num2 = rand(1, 10);    
        $captcha_answer = $num1 + $num2;    
        return array($num1, $num2, $captcha_answer);    
    }    
}  
  
// Function to check email uniqueness  
function erf_check_email_uniqueness() {  
    global $wpdb;  
    $table_name = $wpdb->prefix . 'event_registrations';  
  
    if (isset($_POST['email'])) {  
        $email = sanitize_email($_POST['email']);  
        $existing_email = $wpdb->get_var($wpdb->prepare("SELECT email FROM $table_name WHERE email = %s", $email));  
  
        if ($existing_email) {  
            echo json_encode(array('unique' => false));  
        } else {  
            echo json_encode(array('unique' => true));  
        }  
    }  
    wp_die();  
}  
add_action('wp_ajax_check_email_uniqueness', 'erf_check_email_uniqueness');  
add_action('wp_ajax_nopriv_check_email_uniqueness', 'erf_check_email_uniqueness');  