<?php  

function erf_add_admin_menu() {
    add_menu_page('Event Registrations', 'Event Registrations', 'manage_options', 'event_registrations', 'erf_view_registrations', 'dashicons-list-view', 6);
    add_submenu_page('event_registrations', 'Email Templates', 'Email Templates', 'manage_options', 'event_email_templates', 'erf_email_templates_page');
    add_submenu_page('event_registrations', 'Event Pricing', 'Event Pricing', 'manage_options', 'event_pricing', 'erf_event_pricing_page');
    add_submenu_page('event_registrations', 'Event Form Templates', 'Event Form Templates', 'manage_options', 'event-form', 'erf_event_form_page');
    add_submenu_page('event_registrations', 'E-Ticket Templates', 'E-Ticket Templates', 'manage_options', 'eticket-templates', 'erf_eticket_template_page');
    add_submenu_page('event_registrations', 'Export Data', 'Export Data', 'manage_options', 'event_export_data', 'erf_export_data_page');
}
add_action('admin_menu', 'erf_add_admin_menu');

function erf_export_data_page() {
    ?>
    <div class="wrap">
        <h1>Export Event Registrations</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="erf_export_data">
            <input type="submit" class="button-primary" value="Export to CSV">
          
        </form>
    </div>
    <?php
}

function erf_export_data() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Include the export function
    require_once(plugin_dir_path(__FILE__) . 'export.php');
}
add_action('admin_post_erf_export_data', 'erf_export_data');

function erf_view_registrations() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_registrations';

