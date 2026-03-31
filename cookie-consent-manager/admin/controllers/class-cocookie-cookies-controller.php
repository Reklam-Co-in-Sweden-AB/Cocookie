<?php
/**
 * Cookies controller.
 *
 * Manages categories and cookies in a single merged view.
 * Categories in sidebar, cookies in main area.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Cookies_Controller {

	/**
	 * Render the cookies page.
	 */
	public static function render() {
		self::handle_actions();

		$categories      = CoCookie_Categories::get_all();
		$selected_cat_id = isset( $_GET['cat'] ) ? intval( $_GET['cat'] ) : ( ! empty( $categories ) ? $categories[0]['id'] : 0 );
		$selected_cat    = null;

		foreach ( $categories as $cat ) {
			if ( (int) $cat['id'] === $selected_cat_id ) {
				$selected_cat = $cat;
				break;
			}
		}

		$cookies       = $selected_cat_id ? CoCookie_Categories::get_cookies( $selected_cat_id ) : array();
		$editing_cat   = isset( $_GET['edit_cat'] ) ? CoCookie_Categories::get( intval( $_GET['edit_cat'] ) ) : null;
		$editing_cookie = isset( $_GET['edit_cookie'] ) ? CoCookie_Categories::get_cookie( intval( $_GET['edit_cookie'] ) ) : null;

		$data = array(
			'categories'      => $categories,
			'selected_cat_id' => $selected_cat_id,
			'selected_cat'    => $selected_cat,
			'cookies'         => $cookies,
			'editing_cat'     => $editing_cat,
			'editing_cookie'  => $editing_cookie,
		);

		include COCOOKIE_PLUGIN_DIR . 'admin/views/new/cookies.php';
	}

	/**
	 * Handle form submissions.
	 */
	private static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save category
		if ( isset( $_POST['cocookie_save_category'] ) && check_admin_referer( 'cocookie_category_action' ) ) {
			$data = array(
				'slug'        => $_POST['slug'] ?? '',
				'title'       => $_POST['title'] ?? '',
				'description' => $_POST['description'] ?? '',
				'is_required' => isset( $_POST['is_required'] ) ? 1 : 0,
				'sort_order'  => $_POST['sort_order'] ?? 0,
			);

			if ( ! empty( $_POST['category_id'] ) ) {
				CoCookie_Categories::update( intval( $_POST['category_id'] ), $data );
			} else {
				CoCookie_Categories::create( $data );
			}

			wp_redirect( admin_url( 'admin.php?page=cocookie-cookies&msg=saved' ) );
			exit;
		}

		// Delete category
		if ( isset( $_GET['delete_cat'] ) && check_admin_referer( 'cocookie_delete_category' ) ) {
			CoCookie_Categories::delete( intval( $_GET['delete_cat'] ) );
			wp_redirect( admin_url( 'admin.php?page=cocookie-cookies&msg=deleted' ) );
			exit;
		}

		// Save cookie
		if ( isset( $_POST['cocookie_save_cookie'] ) && check_admin_referer( 'cocookie_cookie_action' ) ) {
			$data = array(
				'category_id' => $_POST['category_id'] ?? 0,
				'name'        => $_POST['name'] ?? '',
				'provider'    => $_POST['provider'] ?? '',
				'purpose'     => $_POST['purpose'] ?? '',
				'expiry'      => $_POST['expiry'] ?? '',
			);

			if ( ! empty( $_POST['cookie_id'] ) ) {
				CoCookie_Categories::update_cookie( intval( $_POST['cookie_id'] ), $data );
			} else {
				CoCookie_Categories::create_cookie( $data );
			}

			$cat = intval( $data['category_id'] );
			wp_redirect( admin_url( "admin.php?page=cocookie-cookies&cat={$cat}&msg=saved" ) );
			exit;
		}

		// Delete cookie
		if ( isset( $_GET['delete_cookie'] ) && check_admin_referer( 'cocookie_delete_cookie' ) ) {
			$cookie = CoCookie_Categories::get_cookie( intval( $_GET['delete_cookie'] ) );
			CoCookie_Categories::delete_cookie( intval( $_GET['delete_cookie'] ) );
			$cat = $cookie ? $cookie['category_id'] : '';
			wp_redirect( admin_url( "admin.php?page=cocookie-cookies&cat={$cat}&msg=deleted" ) );
			exit;
		}

		// Import cookies from JSON
		if ( isset( $_POST['cocookie_import_cookies'] ) && check_admin_referer( 'cocookie_import_cookies' ) ) {
			$msg = 'import_error';
			if ( ! empty( $_FILES['cocookie_import_file']['tmp_name'] ) ) {
				if ( $_FILES['cocookie_import_file']['size'] > 1048576 ) {
					wp_redirect( admin_url( 'admin.php?page=cocookie-cookies&msg=import_error' ) );
					exit;
				}
				$ext = strtolower( pathinfo( $_FILES['cocookie_import_file']['name'], PATHINFO_EXT ) );
				if ( 'json' !== $ext ) {
					wp_redirect( admin_url( 'admin.php?page=cocookie-cookies&msg=import_error' ) );
					exit;
				}
				$json = file_get_contents( $_FILES['cocookie_import_file']['tmp_name'] );
				$data = json_decode( $json, true );
				if ( is_array( $data ) ) {
					$categories = CoCookie_Categories::get_all();
					$slug_map   = array();
					foreach ( $categories as $cat ) {
						$slug_map[ $cat['slug'] ] = $cat['id'];
					}
					$imported = 0;
					foreach ( $data as $item ) {
						if ( empty( $item['name'] ) ) {
							continue;
						}
						$cat_id = 0;
						if ( ! empty( $item['category_slug'] ) && isset( $slug_map[ $item['category_slug'] ] ) ) {
							$cat_id = $slug_map[ $item['category_slug'] ];
						} elseif ( ! empty( $item['category_id'] ) ) {
							$cat_id = intval( $item['category_id'] );
						}
						if ( ! $cat_id ) {
							continue;
						}
						CoCookie_Categories::create_cookie( array(
							'category_id' => $cat_id,
							'name'        => $item['name'],
							'provider'    => $item['provider'] ?? '',
							'purpose'     => $item['purpose'] ?? '',
							'expiry'      => $item['expiry'] ?? '',
						) );
						$imported++;
					}
					$msg = $imported > 0 ? 'imported' : 'import_error';
				}
			}
			wp_redirect( admin_url( 'admin.php?page=cocookie-cookies&msg=' . $msg ) );
			exit;
		}

		// Export cookies
		if ( isset( $_POST['cocookie_export_cookies'] ) && check_admin_referer( 'cocookie_export_cookies' ) ) {
			$cookies    = CoCookie_Categories::get_cookies();
			$categories = CoCookie_Categories::get_all();
			$slug_map   = array();
			foreach ( $categories as $cat ) {
				$slug_map[ $cat['id'] ] = $cat['slug'];
			}

			$export = array();
			foreach ( $cookies as $c ) {
				$export[] = array(
					'name'          => $c['name'],
					'category_slug' => $slug_map[ $c['category_id'] ] ?? '',
					'provider'      => $c['provider'],
					'purpose'       => $c['purpose'],
					'expiry'        => $c['expiry'],
				);
			}

			$filename = 'cocookie-export-' . wp_date( 'Y-m-d' ) . '.json';
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			exit;
		}
	}
}
