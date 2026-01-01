<?php
/**
 * Dynamic facet maps + admin management (generic).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option keys (site-scoped in multisite).
 */
define( 'TAXA_FACETS_OPTION_LABEL_MAP_RAW', 'taxa_facets_label_map_raw' );
define( 'TAXA_FACETS_OPTION_UI_OVERRIDES', 'taxa_facets_ui_overrides' );
define( 'TAXA_FACETS_OPTION_EXCLUDED_COLORS', 'taxa_facets_excluded_colors' );
define( 'TAXA_FACETS_OPTION_COLOR_MAP_RAW', 'taxa_facets_color_map_raw' );
define( 'TAXA_FACETS_OPTION_MAPS_LOCKED', 'taxa_facets_maps_locked' );
define( 'TAXA_FACETS_OPTION_SIZE_MAP_RAW', 'taxa_facets_size_map_raw' );
define( 'TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW', 'taxa_facets_shape_primary_map_raw' );
define( 'TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW', 'taxa_facets_shape_secondary_map_raw' );
define( 'TAXA_FACETS_OPTION_PATTERN_MAP_RAW', 'taxa_facets_pattern_map_raw' );
define( 'TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW', 'taxa_facets_trait_primary_map_raw' );
define( 'TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW', 'taxa_facets_trait_secondary_map_raw' );
define( 'TAXA_FACETS_OPTION_DIET_MAP_RAW', 'taxa_facets_diet_map_raw' );
define( 'TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW', 'taxa_facets_behavior_map_raw' );
define( 'TAXA_FACETS_OPTION_HABITAT_MAP_RAW', 'taxa_facets_habitat_map_raw' );
define( 'TAXA_FACETS_OPTION_SHORTCODE_SCOPE', 'taxa_facets_shortcode_scope' );
define( 'TAXA_FACETS_OPTION_MAP_SCOPE', 'taxa_facets_map_scope' );
define( 'TAXA_FACETS_OPTION_SCOPE_LIST', 'taxa_facets_scope_list' );
define( 'TAXA_FACETS_OPTION_SEED_PROMPT', 'taxa_facets_seed_prompt' );

/**
 * Current scope (used for scoped map lookups).
 */
function taxa_facets_set_current_scope( $scope ) {
    $GLOBALS['taxa_facets_current_scope'] = taxa_facets_sanitize_scope_value( $scope );
}

function taxa_facets_get_current_scope() {
    return isset( $GLOBALS['taxa_facets_current_scope'] ) ? (string) $GLOBALS['taxa_facets_current_scope'] : '';
}