if (isset($_POST['update_status'])) {
    $email = sanitize_email($_POST['email']);
    $new_status = sanitize_text_field($_POST['status']);

    // Fetch the current registration
    $registration = $wpdb->get_row("SELECT * FROM $table_name WHERE email = '$email'");

    // Handle status update
    if ($new_status === 'confirmed') {
        // Only generate Participant ID and QR Code if they don't already exist
        if (empty($registration->participant_id)) {
            $participant_id = uniqid('PID_');
            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($participant_id);
            $wpdb->update(
                $table_name,
                array(
                    'status' => $new_status,
                    'participant_id' => $participant_id,
                    'qr_code_url' => $qr_code_url,
                ),
                array('email' => $email)
            );
        } else {
            // If already exists, just update the status
            $wpdb->update(
                $table_name,
                array('status' => $new_status),
                array('email' => $email)
            );
        }

        // Send confirmation email notification after updating status
        send_registration_email(
            $registration->participant_title,
            $registration->name,
            $registration->nik,
            $registration->institution,
            $registration->address,
            $registration->city,
            $registration->country,
            $registration->phone,
            $registration->email,
            $registration->ticket_name,
            $registration->ticket_price,
            $registration->payment_method,
            $registration->participant_type,
            $registration->sponsor_name,
            $registration->sponsor_email,
            $registration->sponsor_phone,
            $registration->tax,
            $registration->total_invoice,
            $registration->event_type,
            'confirmation'
        );
    } elseif ($new_status === 'eticket') {
        // Send E-Ticket email
        send_registration_email(
            $registration->participant_title,
            $registration->name,
            $registration->nik,
            $registration->institution,
            $registration->address,
            $registration->city,
            $registration->country,
            $registration->phone,
            $registration->email,
            $registration->ticket_name,
            $registration->ticket_price,
            $registration->payment_method,
            $registration->participant_type,
            $registration->sponsor_name,
            $registration->sponsor_email,
            $registration->sponsor_phone,
            $registration->tax,
            $registration->total_invoice,
            $registration->event_type,
            
            'e_ticket'
        );

        // Update status to 'eticket' after sending E-Ticket
        $wpdb->update(
            $table_name,
            array('status' => 'eticket'), // Set status to 'eticket'
            array('email' => $email)
        );
    } else {
        $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('email' => $email)
        );
    }
}

    // Fetch registrations
    $registrations = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h1>Event Registrations</h1>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    echo '<th>Title</th>';
    echo '<th>Name</th>';
    echo '<th>NIK</th>';
    echo '<th>Institution</th>';
    echo '<th>Address</th>';
    echo '<th>City</th>';
    echo '<th>Country</th>';
    echo '<th>Phone</th>';
    echo '<th>Email</th>';
    echo '<th>Participant Type</th>';
    echo '<th>Ticket Name</th>';
    echo '<th>Ticket Price</th>';
    echo '<th>Tax</th>';
    echo '<th>Total Invoice</th>';
    echo '<th>Payment Method</th>';
    echo '<th>Status</th>';
    echo '<th>Event Type</th>';
    echo '<th>Sponsor Name</th>';
    echo '<th>Sponsor Email</th>';
    echo '<th>Sponsor Phone</th>';
    echo '<th>Submitted At</th>';
    echo '<th>Participant ID</th>';
    echo '<th>QR Code</th>';
    echo '<th>INV-PDF</th>'; // New column for invoice PDF download
    echo '<th>Action</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    if ($registrations) {
        foreach ($registrations as $registration) {
            echo '<tr>';
            echo '<td>' . esc_html($registration->participant_title) . '</td>';
            echo '<td>' . esc_html($registration->name) . '</td>';
            echo '<td>' . esc_html($registration->nik) . '</td>';
            echo '<td>' . esc_html($registration->institution) . '</td>';
            echo '<td>' . esc_html($registration->address) . '</td>';
            echo '<td>' . esc_html($registration->city) . '</td>';
            echo '<td>' . esc_html($registration->country) . '</td>';
            echo '<td>' . esc_html($registration->phone) . '</td>';
            echo '<td>' . esc_html($registration->email) . '</td>';
            echo '<td>' . esc_html($registration->participant_type) . '</td>';
            echo '<td>' . esc_html($registration->ticket_name) . '</td>';
            echo '<td>' . esc_html(number_format((float)$registration->ticket_price)) . '</td>';
            echo '<td>' . esc_html(number_format((float)$registration->tax)) . '</td>';
            echo '<td>' . esc_html(number_format((float)$registration->total_invoice)) . '</td>';
            echo '<td>' . esc_html($registration->payment_method) . '</td>';
            echo '<td>' . esc_html($registration->status) . '</td>';
            echo '<td>' . esc_html($registration->event_type) . '</td>';
            echo '<td>' . esc_html($registration->sponsor_name) . '</td>';
            echo '<td>' . esc_html($registration->sponsor_email) . '</td>';
            echo '<td>' . esc_html($registration->sponsor_phone) . '</td>';
            echo '<td>' . esc_html($registration->submitted_at) . '</td>';
            echo '<td>' . esc_html($registration->participant_id) . '</td>';
            echo '<td><img src="' . esc_url($registration->qr_code_url) . '" alt="QR Code" width="100"></td>';
            
            // Add the INV-PDF download link
            $pdf_file_path = plugin_dir_path(__FILE__) . '../invoices/invoice_' . sanitize_file_name($registration->email) . '.pdf';
            if (file_exists($pdf_file_path)) {
                $pdf_url = plugin_dir_url(__FILE__) . '../invoices/invoice_' . sanitize_file_name($registration->email) . '.pdf';
                echo '<td><a href="' . esc_url($pdf_url) . '" download>Download PDF</a></td>';
            } else {
                echo '<td>No PDF</td>';
            }

            echo '<td>';
            echo '<form method="POST" style="display:inline;">';
            echo '<input type="hidden" name="email" value="' . esc_attr($registration->email) . '">';
            echo '<select name="status">';
            echo '<option value="pending" ' . selected($registration->status, 'pending', false) . '>Pending</option>';
            echo '<option value="confirmed" ' . selected($registration->status, 'confirmed', false) . '>Confirmed</option>';
            echo '<option value="eticket" ' . selected($registration->status, 'eticket', false) . '>Send E-Ticket</option>'; // Added E-Ticket status
            echo '</select>';
            echo '<button type="submit" name="update_status">Update</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="21">No registrations found.</td></tr>'; // Adjust colspan to match the number of columns
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Include the invoice template editor
require_once plugin_dir_path(__FILE__) . 'invoice-template-editor.php';

// Include the E-Ticket template editor
require_once plugin_dir_path(__FILE__) . 'eticket-template-editor.php';
function generate_eticket_pdf($registration) {
    require_once plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, -10);
    $pdf->AddPage();

    // Get E-TICKET specific header/footer
    $header_image_url = get_option('erf_eticket_header_image', '');
    $footer_image_url = get_option('erf_eticket_footer_image', '');

    $html = '';

    // E-Ticket Header
    if (!empty($header_image_url)) {
        $html .= '<div style="text-align: center; margin-bottom: 20px;">';
        $html .= '<img src="' . esc_url($header_image_url) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';
    }

    // Main Content
    $template = get_option('erf_eticket_template_' . $registration->event_type, '');
    $html .= parse_eticket_template($template, $registration);

    // E-Ticket Footer
    if (!empty($footer_image_url)) {
        $html .= '<div style="text-align: center; margin-top: 20px;">';
        $html .= '<img src="' . esc_url($footer_image_url) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';
    }

    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

    // Save the PDF
    $etickets_dir = plugin_dir_path(__FILE__) . '../etickets/';
    if (!file_exists($etickets_dir)) {
        wp_mkdir_p($etickets_dir);
    }
    
    $sanitized_email = sanitize_file_name($registration->email);
    $pdf_file_path = $etickets_dir . 'eticket_' . $sanitized_email . '.pdf';
    $pdf->Output($pdf_file_path, 'F');

    return $pdf_file_path;
}

