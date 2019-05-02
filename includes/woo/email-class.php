<?php
/**
 * Class WC_Email_Customer_Review_Reminder file.
 *
 * read: https://www.skyverge.com/blog/how-to-add-a-custom-woocommerce-email/
 *
 * @package WooCommerce\Emails
 */

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Queries as Queries;

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
			'{site_title}'    => $this->get_blogname(),
			'{order_date}'    => '',
			'{order_number}'  => '',
		);

		// Set my email heading and subject lines.
		// $email_heading  = __( 'Your purchases from {order_date} are eligible for review.', 'woo-better-reviews' );
		// $email_subject  = __( 'Leave a review for your recent purchases from {site_title}!', 'woo-better-reviews' );

		// Set the content items for the email class.
		$this->id             = 'customer_review_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Review Reminder', 'woo-better-reviews' );
		$this->description    = $this->get_setup_description();
		$this->placeholders   = apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_placeholders', $set_placeholders );

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
	 * Trigger.
	 *
	 * @param array $args Email arguments.
	 */
	public function trigger( $args ) {

		// preprint( $args, true );

		// Call the locale for emails.
		$this->setup_locale();

		// Run checking with the args.
		if ( ! empty( $args ) ) {

			// Set the default args.
			$default_args   = array(
				'order_id'  => '',
				'recipient' => '',
				'content'   => '',
			);

			// Filter in what we have with what was passed.
			$setup_args     = wp_parse_args( $args, $default_args );

			// Pull out the two pieces.
			$order_id       = $setup_args['order_id'];

			// Assuming we have an order ID, do the things.
			if ( $order_id ) {

				// Pull out and set the object.
				$this->object = wc_get_order( $order_id );

				// Set the whole object.
				if ( $this->object ) {

					// Do some checks for things.
					$recipient  = ! empty( $setup_args['recipient'] ) ? $setup_args['recipient'] : $this->object->get_billing_email();

					// Set up the secondary parts of the object.
					$this->recipient = $recipient;

					// Set the content.
					if ( ! empty( $setup_args['content'] ) ) {
						$this->content = $setup_args['content'];
					}

					// Set the placeholders.
					$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
					$this->placeholders['{order_number}'] = $this->object->get_order_number();
				}
			}
		}

		// Do the thing for the thing.
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		// Go back to whence we came.
		$this->restore_locale();
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {

		// Set an empty.
		$build  = '';

		// Add the content type automatically.
		$build .= 'Content-Type: ' . $this->get_content_type() . "\r\n";

		// Set the name and email from.
		$build .= 'From: ' . $this->get_from_name() . ' <' . $this->get_from_address() . '>' . "\r\n";
		$build .= 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . '>' . "\r\n";

		// Return it filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_headers', $build, $this->object );
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {

		// Set up the base args for the HTML email.
		$base_args  = array(
			'order'          => $this->object,
			'email_heading'  => $this->get_heading(),
			'recipient'      => $this->recipient,
			'content'        => $this->format_string( $this->content ),
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
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {

		// Set up the base args for the plain text email.
		$base_args  = array(
			'order'          => $this->object,
			'recipient'      => $this->recipient,
			'content'        => wp_strip_all_tags( $this->content ),
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
		return $types;
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

		// Now set the actual fields.
		$this->form_fields = apply_filters( Core\HOOK_PREFIX . 'reminder_email_admin_settings', $settings_args );
    }
}

// Return the class.
return new WC_Email_Customer_Review_Reminder();