function taxa_facets_allowed_scopes() {
    $raw = get_option( TAXA_FACETS_OPTION_SCOPE_LIST, '' );
    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_sanitize_scope_value( $value ) {
    $value = is_string( $value ) ? sanitize_key( $value ) : '';
    if ( '' === $value ) {
        return '';
    }

    $allowed = taxa_facets_allowed_scopes();
    if ( empty( $allowed ) ) {
        return $value;
    }

    return in_array( $value, $allowed, true ) ? $value : '';
}

function taxa_facets_get_map_scope() {
    return taxa_facets_sanitize_scope_value( get_option( TAXA_FACETS_OPTION_MAP_SCOPE, '' ) );
}

function taxa_facets_get_shortcode_scope() {
    return taxa_facets_sanitize_scope_value( get_option( TAXA_FACETS_OPTION_SHORTCODE_SCOPE, '' ) );
}

function taxa_facets_get_active_scope() {
    return taxa_facets_get_current_scope();
}

function taxa_facets_get_scoped_option_key( $base_key, $scope ) {
    $scope = taxa_facets_sanitize_scope_value( $scope );
    if ( '' === $scope ) {
        return $base_key;
    }

    return $base_key . '_' . $scope;
}

function taxa_facets_get_scoped_option_value( $base_key, $scope ) {
    $scoped_key = taxa_facets_get_scoped_option_key( $base_key, $scope );
    $value = get_option( $scoped_key, '' );

    if ( $value === '' && $scoped_key !== $base_key ) {
        $value = get_option( $base_key, '' );
    }

    return $value;
}

function taxa_facets_parse_slug_list( $raw ) {
    if ( ! is_string( $raw ) || $raw === '' ) {
        return array();
    }

    $parts = preg_split( '/[\r\n,]+/', $raw );
    $slugs = array();

    foreach ( $parts as $part ) {
        $part = sanitize_key( trim( $part ) );
        if ( $part !== '' ) {
            $slugs[] = $part;
        }
    }

    return array_values( array_unique( $slugs ) );
}

function taxa_facets_build_enum_map( array $slugs ) {
    $map = array();

    foreach ( $slugs as $index => $slug ) {
        $map[ $slug ] = $index + 1;
    }

    return $map;
}

function taxa_facets_build_bitmask_map( array $slugs ) {
    $map = array();

    foreach ( $slugs as $index => $slug ) {
        $map[ $slug ] = 1 << $index;
    }

    return $map;
}

function taxa_facets_maps_locked() {
    return (bool) get_option( TAXA_FACETS_OPTION_MAPS_LOCKED, false );
}

function taxa_facets_get_color_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_COLOR_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_size_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_SIZE_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_shape_primary_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_shape_secondary_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_pattern_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_PATTERN_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_trait_primary_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_trait_secondary_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_diet_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_DIET_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_behavior_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_get_habitat_slugs() {
    $scope = taxa_facets_get_active_scope();
    $raw = taxa_facets_get_scoped_option_value( TAXA_FACETS_OPTION_HABITAT_MAP_RAW, $scope );

    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_provide_color_map( $colors, $scope = '' ) {
    $map = taxa_facets_build_bitmask_map( taxa_facets_get_color_slugs() );
    return ! empty( $map ) ? $map : $colors;
}

function taxa_facets_provide_size_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_size_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_shape_primary_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_shape_primary_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_shape_secondary_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_shape_secondary_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_pattern_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_pattern_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_trait_primary_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_trait_primary_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_trait_secondary_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_trait_secondary_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_diet_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_enum_map( taxa_facets_get_diet_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_behavior_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_bitmask_map( taxa_facets_get_behavior_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

function taxa_facets_provide_habitat_map( $defaults, $scope = '' ) {
    $map = taxa_facets_build_bitmask_map( taxa_facets_get_habitat_slugs() );
    return ! empty( $map ) ? $map : $defaults;
}

add_filter( 'taxa_facets_color_map', 'taxa_facets_provide_color_map', 5, 2 );
add_filter( 'taxa_facets_size_enum_map', 'taxa_facets_provide_size_map', 5, 2 );
add_filter( 'taxa_facets_shape_primary_enum_map', 'taxa_facets_provide_shape_primary_map', 5, 2 );
add_filter( 'taxa_facets_shape_secondary_enum_map', 'taxa_facets_provide_shape_secondary_map', 5, 2 );
add_filter( 'taxa_facets_pattern_enum_map', 'taxa_facets_provide_pattern_map', 5, 2 );
add_filter( 'taxa_facets_trait_primary_enum_map', 'taxa_facets_provide_trait_primary_map', 5, 2 );
add_filter( 'taxa_facets_trait_secondary_enum_map', 'taxa_facets_provide_trait_secondary_map', 5, 2 );
add_filter( 'taxa_facets_diet_enum_map', 'taxa_facets_provide_diet_map', 5, 2 );
add_filter( 'taxa_facets_behavior_map', 'taxa_facets_provide_behavior_map', 5, 2 );
add_filter( 'taxa_facets_habitat_map', 'taxa_facets_provide_habitat_map', 5, 2 );

/**
 * Optional color exclusions (remove from map/UI).
 */
function taxa_facets_get_excluded_colors() {
    $raw = get_option( TAXA_FACETS_OPTION_EXCLUDED_COLORS, '' );
    return taxa_facets_parse_slug_list( $raw );
}

function taxa_facets_filter_color_map( $colors ) {
    $excluded = taxa_facets_get_excluded_colors();
    if ( empty( $excluded ) ) {
        return $colors;
    }

    foreach ( $excluded as $slug ) {
        unset( $colors[ $slug ] );
    }

    return $colors;
}
add_filter( 'taxa_facets_color_map', 'taxa_facets_filter_color_map', 20, 2 );

/**
 * UI overrides (labels + enabled flags).
 */
function taxa_facets_get_ui_overrides_schema() {
    return array(
        'size'            => array( 'label' => __( 'Size', 'taxonomy-api' ), 'enabled' => true ),
        'colors'          => array( 'label' => __( 'Color', 'taxonomy-api' ), 'enabled' => true ),
        'shape_primary'   => array( 'label' => __( 'Primary shape', 'taxonomy-api' ), 'enabled' => true ),
        'shape_secondary' => array( 'label' => __( 'Secondary shape', 'taxonomy-api' ), 'enabled' => true ),
        'pattern'         => array( 'label' => __( 'Pattern', 'taxonomy-api' ), 'enabled' => true ),
        'trait_primary'   => array( 'label' => __( 'Primary trait', 'taxonomy-api' ), 'enabled' => true ),
        'trait_secondary' => array( 'label' => __( 'Secondary trait', 'taxonomy-api' ), 'enabled' => true ),
        'diet'            => array( 'label' => __( 'Diet', 'taxonomy-api' ), 'enabled' => true ),
        'behaviors'       => array( 'label' => __( 'Behavior', 'taxonomy-api' ), 'enabled' => true ),
        'habitats'        => array( 'label' => __( 'Habitat', 'taxonomy-api' ), 'enabled' => true ),
    );
}

function taxa_facets_get_ui_overrides() {
    $value = get_option( TAXA_FACETS_OPTION_UI_OVERRIDES, array() );
    return is_array( $value ) ? $value : array();
}

function taxa_facets_get_override_enabled( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ]['enabled'] ) ) {
        return (bool) $overrides[ $key ]['enabled'];
    }

    return (bool) $default;
}

function taxa_facets_get_override_label( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ]['label'] ) && is_string( $overrides[ $key ]['label'] ) ) {
        $label = trim( $overrides[ $key ]['label'] );
        if ( '' !== $label ) {
            return $label;
        }
    }

    return $default;
}

function taxa_facets_sanitize_ui_overrides( $value ) {
    $allowed = taxa_facets_get_ui_overrides_schema();

    if ( ! is_array( $value ) ) {
        return array();
    }

    $out = array();
    foreach ( $allowed as $key => $defaults ) {
        $enabled = isset( $value[ $key ]['enabled'] ) ? (bool) $value[ $key ]['enabled'] : $defaults['enabled'];
        $label   = isset( $value[ $key ]['label'] ) ? sanitize_text_field( $value[ $key ]['label'] ) : $defaults['label'];

        $out[ $key ] = array(
            'enabled' => (bool) $enabled,
            'label'   => $label,
        );
    }

    return $out;
}

/**
 * Apply UI overrides and build the frontend facet config dynamically.
 */