function generate_pdf_invoice($registration) {
    require_once plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php';

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document margins
    $pdf->SetMargins(10, 10, 10); // Left, Top, Right margins in mm
    $pdf->SetAutoPageBreak(true, -10); // Bottom margin in mm

    // Add a page
    $pdf->AddPage();

    // Get the header and footer image URLs from the options
    $header_image_url = get_option('erf_invoice_header_image', '');
    $footer_image_url = get_option('erf_invoice_footer_image', ''); // Get footer image URL

    // Start building the HTML content
    $html = '';

    // Add the header image if it exists
    if (!empty($header_image_url)) {
        $html .= '<div style="text-align: center; padding-bottom: -20px;">'; // Add margin-bottom for spacing
        $html .= '<img src="' . esc_url($header_image_url) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';
    }

    // Get the invoice template from the options
    $template = get_option('erf_invoice_template', '<h1>Invoice</h1><p>Name: {name}</p><p>Email: {email}</p>');

    // Parse the template with the registration data
    $html .= parse_invoice_template($template, $registration);

    // Add the footer image if it exists
    if (!empty($footer_image_url)) {
        $html .= '<div style="text-align: center; padding-top: -20px;">';
        $html .= '<img src="' . esc_url($footer_image_url) . '" style="max-width: 100%; height: auto;">';
        $html .= '</div>';
    }

    // Print text using writeHTMLCell()
    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

    // Ensure the invoices directory exists
    $invoices_dir = plugin_dir_path(__FILE__) . '../invoices/';
    if (!file_exists($invoices_dir)) {
        wp_mkdir_p($invoices_dir);
    }

    // Sanitize the email for the file name
    $sanitized_email = sanitize_file_name($registration->email);
    $pdf_file_path = $invoices_dir . 'invoice_' . $sanitized_email . '.pdf';

    // Save the PDF file
    $pdf->Output($pdf_file_path, 'F');

    return $pdf_file_path;
}

