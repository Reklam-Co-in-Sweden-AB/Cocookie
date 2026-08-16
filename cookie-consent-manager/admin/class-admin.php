<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
        add_action( 'admin_post_ccm_export_cookies', array( __CLASS__, 'handle_export' ) );
    }

    public static function add_menu() {
        add_options_page(
            'Cookie Consent',
            'Cookie Consent',
            'manage_options',
            'cookie-consent',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'settings_page_cookie-consent' ) {
            return;
        }
        wp_enqueue_style( 'ccm-admin', CCM_PLUGIN_URL . 'admin/admin.css', array(), CCM_VERSION );

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
        if ( $tab === 'settings' ) {
            wp_enqueue_media();
        }
        if ( $tab === 'audit' ) {
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_script( 'ccm-audit', CCM_PLUGIN_URL . 'admin/audit.js', array(), CCM_VERSION, true );
            wp_localize_script( 'ccm-audit', 'ccmAudit', array(
                'restUrl' => esc_url_raw( rest_url() ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'siteUrl' => esc_url( home_url( '/' ) ),
            ) );
        }
        if ( $tab === 'scanner' ) {
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_script( 'ccm-scanner', CCM_PLUGIN_URL . 'admin/scanner.js', array(), CCM_VERSION, true );
            wp_localize_script( 'ccm-scanner', 'ccmScanner', array(
                'restUrl'        => esc_url_raw( rest_url() ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'siteUrl'        => esc_url( home_url( '/' ) ),
                'cleanScanNonce' => wp_create_nonce( 'ccm_clean_scan' ),
            ) );
        }
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'categories';
        $tabs = array(
            'categories' => 'Kategorier',
            'cookies'    => 'Cookies',
            'consents'   => 'Samtyckes-logg',
            'statistics' => 'Statistik',
            'scanner'    => 'Scanner',
            'audit'      => 'Cookie-audit',
            'policies'   => 'Policyer',
            'settings'   => 'Inställningar',
        );

        echo '<div class="wrap">';
        echo '<h1>Cookie Consent Manager</h1>';
        echo '<nav class="nav-tab-wrapper">';
        foreach ( $tabs as $slug => $label ) {
            $active = ( $tab === $slug ) ? ' nav-tab-active' : '';
            $url    = admin_url( 'options-general.php?page=cookie-consent&tab=' . $slug );
            echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $active . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
        echo '<div class="ccm-tab-content">';

        switch ( $tab ) {
            case 'cookies':
                include CCM_PLUGIN_DIR . 'admin/views/cookies.php';
                break;
            case 'consents':
                include CCM_PLUGIN_DIR . 'admin/views/consents.php';
                break;
            case 'statistics':
                include CCM_PLUGIN_DIR . 'admin/views/statistics.php';
                break;
            case 'scanner':
                include CCM_PLUGIN_DIR . 'admin/views/scanner.php';
                break;
            case 'audit':
                include CCM_PLUGIN_DIR . 'admin/views/audit.php';
                break;
            case 'policies':
                include CCM_PLUGIN_DIR . 'admin/views/policies.php';
                break;
            case 'settings':
                self::render_settings();
                break;
            default:
                include CCM_PLUGIN_DIR . 'admin/views/categories.php';
                break;
        }

        echo '</div></div>';
    }

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Category actions
        if ( isset( $_POST['ccm_save_category'] ) && check_admin_referer( 'ccm_category_action' ) ) {
            $data = array(
                'slug'        => $_POST['slug'] ?? '',
                'title'       => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'is_required' => isset( $_POST['is_required'] ) ? 1 : 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            );

            if ( ! empty( $_POST['category_id'] ) ) {
                CCM_Categories::update( intval( $_POST['category_id'] ), $data );
            } else {
                CCM_Categories::create( $data );
            }

            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=categories&msg=saved' ) );
            exit;
        }

        if ( isset( $_GET['ccm_delete_category'] ) && check_admin_referer( 'ccm_delete_category' ) ) {
            CCM_Categories::delete( intval( $_GET['ccm_delete_category'] ) );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=categories&msg=deleted' ) );
            exit;
        }

        // Cookie actions
        if ( isset( $_POST['ccm_save_cookie'] ) && check_admin_referer( 'ccm_cookie_action' ) ) {
            $data = array(
                'category_id' => $_POST['category_id'] ?? 0,
                'name'        => $_POST['name'] ?? '',
                'provider'    => $_POST['provider'] ?? '',
                'purpose'     => $_POST['purpose'] ?? '',
                'expiry'      => $_POST['expiry'] ?? '',
            );

            if ( ! empty( $_POST['cookie_id'] ) ) {
                CCM_Categories::update_cookie( intval( $_POST['cookie_id'] ), $data );
            } else {
                CCM_Categories::create_cookie( $data );
            }

            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&msg=saved' ) );
            exit;
        }

        if ( isset( $_GET['ccm_delete_cookie'] ) && check_admin_referer( 'ccm_delete_cookie' ) ) {
            CCM_Categories::delete_cookie( intval( $_GET['ccm_delete_cookie'] ) );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&msg=deleted' ) );
            exit;
        }

        // Cookie import
        if ( isset( $_POST['ccm_import_cookies'] ) && check_admin_referer( 'ccm_import_cookies' ) ) {
            $msg = 'import_error';
            if ( ! empty( $_FILES['ccm_import_file']['tmp_name'] ) ) {
                // Validate file size (max 1MB)
                if ( $_FILES['ccm_import_file']['size'] > 1048576 ) {
                    wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&msg=import_error' ) );
                    exit;
                }
                // Validate file extension
                $ext = strtolower( pathinfo( $_FILES['ccm_import_file']['name'], PATHINFO_EXTENSION ) );
                if ( $ext !== 'json' ) {
                    wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&msg=import_error' ) );
                    exit;
                }
                $json = file_get_contents( $_FILES['ccm_import_file']['tmp_name'] );
                $data = json_decode( $json, true );
                if ( is_array( $data ) ) {
                    $categories = CCM_Categories::get_all();
                    $slug_map   = array();
                    foreach ( $categories as $cat ) {
                        $slug_map[ $cat['slug'] ] = $cat['id'];
                    }
                    $imported = 0;
                    foreach ( $data as $item ) {
                        if ( empty( $item['name'] ) ) continue;
                        $cat_id = 0;
                        if ( ! empty( $item['category_slug'] ) && isset( $slug_map[ $item['category_slug'] ] ) ) {
                            $cat_id = $slug_map[ $item['category_slug'] ];
                        } elseif ( ! empty( $item['category_id'] ) ) {
                            $cat_id = intval( $item['category_id'] );
                        }
                        if ( ! $cat_id ) continue;
                        CCM_Categories::create_cookie( array(
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
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&msg=' . $msg ) );
            exit;
        }

        // Policy - company info
        if ( isset( $_POST['ccm_save_company'] ) && check_admin_referer( 'ccm_policy_company' ) ) {
            CCM_Policy_Generator::save_company_info( $_POST );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=policies&msg=saved' ) );
            exit;
        }

        // Policy - create/update page
        if ( isset( $_POST['ccm_create_policy_page'] ) && check_admin_referer( 'ccm_policy_create' ) ) {
            $type    = sanitize_text_field( $_POST['policy_type'] ?? '' );
            $page_id = CCM_Policy_Generator::create_page( $type );
            $msg     = is_wp_error( $page_id ) ? 'error' : ( get_post( $page_id )->post_status === 'draft' ? 'created' : 'updated' );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=policies&msg=' . $msg ) );
            exit;
        }

        // Settings
        if ( isset( $_POST['ccm_save_settings'] ) && check_admin_referer( 'ccm_settings_action' ) ) {
            $settings = array(
                'banner_title'       => sanitize_text_field( $_POST['banner_title'] ?? '' ),
                'banner_text'        => sanitize_textarea_field( $_POST['banner_text'] ?? '' ),
                'accept_all_text'    => sanitize_text_field( $_POST['accept_all_text'] ?? '' ),
                'reject_all_text'    => sanitize_text_field( $_POST['reject_all_text'] ?? '' ),
                'save_text'          => sanitize_text_field( $_POST['save_text'] ?? '' ),
                'settings_text'      => sanitize_text_field( $_POST['settings_text'] ?? '' ),
                'position'           => sanitize_text_field( $_POST['position'] ?? 'bottom' ),
                'primary_color'      => sanitize_hex_color( $_POST['primary_color'] ?? '#2271b1' ),
                'primary_text_color' => sanitize_hex_color( $_POST['primary_text_color'] ?? '#ffffff' ),
                'banner_bg_color'    => sanitize_hex_color( $_POST['banner_bg_color'] ?? '#ffffff' ),
                'banner_text_color'  => sanitize_hex_color( $_POST['banner_text_color'] ?? '#333333' ),
                'reject_bg_color'    => sanitize_hex_color( $_POST['reject_bg_color'] ?? '#f0f0f0' ),
                'reject_text_color'  => sanitize_hex_color( $_POST['reject_text_color'] ?? '#333333' ),
                'logo_url'           => esc_url_raw( $_POST['logo_url'] ?? '' ),
                'cookie_lifetime'    => min( intval( $_POST['cookie_lifetime'] ?? 365 ), 395 ),
                'cookie_icon'        => esc_url_raw( $_POST['cookie_icon'] ?? '' ),
            );
            update_option( 'ccm_settings', $settings );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=settings&msg=saved' ) );
            exit;
        }

        // Central reporting
        if ( isset( $_POST['ccm_save_central'] ) && check_admin_referer( 'ccm_central_action' ) ) {
            $existing = get_option( 'ccm_central_settings', array() );
            $enabled  = isset( $_POST['ccm_central_enabled'] ) ? 1 : 0;

            // API-nyckeln visas aldrig i formuläret. Tomt fält = behåll sparad nyckel.
            $api_key      = sanitize_text_field( $_POST['ccm_central_api_key'] ?? '' );
            $stored_key   = $existing['api_key'] ?? '';
            $api_key_enc  = '' !== $api_key ? self::encrypt_api_key( $api_key ) : $stored_key;

            // Rapporten innehåller sajtdata och nyckeln skickas som header —
            // därför tillåts endast https.
            $api_url = esc_url_raw( $_POST['ccm_central_api_url'] ?? '', array( 'https' ) );

            $central = array(
                'enabled' => $enabled,
                'api_url' => $api_url,
                'api_key' => $api_key_enc,
            );
            update_option( 'ccm_central_settings', $central );

            if ( $enabled && $central['api_url'] && $central['api_key'] ) {
                CCM_Central_Reporter::schedule_cron();
            } else {
                CCM_Central_Reporter::clear_cron();
            }

            // En icke-tom URL som föll bort betyder att den inte var https.
            $submitted_url = trim( $_POST['ccm_central_api_url'] ?? '' );
            $msg           = ( '' !== $submitted_url && '' === $api_url ) ? 'central_url_error' : 'saved';

            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=settings&msg=' . $msg ) );
            exit;
        }

        // Central: send report now
        if ( isset( $_POST['ccm_send_report_now'] ) && check_admin_referer( 'ccm_central_action' ) ) {
            CCM_Central_Reporter::send_report();
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=settings&msg=report_sent' ) );
            exit;
        }

        // Central: test connection
        if ( isset( $_POST['ccm_test_connection'] ) && check_admin_referer( 'ccm_central_action' ) ) {
            $result = CCM_Central_Reporter::test_connection();
            set_transient( 'ccm_connection_test', $result, 30 );
            wp_redirect( admin_url( 'options-general.php?page=cookie-consent&tab=settings&msg=connection_tested' ) );
            exit;
        }
    }

    public static function handle_export() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ccm_export_cookies' ) ) {
            wp_die( 'Unauthorized' );
        }

        $cookies    = CCM_Categories::get_cookies();
        $categories = CCM_Categories::get_all();
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

        $filename = 'ccm-cookies-' . wp_date( 'Y-m-d' ) . '.json';
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit;
    }

    public static function encrypt_api_key( $key ) {
        if ( empty( $key ) ) {
            return '';
        }
        $salt = wp_salt( 'auth' );
        return base64_encode( openssl_encrypt( $key, 'aes-256-cbc', $salt, 0, substr( md5( $salt ), 0, 16 ) ) );
    }

    public static function decrypt_api_key( $encrypted ) {
        if ( empty( $encrypted ) ) {
            return '';
        }
        $salt = wp_salt( 'auth' );
        $decrypted = openssl_decrypt( base64_decode( $encrypted ), 'aes-256-cbc', $salt, 0, substr( md5( $salt ), 0, 16 ) );
        return $decrypted !== false ? $decrypted : '';
    }

    private static function render_settings() {
        $settings = get_option( 'ccm_settings', array() );
        $defaults = array(
            'banner_title'       => 'Vi använder cookies',
            'banner_text'        => 'Denna webbplats använder cookies för att förbättra din upplevelse.',
            'accept_all_text'    => 'Acceptera alla',
            'reject_all_text'    => 'Avvisa alla',
            'save_text'          => 'Spara inställningar',
            'settings_text'      => 'Inställningar',
            'position'           => 'bottom',
            'primary_color'      => '#2271b1',
            'primary_text_color' => '#ffffff',
            'banner_bg_color'    => '#ffffff',
            'banner_text_color'  => '#333333',
            'reject_bg_color'    => '#f0f0f0',
            'reject_text_color'  => '#333333',
            'logo_url'           => '',
            'cookie_lifetime'    => 365,
            'cookie_icon'        => '',
        );
        $s = wp_parse_args( $settings, $defaults );

        $msg = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';

        if ( 'saved' === $msg ) {
            echo '<div class="notice notice-success is-dismissible"><p>Inställningar sparade.</p></div>';
        } elseif ( 'central_url_error' === $msg ) {
            echo '<div class="notice notice-error is-dismissible"><p>API-URL:en sparades inte — den måste börja med https://.</p></div>';
        }
        ?>
        <form method="post">
            <?php wp_nonce_field( 'ccm_settings_action' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="banner_title">Banner-titel</label></th>
                    <td><input type="text" id="banner_title" name="banner_title" value="<?php echo esc_attr( $s['banner_title'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="banner_text">Banner-text</label></th>
                    <td><textarea id="banner_text" name="banner_text" rows="3" class="large-text"><?php echo esc_textarea( $s['banner_text'] ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="accept_all_text">Acceptera alla-text</label></th>
                    <td><input type="text" id="accept_all_text" name="accept_all_text" value="<?php echo esc_attr( $s['accept_all_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="reject_all_text">Avvisa alla-text</label></th>
                    <td><input type="text" id="reject_all_text" name="reject_all_text" value="<?php echo esc_attr( $s['reject_all_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="save_text">Spara-text</label></th>
                    <td><input type="text" id="save_text" name="save_text" value="<?php echo esc_attr( $s['save_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="settings_text">Inställningar-text</label></th>
                    <td><input type="text" id="settings_text" name="settings_text" value="<?php echo esc_attr( $s['settings_text'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="position">Position</label></th>
                    <td>
                        <select id="position" name="position">
                            <option value="bottom" <?php selected( $s['position'], 'bottom' ); ?>>Botten</option>
                            <option value="top" <?php selected( $s['position'], 'top' ); ?>>Topp</option>
                            <option value="center" <?php selected( $s['position'], 'center' ); ?>>Center (modal)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="logo_url">Logotyp</label></th>
                    <td>
                        <div class="ccm-logo-field">
                            <input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr( $s['logo_url'] ); ?>">
                            <button type="button" id="ccm-upload-logo" class="button">Välj bild</button>
                            <button type="button" id="ccm-remove-logo" class="button" <?php echo empty( $s['logo_url'] ) ? 'style="display:none;"' : ''; ?>>Ta bort</button>
                            <div id="ccm-logo-preview" class="ccm-logo-preview">
                                <?php if ( ! empty( $s['logo_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="description">Visas ovanför banner-titeln. Rekommenderad höjd: 40px.</p>
                    </td>
                </tr>
                <tr>
                    <th>Färger</th>
                    <td>
                        <table class="ccm-color-grid">
                            <tr>
                                <td>
                                    <label for="banner_bg_color">Banner-bakgrund</label>
                                    <input type="color" id="banner_bg_color" name="banner_bg_color" value="<?php echo esc_attr( $s['banner_bg_color'] ); ?>">
                                </td>
                                <td>
                                    <label for="banner_text_color">Banner-text</label>
                                    <input type="color" id="banner_text_color" name="banner_text_color" value="<?php echo esc_attr( $s['banner_text_color'] ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="primary_color">Primärknapp-bakgrund</label>
                                    <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>">
                                </td>
                                <td>
                                    <label for="primary_text_color">Primärknapp-text</label>
                                    <input type="color" id="primary_text_color" name="primary_text_color" value="<?php echo esc_attr( $s['primary_text_color'] ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="reject_bg_color">Avvisa-bakgrund</label>
                                    <input type="color" id="reject_bg_color" name="reject_bg_color" value="<?php echo esc_attr( $s['reject_bg_color'] ); ?>">
                                </td>
                                <td>
                                    <label for="reject_text_color">Avvisa-text</label>
                                    <input type="color" id="reject_text_color" name="reject_text_color" value="<?php echo esc_attr( $s['reject_text_color'] ); ?>">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th><label for="cookie_icon">Cookie-ikon</label></th>
                    <td>
                        <div class="ccm-icon-field">
                            <input type="hidden" id="cookie_icon" name="cookie_icon" value="<?php echo esc_attr( $s['cookie_icon'] ); ?>">
                            <button type="button" id="ccm-upload-icon" class="button">Välj ikon</button>
                            <button type="button" id="ccm-remove-icon" class="button" <?php echo empty( $s['cookie_icon'] ) ? 'style="display:none;"' : ''; ?>>Ta bort</button>
                            <div id="ccm-icon-preview" class="ccm-icon-preview">
                                <?php if ( ! empty( $s['cookie_icon'] ) ) : ?>
                                    <img src="<?php echo esc_url( $s['cookie_icon'] ); ?>" alt="" style="max-height:40px;">
                                <?php else : ?>
                                    <span style="font-size:2em;">🍪</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="description">Välj en bild från mediabiblioteket som visas på den flytande cookie-knappen. Utan bild visas 🍪.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cookie_lifetime">Cookie-livslängd (dagar)</label></th>
                    <td>
                        <input type="number" id="cookie_lifetime" name="cookie_lifetime" value="<?php echo esc_attr( $s['cookie_lifetime'] ); ?>" min="1" max="395">
                        <p class="description">Max 395 dagar (EDPB-riktlinje, ~13 månader).</p>
                    </td>
                </tr>
            </table>
            <script>
            jQuery(function($){
                var frame;
                $('#ccm-upload-logo').on('click', function(e){
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: 'Välj logotyp',
                        button: { text: 'Använd denna bild' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#logo_url').val(attachment.url);
                        $('#ccm-logo-preview').html('<img src="' + attachment.url + '" alt="">');
                        $('#ccm-remove-logo').show();
                    });
                    frame.open();
                });
                $('#ccm-remove-logo').on('click', function(e){
                    e.preventDefault();
                    $('#logo_url').val('');
                    $('#ccm-logo-preview').html('');
                    $(this).hide();
                });

                var iconFrame;
                $('#ccm-upload-icon').on('click', function(e){
                    e.preventDefault();
                    if (iconFrame) { iconFrame.open(); return; }
                    iconFrame = wp.media({
                        title: 'Välj cookie-ikon',
                        button: { text: 'Använd denna bild' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    iconFrame.on('select', function(){
                        var attachment = iconFrame.state().get('selection').first().toJSON();
                        $('#cookie_icon').val(attachment.url);
                        $('#ccm-icon-preview').html('<img src="' + attachment.url + '" alt="" style="max-height:40px;">');
                        $('#ccm-remove-icon').show();
                    });
                    iconFrame.open();
                });
                $('#ccm-remove-icon').on('click', function(e){
                    e.preventDefault();
                    $('#cookie_icon').val('');
                    $('#ccm-icon-preview').html('<span style="font-size:2em;">🍪</span>');
                    $(this).hide();
                });
            });
            </script>
            <?php submit_button( 'Spara inställningar', 'primary', 'ccm_save_settings' ); ?>
        </form>

        <hr>
        <h2>Central rapportering</h2>
        <p>Anslut till CCM Central Dashboard för att övervaka compliance-status centralt.</p>

        <?php
        $central   = get_option( 'ccm_central_settings', array() );
        $c_enabled = ! empty( $central['enabled'] );
        $c_url     = $central['api_url'] ?? '';
        // Nyckeln dekrypteras aldrig till formuläret — vi visar bara om den finns.
        $has_key   = ! empty( $central['api_key'] );
        $last_rep  = get_option( 'ccm_central_last_report', '' );
        $last_err  = get_option( 'ccm_central_last_error', '' );
        $conn_test = get_transient( 'ccm_connection_test' );

        if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'report_sent' ) {
            if ( $last_err ) {
                echo '<div class="notice notice-error is-dismissible"><p>Rapport misslyckades: ' . esc_html( $last_err ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Rapport skickad.</p></div>';
            }
        }
        if ( $conn_test ) {
            $class = $conn_test['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html( $conn_test['message'] ) . '</p></div>';
            delete_transient( 'ccm_connection_test' );
        }
        ?>

        <form method="post">
            <?php wp_nonce_field( 'ccm_central_action' ); ?>
            <table class="form-table">
                <tr>
                    <th>Aktivera</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ccm_central_enabled" value="1" <?php checked( $c_enabled ); ?>>
                            Skicka dagliga statusrapporter till CCM Central
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="ccm_central_api_url">API-URL</label></th>
                    <td>
                        <input type="url" id="ccm_central_api_url" name="ccm_central_api_url" value="<?php echo esc_attr( $c_url ); ?>" class="regular-text" placeholder="https://central.example.com/wp-json/">
                        <p class="description">REST API-URL till din CCM Central-installation. Måste vara https.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ccm_central_api_key">API-nyckel</label></th>
                    <td>
                        <input type="password" id="ccm_central_api_key" name="ccm_central_api_key" value="" class="regular-text" placeholder="<?php echo esc_attr( $has_key ? '•••••••• (sparad)' : 'ccm_ak_...' ); ?>" autocomplete="off">
                        <p class="description">
                            Genereras i CCM Central Dashboard vid registrering av sajt.
                            <?php if ( $has_key ) : ?>
                                <br>En nyckel är sparad. Lämna fältet tomt för att behålla den, eller klistra in en ny för att ersätta den.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ( $c_enabled && $c_url && $has_key ) : ?>
                            <?php if ( $last_rep ) : ?>
                                <span style="color:#46b450;">&#10003;</span> Ansluten — Senaste rapport: <?php echo esc_html( $last_rep ); ?>
                            <?php else : ?>
                                <span style="color:#f0b849;">&#9679;</span> Konfigurerad — Ingen rapport skickad ännu
                            <?php endif; ?>
                            <?php if ( $last_err ) : ?>
                                <br><span style="color:#dc3232;">&#10007;</span> Senaste fel: <?php echo esc_html( $last_err ); ?>
                            <?php endif; ?>
                        <?php else : ?>
                            <span style="color:#999;">&#9679;</span> Inaktiverad
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 'Spara central-inställningar', 'primary', 'ccm_save_central', false ); ?>
                &nbsp;
                <?php submit_button( 'Skicka rapport nu', 'secondary', 'ccm_send_report_now', false ); ?>
                &nbsp;
                <?php submit_button( 'Testa anslutning', 'secondary', 'ccm_test_connection', false ); ?>
            </p>
        </form>
        <?php
    }
}
