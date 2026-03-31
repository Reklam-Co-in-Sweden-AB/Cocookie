<?php
/**
 * Policies controller.
 *
 * Handles privacy and cookie policy generation.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Policies_Controller {

	/**
	 * Render the policies page.
	 */
	public static function render() {
		self::handle_actions();

		$company_info    = CCM_Policy_Generator::get_company_info();
		$privacy_content = CCM_Policy_Generator::generate_privacy_policy();
		$cookie_content  = CCM_Policy_Generator::generate_cookie_policy();

		$data = array(
			'company_info'    => $company_info,
			'privacy_content' => $privacy_content,
			'cookie_content'  => $cookie_content,
		);

		include COCOOKIE_PLUGIN_DIR . 'admin/views/new/policies.php';
	}

	/**
	 * Handle form submissions.
	 */
	private static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save company info
		if ( isset( $_POST['cocookie_save_company'] ) && check_admin_referer( 'cocookie_policy_company' ) ) {
			CCM_Policy_Generator::save_company_info( $_POST );
			wp_redirect( admin_url( 'admin.php?page=cocookie-policies&msg=saved' ) );
			exit;
		}

		// Create/update policy page
		if ( isset( $_POST['cocookie_create_policy'] ) && check_admin_referer( 'cocookie_policy_create' ) ) {
			$type    = sanitize_text_field( $_POST['policy_type'] ?? '' );
			$page_id = CCM_Policy_Generator::create_page( $type );
			$msg     = is_wp_error( $page_id ) ? 'error' : 'created';
			wp_redirect( admin_url( 'admin.php?page=cocookie-policies&msg=' . $msg ) );
			exit;
		}
	}
}