function parse_invoice_template($template, $registration) {
    // Replace placeholders with actual data
    $html = str_replace(
        array(
            '{id}',
            '{submitted_at}',
            '{participant_title}',
            '{name}',
            '{institution}',
            '{address}',
            '{city}',
            '{country}',
            '{phone}',
            '{email}',
            '{ticket_name}',
            '{ticket_price}',
            '{sponsor_name}',
            '{sponsor_phone}',
            '{sponsor_email}',
            '{tax}',
            '{total_invoice}',
            '{payment_method}',
            '{participant_type}' // Add this line
        ),
        array(
            esc_html($registration->id),
            esc_html($registration->submitted_at),
            esc_html($registration->participant_title),
            esc_html($registration->name),
            esc_html($registration->institution),
            esc_html($registration->address),
            esc_html($registration->city),
            esc_html($registration->country),
            esc_html($registration->phone),
            esc_html($registration->email),
            esc_html($registration->ticket_name),
            esc_html(number_format((float)$registration->ticket_price, 0, ',', '.')),
            esc_html($registration->sponsor_name ?? ''),
            esc_html($registration->sponsor_phone ?? ''),
            esc_html($registration->sponsor_email ?? ''),
            esc_html(number_format((float)$registration->tax, 0, ',', '.') ?? ''),
            esc_html(number_format((float)$registration->total_invoice, 0, ',', '.')),
            esc_html($registration->payment_method),
            esc_html($registration->participant_type) // Add this line
        ),
        $template
    );

    // Get custom values for payment method conditions
    $payment_bank_transfer_text = get_option('erf_payment_bank_transfer_text', 'abc');
    $payment_credit_card_400_url = get_option('erf_payment_credit_card_400_url', 'http://iddw400.com');
    $payment_credit_card_700_url = get_option('erf_payment_credit_card_700_url', 'http://iddw700.com');
    $payment_credit_card_1100_url = get_option('erf_payment_credit_card_1100_url', 'http://iddw1100.com');

    // Conditional logic for {payment_method}
    if (strpos($html, '{if payment_method}') !== false) {
        // Handle Bank Transfer
        if ($registration->payment_method === 'Bank Transfer') {
            $html = preg_replace('/{if payment_method}Bank Transfer{\/if}/', $payment_bank_transfer_text, $html);
        } else {
            $html = preg_replace('/{if payment_method}Bank Transfer{\/if}/', '', $html);
        }

        // Handle Credit Card
        if ($registration->payment_method === 'Credit Card') {
            if ($registration->total_invoice == 400) {
                $html = preg_replace('/{if payment_method}Credit Card{\/if}/', $payment_credit_card_400_url, $html);
            } elseif ($registration->total_invoice == 700) {
                $html = preg_replace('/{if payment_method}Credit Card{\/if}/', $payment_credit_card_700_url, $html);
            } elseif ($registration->total_invoice == 1100) {
                $html = preg_replace('/{if payment_method}Credit Card{\/if}/', $payment_credit_card_1100_url, $html);
            } else {
                $html = preg_replace('/{if payment_method}Credit Card{\/if}/', '', $html);
            }
        } else {
            $html = preg_replace('/{if payment_method}Credit Card{\/if}/', '', $html);
        }
    }
function erf_preview_pdf() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Create dummy registration data
    $dummy_registration = (object) [
        'id' => '123',
        'submitted_at' => date('Y-m-d H:i:s'),
        'participant_title' => 'Mr.',
        'name' => 'John Doe',
        'institution' => 'Example Inc.',
        'address' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
        'phone' => '+1 123 456 7890',
        'email' => 'john.doe@example.com',
        'ticket_name' => 'Standard Ticket',
        'ticket_price' => 100,
        'sponsor_name' => 'Sponsor Inc.',
        'sponsor_phone' => '+1 987 654 3210',
        'sponsor_email' => 'sponsor@example.com',
        'tax' => 10,
        'total_invoice' => 110,
        'payment_method' => 'Bank Transfer',
        'participant_type' => 'Speaker'
    ];

    // Generate the PDF
    $pdf_file_path = generate_pdf_invoice($dummy_registration);

    // Output the PDF for preview
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="preview.pdf"');
    readfile($pdf_file_path);
    exit;
}

add_action('admin_post_erf_preview_pdf', 'erf_preview_pdf');

    // Remove {if} blocks for empty fields
    $html = preg_replace('/{if sponsor_name}(.*?){\/if}/s', !empty($registration->sponsor_name) ? '$1' : '', $html);
    $html = preg_replace('/{if sponsor_phone}(.*?){\/if}/s', !empty($registration->sponsor_phone) ? '$1' : '', $html);
    $html = preg_replace('/{if sponsor_email}(.*?){\/if}/s', !empty($registration->sponsor_email) ? '$1' : '', $html);
    $html = preg_replace('/{if tax}(.*?){\/if}/s', !empty($registration->tax) ? '$1' : '', $html);

    return $html;
}
