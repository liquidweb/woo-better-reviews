<?php
/**
 * Class WC_Email_Customer_Review_Reminder file.
 *
 * read: https://www.skyverge.com/blog/how-to-add-a-custom-woocommerce-email/
 *
 * @package WooCommerce\Emails
 */

// Set our aliases.
use Nexcess\WooBetterReviews as Core;
use Nexcess\WooBetterReviews\Helpers as Helpers;
use Nexcess\WooBetterReviews\Queries as Queries;

// Don't load without direct.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load in the WC_Email class file if need be.
if ( ! class_exists( 'WC_Email' ) ) {
	require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

/**
 * Customer Review Reminder.
 *
 * An email sent to the customer when they have purchased something.
 *
 * @class       WC_Email_Customer_Review_Reminder
 * @extends     WC_Email
 */
class WC_Email_Customer_Review_Reminder extends WC_Email {

	/**
	 * The review reminder.
	 *
	 * @var string
	 */
	public $email_reminder;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Set up our email placeholders.
		$set_placeholders   = array(
			'{site_title}'     => $this->get_blogname(),
			'{order_date}'     => '',
			'{order_number}'   => '',
			'{customer_name}'  => '',
			'{customer_first}' => '',
			'{customer_last}'  => '',
		);

		// Set the content items for the email class.
		$this->id             = 'customer_review_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Review Reminder', 'woo-better-reviews' );
		$this->description    = $this->get_setup_description();
		$this->placeholders   = apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_placeholders', $set_placeholders );

		// Pull in our default subject and heading strings.
		$this->heading        = $this->format_string( $this->get_default_heading() );
		$this->subject        = $this->format_string( $this->get_default_subject() );

		// Load up the template base and the individual files.
		$this->template_html  = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_html', 'customer-review-reminder-html.php' );
		$this->template_plain = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_plain', 'customer-review-reminder-plain.php' );
		$this->template_base  = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_base', Core\TEMPLATE_PATH . '/emails/' );

		// Set this to "manual" since the trigger is called via the cron job.
		$this->manual = true;

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return Helpers\maybe_reminders_enabled();
	}

	/**
	 * Set our description with a settings link.
	 *
	 * @return mixed
	 */
	public function get_setup_description() {

		// Get our main settings link.
		$tab_link   = Helpers\get_admin_tab_link();

		// Set the description text.
		$setup_desc = sprintf( __( 'A reminder sent to customers to leave a review on a recent purchase. <a href="%s">Click here</a> for additional settings.', 'woo-better-reviews' ), esc_url( $tab_link ) );

		// Return our string, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_setup_description', $setup_desc );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {

		// Set my email heading line.
		$email_heading  = __( 'Your purchases from {order_date} are eligible for review.', 'woo-better-reviews' );

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_heading', $email_heading );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {

		// Set my email subject line.
		$email_subject  = __( 'Leave a review for your recent purchases from {site_title}!', 'woo-better-reviews' );

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_subject', $email_subject );
	}

	/**
	 * Prepare and send the reminder via the cron job.
	 *
	 * @param array
	 */
	public function send_reminder( $email_reminder = false ) {

		// Bail if it's empty or not an array.
		if ( empty( $email_reminder ) || ! is_array( $email_reminder ) ) {
			return;
		}

		// Send the data to the trigger.
		$this->trigger( $email_reminder );
	}

	/**
	 * Our actual trigger function, which begins the email sending.
	 *
	 * @param array $passed_args  The array of required email arguments passed via the cron.
	 */
	public function trigger( $passed_args ) {

		// Bail right away without any args to process.
		if ( empty( $passed_args ) || ! is_array( $passed_args ) ) {
			return false;
		}

		// Call the locale for emails.
		$this->setup_locale();

		// Set the default args.
		$default_args   = array(
			'order_id'  => '',
			'customer'  => '',
			'products'  => '',
		);

		// Filter in what we have with what was passed.
		$setup_args     = wp_parse_args( $passed_args, $default_args );

		// Bail if any piece or section is missing.
		if ( empty( $setup_args['order_id'] ) || empty( $setup_args['customer'] ) || empty( $setup_args['products'] ) ) {
			return false;
		}

		// Set each part of our data as needed.
		$customer_data  = $setup_args['customer'];
		$product_list   = is_array( $setup_args['products'] ) ? array_keys( $setup_args['products'] ) : false;

		// Pull and  sanitize the order ID.
		$order_id       = absint( $setup_args['order_id'] );

		// Pull out and set the object.
		$this->object = wc_get_order( $order_id );

		// Bail if we don't have the resulting object.
		if ( ! $this->object ) {
			return false;
		}

		// Do some checks for things.
		$recipient  = ! empty( $customer_data['email'] ) ? $customer_data['email'] : $this->object->get_billing_email();

		// Set up the secondary parts of the object.
		$this->recipient = $recipient;
		$this->customer  = $customer_data;
		$this->products  = $product_list;
		$this->order_id  = $order_id;

		// Set the rest of the placeholders.
		$this->placeholders['{order_date}']     = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}']   = $this->object->get_order_number();
		$this->placeholders['{customer_name}']  = $customer_data['name'];
		$this->placeholders['{customer_first}'] = $customer_data['first'];
		$this->placeholders['{customer_last}']  = $customer_data['last'];

		// Bail out if we don't have these two.
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return false;
		}

		// Attempt to send the email.
		$attempt_email  = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		// Go back to whence we came.
		$this->restore_locale();

		// Return a boolean based on the return status.
		return false !== $attempt_email ? true : false;
	}

	/**
	 * Set up and return our filtered email headers.
	 *
	 * @return string
	 */
	public function get_headers() {

		// Add the content type automatically.
		$headers[] = 'Content-Type: ' . $this->get_content_type() . "\r\n";

		// Set the name and email from.
		$headers[] = 'To: ' . esc_attr( $this->customer['name'] ) . ' <' . sanitize_email( $this->get_recipient() ) . '>';
		$headers[] = 'From: ' . $this->get_from_name() . ' <' . $this->get_from_address() . '>';
		$headers[] = 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . '>';

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_headers', $headers, $this->object );
	}

	/**
	 * Set up and return our filtered HTML formatted content.
	 *
	 * @return HTML
	 */
	public function get_content_html() {

		// Set up the base args for the HTML email.
		$base_args  = array(
			'order'          => $this->object,
			'email_heading'  => $this->get_heading(),
			'recipient'      => $this->recipient,
			'sections'       => $this->build_email_body_sections(),
			'sent_to_admin'  => false,
			'plain_text'     => false,
			'email'          => $this,
		);

		// Filter the args.
		$html_args  = apply_filters( Core\HOOK_PREFIX . 'reminder_email_html_args', $base_args );

		// Return the template HTML using our args.
		return wc_get_template_html( $this->template_html, $html_args );
	}

	/**
	 * Set up and return our filtered plain text formatted content.
	 *
	 * @return string
	 */
	public function get_content_plain() {

		// Set up the base args for the plain text email.
		$base_args  = array(
			'order'          => $this->object,
			'email_heading'  => $this->get_heading(),
			'recipient'      => $this->recipient,
			'sections'       => $this->build_email_body_sections(),
			'sent_to_admin'  => false,
			'plain_text'     => true,
			'email'          => $this,
		);

		// Filter the args.
		$plain_args = apply_filters( Core\HOOK_PREFIX . 'reminder_email_plain_args', $base_args );

		// Return the template plain text using our args.
		return wc_get_template_html( $this->template_plain, $plain_args );
	}

	/**
	 * Build out the body of the email.
	 *
	 * @return HTML
	 */
	public function build_email_body_sections() {

		// Set an array of the sections.
		$body_args = array(
			'introduction' => $this->get_content_body_introduction( $this->customer ),
			'product_list' => $this->products,
			'closing'      => $this->get_content_body_closing( $this->customer ),
		);

		// Filter all the known strings.
		$body_args = array_map( array( $this, 'format_string' ), $body_args );

		// Return the filtered array.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_html_content_body_args', $body_args, $this->order_id );
	}

	/**
	 * Set up the intro text for the email.
	 *
	 * @param  array $customer_data  The customer data we have.
	 *
	 * @return HTML
	 */
	public function get_content_body_introduction( $customer_data = array() ) {

		// Bail without customer data.
		if ( empty( $customer_data ) ) {
			return;
		}

		// Set up the intro data.
		$email_content  = __( 'Hello {customer_first}! Recently, you made a purchase at our store on {order_date} and we would appreciate it if you could leave a review!', 'woo-better-reviews' );

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_body_introduction', $email_content, $customer_data, $this->order_id );
	}

	/**
	 * Set up the closing text for the email.
	 *
	 * @param  array $customer_data  The customer data we have.
	 *
	 * @return HTML
	 */
	public function get_content_body_closing( $customer_data = array() ) {

		// Bail without customer data.
		if ( empty( $customer_data ) ) {
			return;
		}

		// Set up the initial data.
		$email_content  = __( 'Thanks again for everyone here at {site_title}!', 'woo-better-reviews' );

		// Add the account link if it's a WP user.
		if ( false !== $customer_data['is-wp'] ) {

			// Get the profile link.
			$profile_link   = trailingslashit( wc_get_account_endpoint_url( '' ) );

			// Switch through the two available types.
			switch ( esc_attr( $this->email_type ) ) {

				// Set the HTML version.
				case 'html' :

					// Add the content.
					$email_content .= sprintf( __( ' <a href="%s">Click here to view your account profile</a>.', 'woo-better-reviews' ), esc_url( $profile_link ) );

					// And break.
					break;

				// Set the plain text version.
				case 'plain' :

					// Add the content.
					$email_content .= sprintf( __( ' Click here to view your account profile:  %s', 'woo-better-reviews' ), esc_url( $profile_link ) );

					// And break.
					break;
			}

			// Nothing else.
		}

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_body_closing', $email_content, $customer_data, $this->order_id );
	}

	/**
	 * Email type options.
	 *
	 * @return array
	 */
	public function get_email_type_options() {

		// Set the plain text.
		$types = array( 'plain' => __( 'Plain text', 'woo-better-reviews' ) );

		// Add HTML if we have DOMDoc to work with.
		if ( class_exists( 'DOMDocument' ) ) {
			$types['html'] = __( 'HTML', 'woo-better-reviews' );
		}

		// Return the resulting array.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_option_types', $types );
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 0.1
	 */
	public function init_form_fields() {

		// Set our new array of fields.
		$settings_args  = array(

			'subject'    => array(
				'title'       => __( 'Email Subject', 'woo-better-reviews' ),
				'type'        => 'text',
				'desc_tip'    => true,
				// translators: %s: list of placeholders
				'description' => sprintf( __( 'Available placeholders: %s', 'woo-better-reviews' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),

			'heading' => array(
				'title'       => __( 'Email Heading', 'woo-better-reviews' ),
				'type'        => 'text',
				'desc_tip'    => true,
				// translators: %s: list of placeholders
				'description' => sprintf( __( 'Available placeholders: %s', 'woo-better-reviews' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),

			'email_type' => array(
				'title'       => __( 'Email Type', 'woo-better-reviews' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woo-better-reviews' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);

		// Now set the actual fields with a filter.
		$this->form_fields = apply_filters( Core\HOOK_PREFIX . 'reminder_email_admin_settings', $settings_args );
	}

	// We are done with this class. For now.
}

// Return the class.
return new WC_Email_Customer_Review_Reminder();