add_filter( 'taxa_facets_frontend_options', 'taxa_facets_dynamic_frontend_options' );
function taxa_facets_dynamic_frontend_options( $config ) {
    $overrides = taxa_facets_get_ui_overrides();

    $size_map            = (array) taxa_facets_get_size_enum_map();
    $color_map           = (array) taxa_facets_get_color_map();
    $shape_primary_map   = (array) taxa_facets_get_shape_primary_enum_map();
    $shape_secondary_map = (array) taxa_facets_get_shape_secondary_enum_map();
    $pattern_map         = (array) taxa_facets_get_pattern_enum_map();
    $trait_primary_map   = (array) taxa_facets_get_trait_primary_enum_map();
    $trait_secondary_map = (array) taxa_facets_get_trait_secondary_enum_map();
    $diet_map            = (array) taxa_facets_get_diet_enum_map();
    $behavior_map        = (array) taxa_facets_get_behavior_map();
    $habitat_map         = (array) taxa_facets_get_habitat_map();

    $build_enum_options = function( array $map, $include_any ) {
        $options = array();
        if ( $include_any ) {
            $options[] = array( 'slug' => '', 'label' => __( 'Any', 'taxonomy-api' ) );
        }

        foreach ( array_keys( $map ) as $slug ) {
            $options[] = array(
                'slug'  => (string) $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
            );
        }

        return $options;
    };

    $build_color_options = function( array $map ) {
        $options = array();
        foreach ( array_keys( $map ) as $slug ) {
            $options[] = array(
                'slug'  => (string) $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
                'class' => 'taxa-chip--' . sanitize_html_class( $slug ),
            );
        }

        return $options;
    };

    $build_bitmask_options = function( array $map ) {
        $options = array();
        foreach ( array_keys( $map ) as $slug ) {
            $options[] = array(
                'slug'  => (string) $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
            );
        }

        return $options;
    };

    $config = array();

    if ( ! empty( $size_map ) ) {
        $config['size'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'size', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'size', __( 'Size', 'taxonomy-api' ), $overrides ),
            'key'     => 'size',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $size_map, true ),
        );
    }

    if ( ! empty( $color_map ) ) {
        $config['colors'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'colors', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'colors', __( 'Color', 'taxonomy-api' ), $overrides ),
            'key'     => 'colors',
            'type'    => 'color',
            'multi'   => true,
            'options' => $build_color_options( $color_map ),
        );
    }

    if ( ! empty( $shape_primary_map ) ) {
        $config['shape_primary'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'shape_primary', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'shape_primary', __( 'Primary shape', 'taxonomy-api' ), $overrides ),
            'key'     => 'shape_primary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $shape_primary_map, true ),
        );
    }

    if ( ! empty( $shape_secondary_map ) ) {
        $config['shape_secondary'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'shape_secondary', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'shape_secondary', __( 'Secondary shape', 'taxonomy-api' ), $overrides ),
            'key'     => 'shape_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $shape_secondary_map, true ),
        );
    }

    if ( ! empty( $pattern_map ) ) {
        $config['pattern'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'pattern', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'pattern', __( 'Pattern', 'taxonomy-api' ), $overrides ),
            'key'     => 'pattern',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $pattern_map, true ),
        );
    }

    if ( ! empty( $trait_primary_map ) ) {
        $config['trait_primary'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'trait_primary', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'trait_primary', __( 'Primary trait', 'taxonomy-api' ), $overrides ),
            'key'     => 'trait_primary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $trait_primary_map, true ),
        );
    }

    if ( ! empty( $trait_secondary_map ) ) {
        $config['trait_secondary'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'trait_secondary', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'trait_secondary', __( 'Secondary trait', 'taxonomy-api' ), $overrides ),
            'key'     => 'trait_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => $build_enum_options( $trait_secondary_map, true ),
        );
    }

    if ( ! empty( $diet_map ) ) {
        $config['diet'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'diet', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'diet', __( 'Diet', 'taxonomy-api' ), $overrides ),
            'key'     => 'diet',
            'type'    => 'enum',
            'multi'   => true,
            'options' => $build_enum_options( $diet_map, false ),
        );
    }

    if ( ! empty( $behavior_map ) ) {
        $config['behaviors'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'behaviors', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'behaviors', __( 'Behavior', 'taxonomy-api' ), $overrides ),
            'key'     => 'behaviors',
            'type'    => 'bitmask',
            'multi'   => true,
            'options' => $build_bitmask_options( $behavior_map ),
        );
    }

    if ( ! empty( $habitat_map ) ) {
        $config['habitats'] = array(
            'enabled' => taxa_facets_get_override_enabled( 'habitats', true, $overrides ),
            'label'   => taxa_facets_get_override_label( 'habitats', __( 'Habitat', 'taxonomy-api' ), $overrides ),
            'key'     => 'habitats',
            'type'    => 'bitmask',
            'multi'   => true,
            'options' => $build_bitmask_options( $habitat_map ),
        );
    }

    return $config;
}

/**
 * Relabel facet summaries in the /taxa/v1/search response.
 */
add_filter( 'rest_post_dispatch', 'taxa_facets_relabel_in_taxa_search', 10, 3 );
function taxa_facets_relabel_in_taxa_search( $result, $server, $request ) {
    $route = $request->get_route();
    if ( strpos( $route, '/taxa/v1/search' ) === false ) {
        return $result;
    }

    if ( is_wp_error( $result ) || ! $result instanceof WP_REST_Response ) {
        return $result;
    }

    $data = $result->get_data();
    if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
        return $result;
    }

    foreach ( $data['items'] as &$item ) {
        if ( empty( $item['facets'] ) || ! is_array( $item['facets'] ) ) {
            continue;
        }

        $item['facets'] = taxa_facets_adjust_trait_labels(
            $item['facets'],
            ! empty( $item['id'] ) ? (int) $item['id'] : 0
        );
    }
    unset( $item );

    $result->set_data( $data );
    return $result;
}

