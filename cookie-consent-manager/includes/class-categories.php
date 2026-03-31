<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Categories {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'cc_categories';
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " ORDER BY sort_order ASC",
            ARRAY_A
        );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    public static function create( $data ) {
        global $wpdb;
        $wpdb->insert( self::table(), array(
            'slug'        => sanitize_title( $data['slug'] ),
            'title'       => sanitize_text_field( $data['title'] ),
            'description' => sanitize_textarea_field( $data['description'] ),
            'is_required' => intval( $data['is_required'] ),
            'sort_order'  => intval( $data['sort_order'] ),
        ) );
        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        return $wpdb->update(
            self::table(),
            array(
                'slug'        => sanitize_title( $data['slug'] ),
                'title'       => sanitize_text_field( $data['title'] ),
                'description' => sanitize_textarea_field( $data['description'] ),
                'is_required' => intval( $data['is_required'] ),
                'sort_order'  => intval( $data['sort_order'] ),
            ),
            array( 'id' => intval( $id ) )
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'cc_cookies', array( 'category_id' => intval( $id ) ) );
        return $wpdb->delete( self::table(), array( 'id' => intval( $id ) ) );
    }

    public static function get_cookies_table() {
        global $wpdb;
        return $wpdb->prefix . 'cc_cookies';
    }

    public static function get_cookies( $category_id = null ) {
        global $wpdb;
        $table = self::get_cookies_table();
        if ( $category_id ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE category_id = %d", $category_id ),
                ARRAY_A
            );
        }
        return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
    }

    public static function get_cookie( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::get_cookies_table() . " WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    public static function create_cookie( $data ) {
        global $wpdb;
        $wpdb->insert( self::get_cookies_table(), array(
            'category_id' => intval( $data['category_id'] ),
            'name'        => sanitize_text_field( $data['name'] ),
            'provider'    => sanitize_text_field( $data['provider'] ),
            'purpose'     => sanitize_textarea_field( $data['purpose'] ),
            'expiry'      => sanitize_text_field( $data['expiry'] ),
        ) );
        return $wpdb->insert_id;
    }

    public static function update_cookie( $id, $data ) {
        global $wpdb;
        return $wpdb->update(
            self::get_cookies_table(),
            array(
                'category_id' => intval( $data['category_id'] ),
                'name'        => sanitize_text_field( $data['name'] ),
                'provider'    => sanitize_text_field( $data['provider'] ),
                'purpose'     => sanitize_textarea_field( $data['purpose'] ),
                'expiry'      => sanitize_text_field( $data['expiry'] ),
            ),
            array( 'id' => intval( $id ) )
        );
    }

    public static function delete_cookie( $id ) {
        global $wpdb;
        return $wpdb->delete( self::get_cookies_table(), array( 'id' => intval( $id ) ) );
    }

    /**
     * Get all cookies grouped by category_id in a single query.
     */
    public static function get_cookies_grouped() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT * FROM " . self::get_cookies_table() . " ORDER BY category_id ASC",
            ARRAY_A
        );
        $grouped = array();
        foreach ( $rows as $row ) {
            $grouped[ $row['category_id'] ][] = $row;
        }
        return $grouped;
    }
}
