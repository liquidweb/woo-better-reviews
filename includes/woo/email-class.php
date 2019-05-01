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


// Don't load without direct.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
	 * Customer note.
	 *
	 * @var string
	 */
	public $customer_note;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Set up our email placeholders.
		$set_placeholders   = array(
			'{site_title}'    => $this->get_blogname(),
			'{order_date}'    => '',
			'{order_number}'  => '',
			'{purchase_list}' => '',
		);

		// Set our plugin as the template base for loading files.
		$this->template_base  = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_base', Core\TEMPLATE_PATH . '/emails/' );

		// Set the remainder of the arguments for the email.
		$this->id             = 'customer_review_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Review Reminder', 'woo-better-reviews' );
		$this->description    = $this->get_setup_description();
		$this->template_html  = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_html', 'customer-review-reminder-html.php' );
		$this->template_plain = apply_filters( Core\HOOK_PREFIX . 'reminder_email_template_plain', 'customer-review-reminder-plain.php' );
		$this->placeholders   = apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_placeholders', $set_placeholders );

		// Triggers.
		// add_action( 'woocommerce_new_customer_note_notification', array( $this, 'trigger' ) );

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
		$setup_desc = sprintf( __( 'A reminder sent to customers to leave a review on a recent purchase. <a href="%s">%s</a> for additional settings.', 'woo-better-reviews' ), esc_url( $tab_link ), 'Click Here' );

		// __( 'A reminder sent to customers to leave a review on a recent purchase.', 'woo-better-reviews' )
		// Return our string, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_setup_description', $setup_desc );
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {

		// Set my email subject line.
		$email_subject  = __( 'Leave a review for your recent purchases from {site_title}!', 'woo-better-reviews' );

		// Return our string, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_subject', $email_subject );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {

		// Set my email heading line.
		$email_heading  = __( 'Your purchases from {order_date} are eligible for review.', 'woo-better-reviews' );

		// Return our string, filtered.
		return apply_filters( Core\HOOK_PREFIX . 'reminder_email_content_heading', $email_heading );
	}

	/**
	 * Trigger.
	 *
	 * @param array $args Email arguments.
	 */
	public function trigger( $args ) {
		$this->setup_locale();

		if ( ! empty( $args ) ) {
			$defaults = array(
				'order_id'      => '',
				'customer_note' => '',
			);

			$args = wp_parse_args( $args, $defaults );

			$order_id      = $args['order_id'];
			$customer_note = $args['customer_note'];

			if ( $order_id ) {
				$this->object = wc_get_order( $order_id );

				if ( $this->object ) {
					$this->recipient                      = $this->object->get_billing_email();
					$this->customer_note                  = $customer_note;
					$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
					$this->placeholders['{order_number}'] = $this->object->get_order_number();
				}
			}
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html, array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'customer_note' => $this->customer_note,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			)
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain, array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'customer_note' => $this->customer_note,
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			)
		);
	}

	/**
	 * Email type options.
	 *
	 * @return array
	 */
	public function get_email_type_options() {
		$types = array( 'plain' => __( 'Plain text', 'woo-better-reviews' ) );

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html'] = __( 'HTML', 'woo-better-reviews' );
		}

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