function taxa_facets_sanitize_label_map_raw( $value ) {
    return is_string( $value ) ? trim( $value ) : '';
}

function taxa_facets_get_label_map() {
    $raw = get_option( TAXA_FACETS_OPTION_LABEL_MAP_RAW, '' );
    if ( ! is_string( $raw ) || $raw === '' ) {
        return array();
    }

    $lines = preg_split( '/\r\n|\r|\n/', $raw );
    $map   = array();

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( $line === '' ) {
            continue;
        }

        $parts = explode( '|', $line, 2 );
        if ( count( $parts ) !== 2 ) {
            continue;
        }

        $old = trim( $parts[0] );
        $new = trim( $parts[1] );

        if ( $old === '' || $new === '' ) {
            continue;
        }

        $map[ $old ] = $new;
    }

    return $map;
}

function taxa_facets_adjust_trait_labels( $facets, $post_id ) {
    if ( ! is_array( $facets ) || empty( $facets ) ) {
        return $facets;
    }

    $map = taxa_facets_get_label_map();
    if ( empty( $map ) ) {
        return $facets;
    }

    $out = array();

    foreach ( $facets as $line ) {
        $new_line = $line;

        foreach ( $map as $old_prefix => $new_prefix ) {
            if ( strpos( $new_line, $old_prefix ) === 0 ) {
                $new_line = $new_prefix . substr( $new_line, strlen( $old_prefix ) );
                break;
            }
        }

        $out[] = $new_line;
    }

    return $out;
}

/**
 * Admin settings + GPT seeding.
 */
add_action( 'admin_menu', 'taxa_facets_add_settings_page' );
function taxa_facets_add_settings_page() {
    add_options_page(
        __( 'Taxa Facets', 'taxonomy-api' ),
        __( 'Taxa Facets', 'taxonomy-api' ),
        'manage_options',
        'taxa-facet-maps',
        'taxa_facets_render_settings_page'
    );
}

add_action( 'admin_init', 'taxa_facets_register_settings' );
function taxa_facets_register_settings() {
    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_LABEL_MAP_RAW,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_label_map_raw' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_UI_OVERRIDES,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_ui_overrides' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_EXCLUDED_COLORS,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_excluded_colors' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_MAPS_LOCKED,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_maps_locked' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_MAP_SCOPE,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_map_scope' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_SHORTCODE_SCOPE,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_shortcode_scope' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_SCOPE_LIST,
        array( 'sanitize_callback' => 'taxa_facets_sanitize_scope_list' )
    );

    register_setting(
        'taxa_facets_maps_group',
        TAXA_FACETS_OPTION_SEED_PROMPT,
        array( 'sanitize_callback' => 'sanitize_textarea_field' )
    );

    $map_options = array(
        TAXA_FACETS_OPTION_COLOR_MAP_RAW           => 'taxa_facets_sanitize_color_map_raw',
        TAXA_FACETS_OPTION_SIZE_MAP_RAW            => 'taxa_facets_sanitize_size_map_raw',
        TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW   => 'taxa_facets_sanitize_shape_primary_map_raw',
        TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW => 'taxa_facets_sanitize_shape_secondary_map_raw',
        TAXA_FACETS_OPTION_PATTERN_MAP_RAW         => 'taxa_facets_sanitize_pattern_map_raw',
        TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW   => 'taxa_facets_sanitize_trait_primary_map_raw',
        TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW => 'taxa_facets_sanitize_trait_secondary_map_raw',
        TAXA_FACETS_OPTION_DIET_MAP_RAW            => 'taxa_facets_sanitize_diet_map_raw',
        TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW        => 'taxa_facets_sanitize_behavior_map_raw',
        TAXA_FACETS_OPTION_HABITAT_MAP_RAW         => 'taxa_facets_sanitize_habitat_map_raw',
    );

    foreach ( $map_options as $key => $sanitize_cb ) {
        register_setting(
            'taxa_facets_maps_group',
            $key,
            array( 'sanitize_callback' => $sanitize_cb )
        );
    }

    foreach ( taxa_facets_allowed_scopes() as $scope ) {
        foreach ( $map_options as $option_key => $sanitize_cb ) {
            register_setting(
                'taxa_facets_maps_group',
                taxa_facets_get_scoped_option_key( $option_key, $scope ),
                array( 'sanitize_callback' => $sanitize_cb )
            );
        }
    }

    add_settings_section(
        'taxa_facets_labels_section',
        __( 'Facet Label Remapping', 'taxonomy-api' ),
        'taxa_facets_labels_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_label_map_raw',
        __( 'Facet label map', 'taxonomy-api' ),
        'taxa_facets_label_map_raw_field_render',
        'taxa-facet-maps',
        'taxa_facets_labels_section'
    );

    add_settings_section(
        'taxa_facets_ui_section',
        __( 'Facet UI Overrides', 'taxonomy-api' ),
        'taxa_facets_ui_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_ui_overrides',
        __( 'UI overrides', 'taxonomy-api' ),
        'taxa_facets_ui_overrides_field_render',
        'taxa-facet-maps',
        'taxa_facets_ui_section'
    );

    add_settings_section(
        'taxa_facets_color_section',
        __( 'Color Controls', 'taxonomy-api' ),
        'taxa_facets_color_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_color_map',
        __( 'Color map', 'taxonomy-api' ),
        'taxa_facets_color_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_color_section'
    );

    add_settings_field(
        'taxa_facets_color_exclusions',
        __( 'Excluded colors', 'taxonomy-api' ),
        'taxa_facets_color_exclusions_field_render',
        'taxa-facet-maps',
        'taxa_facets_color_section'
    );

    add_settings_section(
        'taxa_facets_map_section',
        __( 'Facet Maps', 'taxonomy-api' ),
        'taxa_facets_map_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_maps_lock',
        __( 'Lock map editing', 'taxonomy-api' ),
        'taxa_facets_maps_lock_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_scope_list',
        __( 'Facet scopes', 'taxonomy-api' ),
        'taxa_facets_scope_list_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_map_scope',
        __( 'Facet map scope', 'taxonomy-api' ),
        'taxa_facets_map_scope_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_size_map',
        __( 'Size map', 'taxonomy-api' ),
        'taxa_facets_size_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_shape_primary_map',
        __( 'Primary shape map', 'taxonomy-api' ),
        'taxa_facets_shape_primary_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_shape_secondary_map',
        __( 'Secondary shape map', 'taxonomy-api' ),
        'taxa_facets_shape_secondary_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_pattern_map',
        __( 'Pattern map', 'taxonomy-api' ),
        'taxa_facets_pattern_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_trait_primary_map',
        __( 'Primary trait map', 'taxonomy-api' ),
        'taxa_facets_trait_primary_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_trait_secondary_map',
        __( 'Secondary trait map', 'taxonomy-api' ),
        'taxa_facets_trait_secondary_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_diet_map',
        __( 'Diet map', 'taxonomy-api' ),
        'taxa_facets_diet_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_behavior_map',
        __( 'Behavior map', 'taxonomy-api' ),
        'taxa_facets_behavior_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_field(
        'taxa_facets_habitat_map',
        __( 'Habitat map', 'taxonomy-api' ),
        'taxa_facets_habitat_map_field_render',
        'taxa-facet-maps',
        'taxa_facets_map_section'
    );

    add_settings_section(
        'taxa_facets_overview_section',
        __( 'Current Map Overview', 'taxonomy-api' ),
        'taxa_facets_overview_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_overview',
        __( 'Overview', 'taxonomy-api' ),
        'taxa_facets_overview_field_render',
        'taxa-facet-maps',
        'taxa_facets_overview_section'
    );

    add_settings_section(
        'taxa_facets_shortcode_section',
        __( 'Shortcode Defaults', 'taxonomy-api' ),
        'taxa_facets_shortcode_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_shortcode_scope',
        __( 'Default scope for [taxa_explorer]', 'taxonomy-api' ),
        'taxa_facets_shortcode_scope_field_render',
        'taxa-facet-maps',
        'taxa_facets_shortcode_section'
    );

    add_settings_section(
        'taxa_facets_seed_section',
        __( 'GPT Map Seeding', 'taxonomy-api' ),
        'taxa_facets_seed_section_intro',
        'taxa-facet-maps'
    );

    add_settings_field(
        'taxa_facets_seed_prompt',
        __( 'Seed prompt instructions', 'taxonomy-api' ),
        'taxa_facets_seed_prompt_field_render',
        'taxa-facet-maps',
        'taxa_facets_seed_section'
    );
}

function taxa_facets_labels_section_intro() {
    echo '<p>' . esc_html__( 'Provide optional label remaps for facet summary lines. Format: "Old Prefix|New Prefix" per line.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_label_map_raw_field_render() {
    $value = get_option( TAXA_FACETS_OPTION_LABEL_MAP_RAW, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( TAXA_FACETS_OPTION_LABEL_MAP_RAW ); ?>"
        id="<?php echo esc_attr( TAXA_FACETS_OPTION_LABEL_MAP_RAW ); ?>"
        rows="6"
        class="large-text"
        placeholder="<?php echo esc_attr__( 'Trait:|Feature: (example)', 'taxonomy-api' ); ?>"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <?php
}

function taxa_facets_ui_section_intro() {
    echo '<p>' . esc_html__( 'Control which facets appear in the UI and override their labels.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_ui_overrides_field_render() {
    $schema    = taxa_facets_get_ui_overrides_schema();
    $overrides = taxa_facets_get_ui_overrides();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Facet', 'taxonomy-api' ); ?></th>
                <th><?php esc_html_e( 'Enabled', 'taxonomy-api' ); ?></th>
                <th><?php esc_html_e( 'Label', 'taxonomy-api' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $schema as $key => $defaults ) : ?>
                <?php
                $enabled = isset( $overrides[ $key ]['enabled'] ) ? (bool) $overrides[ $key ]['enabled'] : $defaults['enabled'];
                $label   = isset( $overrides[ $key ]['label'] ) ? $overrides[ $key ]['label'] : $defaults['label'];
                ?>
                <tr>
                    <td><code><?php echo esc_html( $key ); ?></code></td>
                    <td>
                        <label>
                            <input type="checkbox"
                                name="<?php echo esc_attr( TAXA_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][enabled]"
                                value="1"
                                <?php checked( $enabled ); ?>
                            />
                        </label>
                    </td>
                    <td>
                        <input
                            type="text"
                            class="regular-text"
                            name="<?php echo esc_attr( TAXA_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][label]"
                            value="<?php echo esc_attr( $label ); ?>"
                        />
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function taxa_facets_color_section_intro() {
    echo '<p>' . esc_html__( 'Define the available color tokens and optionally exclude specific colors from display.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_color_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_COLOR_MAP_RAW,
        __( "red\ngreen\nblue", 'taxonomy-api' ),
        __( 'One slug per line. Order is important and should never change once used.', 'taxonomy-api' )
    );
}

function taxa_facets_color_exclusions_field_render() {
    $value = get_option( TAXA_FACETS_OPTION_EXCLUDED_COLORS, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( TAXA_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        id="<?php echo esc_attr( TAXA_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        rows="3"
        class="large-text"
        placeholder="<?php echo esc_attr__( "brown\nblack", 'taxonomy-api' ); ?>"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php esc_html_e( 'Excluded colors will be removed from the UI while still remaining in the map order.', 'taxonomy-api' ); ?></p>
    <?php
}

function taxa_facets_map_section_intro() {
    echo '<p>' . esc_html__( 'Maintain the facet slug lists that back the database enums and masks. Only append new values.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_maps_lock_field_render() {
    $locked = taxa_facets_maps_locked();
    ?>
    <label>
        <input type="checkbox"
            name="<?php echo esc_attr( TAXA_FACETS_OPTION_MAPS_LOCKED ); ?>"
            value="1"
            <?php checked( $locked ); ?>
        />
        <?php esc_html_e( 'Prevent map edits (including GPT seeding).', 'taxonomy-api' ); ?>
    </label>
    <?php
}

function taxa_facets_scope_list_field_render() {
    $value = get_option( TAXA_FACETS_OPTION_SCOPE_LIST, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( TAXA_FACETS_OPTION_SCOPE_LIST ); ?>"
        id="<?php echo esc_attr( TAXA_FACETS_OPTION_SCOPE_LIST ); ?>"
        rows="4"
        class="large-text"
        placeholder="<?php echo esc_attr__( "birds\nmammals\nplants", 'taxonomy-api' ); ?>"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php esc_html_e( 'Optional scopes (one per line) used to manage multiple sets of facet maps.', 'taxonomy-api' ); ?></p>
    <?php
}

function taxa_facets_map_scope_field_render() {
    $current = taxa_facets_get_map_scope();
    $scopes  = taxa_facets_allowed_scopes();
    ?>
    <select id="taxa_facets_map_scope" name="<?php echo esc_attr( TAXA_FACETS_OPTION_MAP_SCOPE ); ?>">
        <option value=""><?php esc_html_e( 'Site-wide (no scope)', 'taxonomy-api' ); ?></option>
        <?php foreach ( $scopes as $scope ) : ?>
            <option value="<?php echo esc_attr( $scope ); ?>" <?php selected( $current, $scope ); ?>>
                <?php echo esc_html( ucfirst( str_replace( '-', ' ', $scope ) ) ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e( 'Choose which scope you are editing or seeding with GPT.', 'taxonomy-api' ); ?></p>
    <?php
}

function taxa_facets_render_map_textarea( $option_key, $placeholder, $description, $rows = 6 ) {
    $scope = taxa_facets_get_map_scope();
    $scoped_key = taxa_facets_get_scoped_option_key( $option_key, $scope );
    $value = get_option( $scoped_key, '' );
    $locked = taxa_facets_maps_locked();
    ?>
    <textarea
        name="<?php echo esc_attr( $scoped_key ); ?>"
        id="<?php echo esc_attr( $scoped_key ); ?>"
        rows="<?php echo (int) $rows; ?>"
        class="large-text"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        <?php disabled( $locked ); ?>
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php echo esc_html( $description ); ?></p>
    <?php
}

function taxa_facets_size_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_SIZE_MAP_RAW,
        __( "tiny\nsmall\nmedium\nlarge", 'taxonomy-api' ),
        __( 'Size enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_shape_primary_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW,
        __( "slender\nrobust\nstocky", 'taxonomy-api' ),
        __( 'Primary shape enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_shape_secondary_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW,
        __( "short\nlong\nforked", 'taxonomy-api' ),
        __( 'Secondary shape enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_pattern_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_PATTERN_MAP_RAW,
        __( "striped\nspotted\nsolid", 'taxonomy-api' ),
        __( 'Pattern enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_trait_primary_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW,
        __( "crest\nspines\nhorns", 'taxonomy-api' ),
        __( 'Primary trait enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_trait_secondary_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW,
        __( "patterned_scales\nwebbed_feet", 'taxonomy-api' ),
        __( 'Secondary trait enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_diet_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_DIET_MAP_RAW,
        __( "herbivore\nomnivore\ncarnivore", 'taxonomy-api' ),
        __( 'Diet enum slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_behavior_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW,
        __( "nocturnal\nsolitary\nmigratory", 'taxonomy-api' ),
        __( 'Behavior bitmask slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_habitat_map_field_render() {
    taxa_facets_render_map_textarea(
        TAXA_FACETS_OPTION_HABITAT_MAP_RAW,
        __( "forest\nwetland\ndesert", 'taxonomy-api' ),
        __( 'Habitat bitmask slugs (one per line).', 'taxonomy-api' )
    );
}

function taxa_facets_overview_section_intro() {
    echo '<p>' . esc_html__( 'Snapshot of the current maps for the selected scope.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_overview_field_render() {
    $previous_scope = taxa_facets_get_current_scope();
    $map_scope = taxa_facets_get_map_scope();
    if ( $map_scope !== '' ) {
        taxa_facets_set_current_scope( $map_scope );
    }

    $overview = array(
        'Colors'           => taxa_facets_get_color_slugs(),
        'Size'             => taxa_facets_get_size_slugs(),
        'Primary shape'    => taxa_facets_get_shape_primary_slugs(),
        'Secondary shape'  => taxa_facets_get_shape_secondary_slugs(),
        'Pattern'          => taxa_facets_get_pattern_slugs(),
        'Primary trait'    => taxa_facets_get_trait_primary_slugs(),
        'Secondary trait'  => taxa_facets_get_trait_secondary_slugs(),
        'Diet'             => taxa_facets_get_diet_slugs(),
        'Behavior'         => taxa_facets_get_behavior_slugs(),
        'Habitat'          => taxa_facets_get_habitat_slugs(),
    );

    echo '<pre style="max-width: 960px; white-space: pre-wrap;">' . esc_html( wp_json_encode( $overview, JSON_PRETTY_PRINT ) ) . '</pre>';

    taxa_facets_set_current_scope( $previous_scope );
}

function taxa_facets_shortcode_section_intro() {
    echo '<p>' . esc_html__( 'Choose a default scope applied to the [taxa_explorer] shortcode.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_shortcode_scope_field_render() {
    $current = taxa_facets_get_shortcode_scope();
    $scopes  = taxa_facets_allowed_scopes();
    ?>
    <select id="taxa_facets_shortcode_scope" name="<?php echo esc_attr( TAXA_FACETS_OPTION_SHORTCODE_SCOPE ); ?>">
        <option value=""><?php esc_html_e( 'No default scope', 'taxonomy-api' ); ?></option>
        <?php foreach ( $scopes as $scope ) : ?>
            <option value="<?php echo esc_attr( $scope ); ?>" <?php selected( $current, $scope ); ?>>
                <?php echo esc_html( ucfirst( str_replace( '-', ' ', $scope ) ) ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function taxa_facets_seed_section_intro() {
    echo '<p>' . esc_html__( 'Generate facet maps using GPT, based on the Site Focus Keyword and selected scope.', 'taxonomy-api' ) . '</p>';
}

function taxa_facets_seed_prompt_field_render() {
    $default = taxa_facets_get_default_seed_prompt();
    $value = get_option( TAXA_FACETS_OPTION_SEED_PROMPT, $default );
    ?>
    <textarea
        name="<?php echo esc_attr( TAXA_FACETS_OPTION_SEED_PROMPT ); ?>"
        id="<?php echo esc_attr( TAXA_FACETS_OPTION_SEED_PROMPT ); ?>"
        rows="8"
        class="large-text"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">
        <?php esc_html_e( 'You can use placeholders: {focus}, {scope}.', 'taxonomy-api' ); ?>
    </p>
    <?php
}

function taxa_facets_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Taxa Facets', 'taxonomy-api' ); ?></h1>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url(
                    admin_url( 'admin-post.php?action=taxa_facets_seed_maps' ),
                    'taxa_facets_seed_maps'
                ) ); ?>">
                    <?php esc_html_e( 'Generate facet maps with GPT', 'taxonomy-api' ); ?>
                </a>
            </p>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'taxa_facets_maps_group' );
            do_settings_sections( 'taxa-facet-maps' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function taxa_facets_sanitize_scope_list( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }

    $slugs = taxa_facets_parse_slug_list( $value );
    return implode( "\n", $slugs );
}

function taxa_facets_sanitize_shortcode_scope( $value ) {
    return taxa_facets_sanitize_scope_value( $value );
}

function taxa_facets_sanitize_map_scope( $value ) {
    return taxa_facets_sanitize_scope_value( $value );
}

function taxa_facets_sanitize_excluded_colors( $value ) {
    return implode( "\n", taxa_facets_parse_slug_list( $value ) );
}

function taxa_facets_sanitize_locked_map_raw( $value, $option_key ) {
    if ( taxa_facets_maps_locked() ) {
        return get_option( $option_key, '' );
    }

    return implode( "\n", taxa_facets_parse_slug_list( $value ) );
}

function taxa_facets_sanitize_maps_locked( $value ) {
    return (bool) $value;
}

function taxa_facets_sanitize_color_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_COLOR_MAP_RAW );
}

function taxa_facets_sanitize_size_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_SIZE_MAP_RAW );
}

function taxa_facets_sanitize_shape_primary_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW );
}

function taxa_facets_sanitize_shape_secondary_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW );
}

function taxa_facets_sanitize_pattern_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_PATTERN_MAP_RAW );
}

function taxa_facets_sanitize_trait_primary_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW );
}

function taxa_facets_sanitize_trait_secondary_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW );
}

function taxa_facets_sanitize_diet_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_DIET_MAP_RAW );
}

function taxa_facets_sanitize_behavior_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW );
}

function taxa_facets_sanitize_habitat_map_raw( $value ) {
    return taxa_facets_sanitize_locked_map_raw( $value, TAXA_FACETS_OPTION_HABITAT_MAP_RAW );
}

function taxa_facets_get_default_seed_prompt() {
    return <<<PROMPT
You are generating facet map tokens for a taxonomy site.
Site focus: {focus}
Scope: {scope}

Return JSON with lower-case slugs only. Use these keys:
colors, sizes, shape_primary, shape_secondary, pattern, trait_primary, trait_secondary, diet, behavior, habitat.

Example response:
{
  "colors": ["green", "brown"],
  "sizes": ["small", "medium"],
  "shape_primary": ["slender", "robust"],
  "shape_secondary": ["short", "long"],
  "pattern": ["striped", "spotted"],
  "trait_primary": ["crested", "spined"],
  "trait_secondary": ["webbed_feet"],
  "diet": ["herbivore", "omnivore"],
  "behavior": ["nocturnal", "solitary"],
  "habitat": ["forest", "wetland"]
}
PROMPT;
}

function taxa_facets_build_seed_prompt( $focus_keyword, $scope ) {
    $template = get_option( TAXA_FACETS_OPTION_SEED_PROMPT, taxa_facets_get_default_seed_prompt() );
    $scope_label = $scope !== '' ? $scope : __( 'site-wide', 'taxonomy-api' );

    $prompt = str_replace(
        array( '{focus}', '{scope}' ),
        array( $focus_keyword, $scope_label ),
        $template
    );

    return $prompt;
}

function taxa_facets_normalize_slug_list( $values ) {
    if ( ! is_array( $values ) ) {
        return array();
    }

    $out = array();
    foreach ( $values as $value ) {
        $slug = sanitize_key( $value );
        if ( $slug !== '' ) {
            $out[] = $slug;
        }
    }

    return array_values( array_unique( $out ) );
}

function taxa_facets_update_map_from_gpt( $option_key, $values, $scope ) {
    $normalized = taxa_facets_normalize_slug_list( $values );
    if ( empty( $normalized ) ) {
        return;
    }

    $scoped_key = taxa_facets_get_scoped_option_key( $option_key, $scope );
    update_option( $scoped_key, implode( "\n", $normalized ) );
}

function taxa_facets_handle_gpt_seed_maps() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'taxonomy-api' ) );
    }

    check_admin_referer( 'taxa_facets_seed_maps' );

    if ( taxa_facets_maps_locked() ) {
        wp_safe_redirect(
            add_query_arg(
                'taxa_facets_seed_status',
                'locked',
                admin_url( 'options-general.php?page=taxa-facet-maps' )
            )
        );
        exit;
    }

    $gpt_enabled = get_option( 'gpt_enabled', '0' );
    if ( ! in_array( $gpt_enabled, array( 'true', true, '1', 1 ), true ) ) {
        wp_safe_redirect(
            add_query_arg(
                'taxa_facets_seed_status',
                'disabled',
                admin_url( 'options-general.php?page=taxa-facet-maps' )
            )
        );
        exit;
    }

    $focus_keyword = get_option( 'site_focus_keyword', '' );
    if ( $focus_keyword === '' ) {
        wp_safe_redirect(
            add_query_arg(
                'taxa_facets_seed_status',
                'missing',
                admin_url( 'options-general.php?page=taxa-facet-maps' )
            )
        );
        exit;
    }

    $map_scope = taxa_facets_get_map_scope();
    $prompt = taxa_facets_build_seed_prompt( $focus_keyword, $map_scope );
    $response = get_gpt_response( $prompt, 'gpt-4o-mini' );

    if ( ! $response ) {
        wp_safe_redirect(
            add_query_arg(
                'taxa_facets_seed_status',
                'empty',
                admin_url( 'options-general.php?page=taxa-facet-maps' )
            )
        );
        exit;
    }

    $raw = trim( (string) $response );
    if ( strpos( $raw, '```' ) === 0 ) {
        $raw = preg_replace( '#^```(?:json)?#i', '', $raw );
        $raw = preg_replace( '#```$#', '', $raw );
        $raw = trim( $raw );
    }

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) {
        wp_safe_redirect(
            add_query_arg(
                'taxa_facets_seed_status',
                'invalid',
                admin_url( 'options-general.php?page=taxa-facet-maps' )
            )
        );
        exit;
    }

    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_COLOR_MAP_RAW, $data['colors'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_SIZE_MAP_RAW, $data['sizes'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW, $data['shape_primary'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW, $data['shape_secondary'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_PATTERN_MAP_RAW, $data['pattern'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW, $data['trait_primary'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW, $data['trait_secondary'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_DIET_MAP_RAW, $data['diet'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_BEHAVIOR_MAP_RAW, $data['behavior'] ?? array(), $map_scope );
    taxa_facets_update_map_from_gpt( TAXA_FACETS_OPTION_HABITAT_MAP_RAW, $data['habitat'] ?? array(), $map_scope );

    wp_safe_redirect(
        add_query_arg(
            'taxa_facets_seed_status',
            'success',
            admin_url( 'options-general.php?page=taxa-facet-maps' )
        )
    );
    exit;
}
add_action( 'admin_post_taxa_facets_seed_maps', 'taxa_facets_handle_gpt_seed_maps' );

function taxa_facets_seed_maps_admin_notice() {
    if ( ! isset( $_GET['page'], $_GET['taxa_facets_seed_status'] ) || $_GET['page'] !== 'taxa-facet-maps' ) {
        return;
    }

    $status = sanitize_text_field( wp_unslash( $_GET['taxa_facets_seed_status'] ) );
    $messages = array(
        'success'  => __( 'Facet maps updated from GPT.', 'taxonomy-api' ),
        'locked'   => __( 'Facet maps are locked. Unlock to seed.', 'taxonomy-api' ),
        'missing'  => __( 'Site Focus Keyword is missing. Set it before seeding.', 'taxonomy-api' ),
        'disabled' => __( 'GPT is disabled. Enable it before seeding.', 'taxonomy-api' ),
        'empty'    => __( 'GPT response was empty.', 'taxonomy-api' ),
        'invalid'  => __( 'GPT response could not be parsed.', 'taxonomy-api' ),
    );

    if ( isset( $messages[ $status ] ) ) {
        $class = ( $status === 'success' ) ? 'notice notice-success' : 'notice notice-error';
        printf(
            '<div class="%s"><p>%s</p></div>',
            esc_attr( $class ),
            esc_html( $messages[ $status ] )
        );
    }
}
add_action( 'admin_notices', 'taxa_facets_seed_maps_admin_notice' );
