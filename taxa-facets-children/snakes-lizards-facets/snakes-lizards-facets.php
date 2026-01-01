<?php
/**
 * Plugin Name: Snakes & Lizards – Taxonomy API Facets
 * Description: Snakes & Lizards facet maps (size, body form, tail features, pattern, lifestyle, activity, diet/prey, venomous) for the Taxonomy API plugin.
 * Author: Brandon Bartlett
 * Version: 1.2.2
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Text Domain: snakes-lizards-facets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'snakes_lizards_facets_enum_options_from_map' ) ) {
    /**
     * Build enum/bitmask option arrays from a slug=>id (or slug=>bit) map.
     * Optionally restrict to a whitelist of slugs.
     */
    function snakes_lizards_facets_enum_options_from_map( $map, $allowed_slugs = array() ) {
        $out = array();

        foreach ( $map as $slug => $id ) {
            if ( ! empty( $allowed_slugs ) && ! in_array( $slug, $allowed_slugs, true ) ) {
                continue;
            }

            $out[] = array(
                'slug'  => $slug,
                'label' => function_exists( 'taxa_facets_pretty_slug' ) ? taxa_facets_pretty_slug( $slug ) : ucwords( str_replace( array( '_', '-' ), ' ', $slug ) ),
            );
        }

        return $out;
    }
}

/**
 * Option keys (site-scoped in multisite).
 */
define( 'SNL_FACETS_OPTION_LABEL_MAP_RAW', 'snl_facets_label_map_raw' );
define( 'SNL_FACETS_OPTION_UI_OVERRIDES', 'snl_facets_ui_overrides' );
define( 'SNL_FACETS_OPTION_EXCLUDED_COLORS', 'snl_facets_excluded_colors' );
define( 'SNL_FACETS_OPTION_COLOR_MAP_RAW', 'snl_facets_color_map_raw' );
define( 'SNL_FACETS_OPTION_MAPS_LOCKED', 'snl_facets_maps_locked' );
define( 'SNL_FACETS_OPTION_SIZE_MAP_RAW', 'snl_facets_size_map_raw' );
define( 'SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW', 'snl_facets_shape_primary_map_raw' );
define( 'SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW', 'snl_facets_shape_secondary_map_raw' );
define( 'SNL_FACETS_OPTION_PATTERN_MAP_RAW', 'snl_facets_pattern_map_raw' );
define( 'SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW', 'snl_facets_trait_primary_map_raw' );
define( 'SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW', 'snl_facets_trait_secondary_map_raw' );
define( 'SNL_FACETS_OPTION_DIET_MAP_RAW', 'snl_facets_diet_map_raw' );
define( 'SNL_FACETS_OPTION_VENOMOUS_MAP_RAW', 'snl_facets_venomous_map_raw' );
define( 'SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW', 'snl_facets_behavior_map_raw' );
define( 'SNL_FACETS_OPTION_HABITAT_MAP_RAW', 'snl_facets_habitat_map_raw' );
define( 'SNL_FACETS_OPTION_SHORTCODE_SCOPE', 'snl_facets_shortcode_scope' );
define( 'SNL_FACETS_OPTION_MAP_SCOPE', 'snl_facets_map_scope' );

/**
 * Init after parent plugin loads.
 */
add_action( 'plugins_loaded', 'snakes_lizards_facets_init', 20 );
function snakes_lizards_facets_init() {

    // Parent plugin facet helpers must exist.
    if ( ! function_exists( 'taxa_facets_get_color_map' ) ) {
        add_action( 'admin_notices', 'snakes_lizards_facets_missing_parent_notice' );
        return;
    }

    snakes_lizards_facets_register_maps();
}

/**
 * Admin notice if parent plugin isn't loaded.
 */
function snakes_lizards_facets_missing_parent_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__(
        'Snakes & Lizards – Taxonomy API Facets requires the "Taxonomy API" plugin to be active.',
        'snakes-lizards-facets'
    );
    echo '</p></div>';
}

/**
 * Snakes & Lizards-specific maps and aliases (used in filters + admin overview).
 */
function snl_facets_color_aliases() {
    return array(
        'olive'        => 'green',
        'tan'          => 'brown',
        'sand'         => 'tan',
        'sandy'        => 'tan',
        'cream'        => 'white',
        'offwhite'     => 'white',
        'off white'    => 'white',
        'off-white'    => 'white',
        'charcoal'     => 'black',
        'bronze'       => 'brown',
        'rust'         => 'orange',
        'reddish'      => 'red',
        'bluish'       => 'blue',
        'grayish'      => 'gray',
        'grey'         => 'gray',
        'gold'         => 'yellow',
        'golden'       => 'yellow',
    );
}

function snl_facets_maps_locked() {
    return (bool) get_option( SNL_FACETS_OPTION_MAPS_LOCKED, false );
}

function snl_facets_allowed_scopes() {
    return array( 'snakes', 'lizards', 'amphibians' );
}

function snl_facets_sanitize_scope_value( $value ) {
    $value = is_string( $value ) ? sanitize_key( $value ) : '';
    if ( '' === $value ) {
        return '';
    }

    return in_array( $value, snl_facets_allowed_scopes(), true ) ? $value : '';
}

function snl_facets_get_map_scope() {
    return snl_facets_sanitize_scope_value( get_option( SNL_FACETS_OPTION_MAP_SCOPE, '' ) );
}

function snl_facets_get_scoped_option_key( $base_key, $scope ) {
    $scope = snl_facets_sanitize_scope_value( $scope );
    if ( '' === $scope ) {
        return $base_key;
    }

    return $base_key . '_' . $scope;
}

function snl_facets_get_scoped_option_value( $base_key, $scope ) {
    $scoped_key = snl_facets_get_scoped_option_key( $base_key, $scope );
    $value = get_option( $scoped_key, '' );

    if ( $value === '' && $scoped_key !== $base_key ) {
        $value = get_option( $base_key, '' );
    }

    return $value;
}

function snl_facets_parse_slug_list( $raw ) {
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

function snl_facets_build_enum_map( array $slugs ) {
    $map = array();

    foreach ( $slugs as $index => $slug ) {
        $map[ $slug ] = $index + 1;
    }

    return $map;
}

function snl_facets_build_bitmask_map( array $slugs ) {
    $map = array();

    foreach ( $slugs as $index => $slug ) {
        $map[ $slug ] = 1 << $index;
    }

    return $map;
}

function snl_facets_default_color_slugs() {
    return array(
        'red',
        'orange',
        'yellow',
        'green',
        'olive',
        'blue',
        'purple',
        'white',
        'tan',
        'gray',
        'pink',
        'brown',
        'black',
        'teal',
        'cyan',
        'navy',
        'gold',
        'rust',
        'iridescent',
        'patterned',
    );
}

function snl_facets_default_size_slugs() {
    return array(
        'tiny',
        'small',
        'medium',
        'large',
        'very_large',
    );
}

function snl_facets_default_shape_primary_slugs() {
    return array(
        'slender',
        'robust',
        'stocky',
        'bulky',
        'flattened',
        'heavy_bodied',
        'laterally_compressed',
    );
}

function snl_facets_default_shape_secondary_slugs() {
    return array(
        'short',
        'long',
        'prehensile',
        'reduced',
        'clubbed',
        'rattle',
        'blunt',
        'tapered',
    );
}

function snl_facets_default_pattern_slugs() {
    return array(
        'solid',
        'spotted',
        'striped',
        'banded',
        'blotched',
        'mottled',
        'speckled',
        'reticulated',
    );
}

function snl_facets_default_trait_primary_slugs() {
    return array(
        'terrestrial',
        'arboreal',
        'fossorial',
        'aquatic',
        'semi_aquatic',
        'saxicolous',
    );
}

function snl_facets_default_trait_secondary_slugs() {
    return array(
        'diurnal',
        'nocturnal',
        'crepuscular',
    );
}

function snl_facets_default_diet_slugs() {
    return array(
        'carnivore',
        'insectivore',
        'omnivore',
        'herbivore',
        'piscivore',
        'mammals',
        'birds',
        'reptiles',
        'amphibians',
        'fish',
        'eggs',
        'invertebrates',
    );
}

function snl_facets_default_venomous_slugs() {
    return array(
        'venomous',
        'non_venomous',
        'mildly_venomous',
    );
}

function snl_facets_default_behavior_slugs() {
    return array(
        'constricting',
        'venom_strike',
        'tail_autotomy',
        'gliding',
        'chameleon_like',
    );
}

function snl_facets_default_habitat_slugs() {
    return array(
        'montane',
        'coastal',
    );
}

function snl_facets_get_color_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_COLOR_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_color_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_get_color_map() {
    $slugs = snl_facets_get_color_slugs();
    return snl_facets_build_bitmask_map( $slugs );
}

function snl_facets_get_size_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_SIZE_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_size_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_size_map() {
    return snl_facets_build_enum_map( snl_facets_get_size_slugs() );
}

function snl_facets_get_shape_primary_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_shape_primary_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_shape_primary_map() {
    return snl_facets_build_enum_map( snl_facets_get_shape_primary_slugs() );
}

function snl_facets_get_shape_secondary_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_shape_secondary_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_shape_secondary_map() {
    return snl_facets_build_enum_map( snl_facets_get_shape_secondary_slugs() );
}

function snl_facets_get_pattern_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_PATTERN_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_pattern_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_pattern_map() {
    return snl_facets_build_enum_map( snl_facets_get_pattern_slugs() );
}

function snl_facets_get_trait_primary_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_trait_primary_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_trait_primary_map() {
    return snl_facets_build_enum_map( snl_facets_get_trait_primary_slugs() );
}

function snl_facets_get_trait_secondary_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_trait_secondary_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_trait_secondary_map() {
    return snl_facets_build_enum_map( snl_facets_get_trait_secondary_slugs() );
}

function snl_facets_get_diet_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_DIET_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_diet_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_diet_map() {
    return snl_facets_build_enum_map( snl_facets_get_diet_slugs() );
}

function snl_facets_get_venomous_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_VENOMOUS_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_venomous_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_venomous_map() {
    return snl_facets_build_enum_map( snl_facets_get_venomous_slugs() );
}

function snl_facets_get_behavior_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_behavior_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_behavior_map() {
    return snl_facets_build_bitmask_map( snl_facets_get_behavior_slugs() );
}

function snl_facets_get_habitat_slugs() {
    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $raw = snl_facets_get_scoped_option_value( SNL_FACETS_OPTION_HABITAT_MAP_RAW, $scope );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return snl_facets_default_habitat_slugs();
    }

    return snl_facets_parse_slug_list( $raw );
}

function snl_facets_habitat_map() {
    return snl_facets_build_bitmask_map( snl_facets_get_habitat_slugs() );
}

/**
 * Register all Snakes/Lizards facet maps & aliases.
 *
 * IMPORTANT:
 *  - Once live on production with real data, DO NOT REORDER arrays.
 *  - Only APPEND new values at the end to preserve enum IDs / bit positions.
 *
 * NOTE:
 *  - Parent includes/includes/facets.php provides the Habitat + Behavior core getters.
 *  - This child plugin may EXTEND the maps via filters (that's correct),
 *    but the FRONTEND UI should NOT create duplicate sections. We only
 *    scope-restrict options on the existing sections in the frontend config.
 */
function snakes_lizards_facets_register_maps() {

    /**
     * ---------------------------------
     * COLOR: reptile-friendly aliases
     * ---------------------------------
     */
    add_filter( 'taxa_facets_color_aliases', function( $aliases ) {

        return array_merge( $aliases, snl_facets_color_aliases() );
    } );

    add_filter( 'taxa_facets_color_map', 'snl_facets_provide_color_map', 5 );
    add_filter( 'taxa_facets_color_map', 'snl_facets_filter_color_map', 20 );

    /**
     * ---------------------------------
     * SIZE (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_size_enum_map', 'snl_facets_provide_size_map', 5 );

    /**
     * ---------------------------------
     * SHAPE PRIMARY (enum) – body build / form
     * ---------------------------------
     */
    add_filter( 'taxa_facets_shape_primary_enum_map', 'snl_facets_provide_shape_primary_map', 5 );

    /**
     * ---------------------------------
     * SHAPE SECONDARY (enum) – tail feature/type
     * ---------------------------------
     */
    add_filter( 'taxa_facets_shape_secondary_enum_map', 'snl_facets_provide_shape_secondary_map', 5 );

    /**
     * ---------------------------------
     * PATTERN (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_pattern_enum_map', 'snl_facets_provide_pattern_map', 5 );

    /**
     * ---------------------------------
     * TRAIT PRIMARY (enum) – lifestyle/ecology
     * ---------------------------------
     */
    add_filter( 'taxa_facets_trait_primary_enum_map', 'snl_facets_provide_trait_primary_map', 5 );

    /**
     * ---------------------------------
     * TRAIT SECONDARY (enum) – activity pattern
     * ---------------------------------
     */
    add_filter( 'taxa_facets_trait_secondary_enum_map', 'snl_facets_provide_trait_secondary_map', 5 );

    /**
     * ---------------------------------
     * DIET (enum)
     * ---------------------------------
     * Keep broad tokens for lizards; APPEND prey-type tokens for snakes.
     */
    add_filter( 'taxa_facets_diet_enum_map', 'snl_facets_provide_diet_map', 5 );

    /**
     * ---------------------------------
     * VENOMOUS (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_venomous_enum_map', 'snl_facets_provide_venomous_map', 5 );

    /**
     * ---------------------------------
     * BEHAVIOR (bitmask) – extend core map, but UI will not duplicate section
     * ---------------------------------
     */
    add_filter( 'taxa_facets_behavior_map', 'snl_facets_provide_behavior_map', 5 );

    /**
     * ---------------------------------
     * HABITAT (bitmask) – extend core map, but UI will not duplicate section
     * ---------------------------------
     */
    add_filter( 'taxa_facets_habitat_map', 'snl_facets_provide_habitat_map', 5 );
}

/**
 * Override the generic taxa_facets_gpt_prompt with a Snakes/Lizards-specific one.
 */
add_filter( 'taxa_facets_gpt_prompt', 'snakes_lizards_facets_customize_gpt_prompt', 10, 3 );

function snakes_lizards_facets_customize_gpt_prompt( $prompt, $title, array $lists ) {

    $colors           = $lists['colors'];
    $sizes            = $lists['sizes'];
    $shape_primary    = $lists['shape_primary'];
    $shape_secondary  = $lists['shape_secondary'];
    $patterns         = $lists['patterns'];
    $traits_primary   = $lists['traits_primary'];
    $traits_secondary = $lists['traits_secondary'];
    $diets            = $lists['diets'];
    $behaviors        = $lists['behaviors'];
    $habitats         = $lists['habitats'];
    $families         = $lists['families'];
    $regions          = $lists['regions'];

    $venomous = isset( $lists['venomous'] ) ? $lists['venomous'] : '"venomous", "non_venomous", "mildly_venomous"';

    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';

    $diet_allowed_snakes  = array( 'mammals', 'birds', 'reptiles', 'amphibians', 'fish', 'eggs' );
    $diet_allowed_lizards = array( 'insectivore', 'herbivore', 'omnivore', 'carnivore', 'invertebrates', 'fish', 'eggs' );

    $behavior_allowed_snakes = array( 'solitary', 'burrowing', 'climbing', 'basking', 'ambush', 'active_hunter', 'constricting', 'venom_strike' );
    $behavior_allowed_lizards = array( 'solitary', 'burrowing', 'climbing', 'basking', 'active_hunter', 'tail_autotomy', 'gliding', 'chameleon_like' );

    $tail_allowed_snakes  = array( 'prehensile', 'rattle', 'tapered', 'blunt' );
    $tail_allowed_lizards = array( 'clubbed', 'prehensile', 'short', 'long', 'reduced', 'tapered', 'blunt' );

    $shape_allowed_snakes  = array( 'slender', 'robust', 'heavy_bodied', 'laterally_compressed', 'flattened' );
    $shape_allowed_lizards = array( 'slender', 'robust', 'stocky', 'bulky', 'flattened', 'laterally_compressed' );

    $diet_scope_list = ( 'snakes' === $scope )
        ? '"' . implode( '", "', $diet_allowed_snakes ) . '"'
        : '"' . implode( '", "', $diet_allowed_lizards ) . '"';

    $behavior_scope_list = ( 'snakes' === $scope )
        ? '"' . implode( '", "', $behavior_allowed_snakes ) . '"'
        : '"' . implode( '", "', $behavior_allowed_lizards ) . '"';

    $tail_scope_list = ( 'snakes' === $scope )
        ? '"' . implode( '", "', $tail_allowed_snakes ) . '"'
        : '"' . implode( '", "', $tail_allowed_lizards ) . '"';

    $shape_scope_list = ( 'snakes' === $scope )
        ? '"' . implode( '", "', $shape_allowed_snakes ) . '"'
        : '"' . implode( '", "', $shape_allowed_lizards ) . '"';

    $diet_label = ( 'snakes' === $scope ) ? 'primary prey' : 'diet';

    $prompt = <<<EOP
You are a herpetology assistant helping build an interactive field guide
for SnakesAndLizards.com.

Your job is to assign **concise facet tags** to each snake or lizard species
using ONLY the allowed tokens listed below. These facets power filters such as
size, body form, tail features, pattern, lifestyle/ecology, activity,
diet/prey, behavior, habitat, and whether the animal is venomous.

CRITICAL RULES:

1. **Only use tokens from the allowed lists below.**
   - Do NOT invent new tokens.
   - If no suitable token exists, use null (for single-value fields) or [] (for arrays).

2. Scope matters:
   - If the scope is snakes, diet MUST be a prey-type token (mammals/birds/reptiles/amphibians/fish/eggs).
   - If the scope is lizards, diet MAY be insectivore/herbivore/omnivore/carnivore (or other listed tokens).

3. Be conservative:
   - Base choices on a typical adult of the species.
   - If unsure, prefer null or [] rather than guessing.
   - Choose only the most characteristic traits.

FIELD MEANINGS:
- size: overall body size category.
- shape_primary: body form/build (scope-restricted).
- shape_secondary: tail feature/type (scope-restricted).
- pattern: main visible pattern.
- trait_primary: primary lifestyle/ecology niche.
- trait_secondary: activity period.
- diet: {$diet_label}.
- colors: main visible colors.
- behavior: notable behaviors (scope-restricted).
- habitat: typical habitats.
- venomous: whether the species is venomous, mildly venomous, or non-venomous.
- family/region: only if a matching token exists (may be blank).

ALLOWED TOKENS (choose ONLY from these):

- colors (multi): {$colors}
- size (single): {$sizes}

- shape_primary (single) – SCOPE RESTRICTED:
  {$shape_scope_list}

- shape_secondary (single) – SCOPE RESTRICTED:
  {$tail_scope_list}

- pattern (single): {$patterns}
- trait_primary (single): {$traits_primary}
- trait_secondary (single): {$traits_secondary}

- diet (single) – SCOPE RESTRICTED:
  {$diet_scope_list}

- behavior (multi) – SCOPE RESTRICTED:
  {$behavior_scope_list}

- habitat (multi): {$habitats}
- venomous (single): {$venomous}
- family (single): {$families}
- region (single): {$regions}

OUTPUT FORMAT (STRICT JSON ONLY):

{
  "size": "size_token or null",
  "shape_primary": "shape_primary_token or null",
  "shape_secondary": "shape_secondary_token or null",
  "pattern": "pattern_token or null",
  "trait_primary": "trait_primary_token or null",
  "trait_secondary": "trait_secondary_token or null",
  "diet": "diet_token or null",
  "colors": ["color_token1", "color_token2"],
  "behavior": ["behavior_token1", "behavior_token2"],
  "habitat": ["habitat_token1", "habitat_token2"],
  "venomous": "venomous_token or null",
  "family": "family_token or null",
  "region": "region_token or null"
}

If a field has no suitable token, use null (single) or [] (multi).

Now assign facets for this species:

{$title}
EOP;

    return $prompt;
}

/**
 * Front-end facets UI config (scope-aware)
 *
 * IMPORTANT FIX:
 * - Parent already creates behavior/habitat sections when those facets are enabled.
 * - This child MUST NOT add new sections unconditionally.
 * - We ONLY modify existing sections (label/enabled/options), or create them
 *   ONLY if the parent did not define them.
 */
add_filter( 'taxa_facets_frontend_options', 'snakes_lizards_facets_frontend_options' );
function snakes_lizards_facets_frontend_options( $config ) {

    $scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $overrides = snl_facets_get_ui_overrides();
    $excluded_colors = snl_facets_get_excluded_colors();

    $enabled_by_scope = array(
        'snakes' => array(
            'size'           => true,
            'shape_primary'   => true,
            'shape_secondary' => true,
            'pattern'         => true,
            'trait_primary'   => true,
            'trait_secondary' => true,
            'diet'            => true,
            'behavior'        => true,
            'habitat'         => true,
            'venomous'        => true,
        ),
        'lizards' => array(
            'size'           => true,
            'shape_primary'   => true,
            'shape_secondary' => true,
            'pattern'         => true,
            'trait_primary'   => true,
            'trait_secondary' => true,
            'diet'            => true,
            'behavior'        => true,
            'habitat'         => true,
            'venomous'        => true,
        ),
    );

    $labels_by_scope = array(
        'snakes' => array(
            'shape_primary'   => 'Body Form',
            'shape_secondary' => 'Tail Feature',
            'trait_primary'   => 'Lifestyle',
            'trait_secondary' => 'Activity',
            'diet'            => 'Primary Prey',
            'behavior'        => 'Behavior',
            'habitat'         => 'Habitat',
            'venomous'        => 'Venom',
        ),
        'lizards' => array(
            'shape_primary'   => 'Body Form',
            'shape_secondary' => 'Tail Type',
            'trait_primary'   => 'Lifestyle',
            'trait_secondary' => 'Activity',
            'diet'            => 'Diet',
            'behavior'        => 'Behavior',
            'habitat'         => 'Habitat',
            'venomous'        => 'Venom',
        ),
    );

    $scope_labels  = isset( $labels_by_scope[ $scope ] ) ? $labels_by_scope[ $scope ] : array();
    $scope_enabled = isset( $enabled_by_scope[ $scope ] ) ? $enabled_by_scope[ $scope ] : array();

    // SIZE (keep parent config but ensure label/enabled)
    if ( isset( $config['size'] ) ) {
        $config['size']['enabled'] = snl_facets_get_override_enabled(
            'size',
            isset( $scope_enabled['size'] ) ? (bool) $scope_enabled['size'] : ( isset( $config['size']['enabled'] ) ? (bool) $config['size']['enabled'] : true ),
            $overrides
        );
        $config['size']['label']   = snl_facets_get_override_label( 'size', 'Size', $overrides );
    }

    // SHAPE PRIMARY (scope-restricted)
    $shape_primary_map = function_exists( 'taxa_facets_get_shape_primary_enum_map' ) ? taxa_facets_get_shape_primary_enum_map() : array();
    if ( ! empty( $shape_primary_map ) ) {
        $allowed = array();
        if ( 'snakes' === $scope ) {
            $allowed = array( 'slender', 'robust', 'heavy_bodied', 'laterally_compressed', 'flattened' );
        } elseif ( 'lizards' === $scope ) {
            $allowed = array( 'slender', 'robust', 'stocky', 'bulky', 'flattened', 'laterally_compressed' );
        }

        $config['shape_primary'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'shape_primary',
                isset( $scope_enabled['shape_primary'] ) ? (bool) $scope_enabled['shape_primary'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label(
                'shape_primary',
                isset( $scope_labels['shape_primary'] ) ? $scope_labels['shape_primary'] : 'Body Form',
                $overrides
            ),
            'key'     => 'shape_primary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $shape_primary_map, $allowed ),
        );
    }

    // SHAPE SECONDARY (scope-restricted)
    $shape_secondary_map = function_exists( 'taxa_facets_get_shape_secondary_enum_map' ) ? taxa_facets_get_shape_secondary_enum_map() : array();
    if ( ! empty( $shape_secondary_map ) ) {
        $allowed = array();
        if ( 'snakes' === $scope ) {
            $allowed = array( 'prehensile', 'rattle', 'blunt', 'tapered' );
        } elseif ( 'lizards' === $scope ) {
            $allowed = array( 'clubbed', 'prehensile', 'short', 'long', 'reduced', 'blunt', 'tapered' );
        }

        $config['shape_secondary'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'shape_secondary',
                isset( $scope_enabled['shape_secondary'] ) ? (bool) $scope_enabled['shape_secondary'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label(
                'shape_secondary',
                isset( $scope_labels['shape_secondary'] ) ? $scope_labels['shape_secondary'] : 'Tail',
                $overrides
            ),
            'key'     => 'shape_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $shape_secondary_map, $allowed ),
        );
    }

    // PATTERN
    $pattern_map = function_exists( 'taxa_facets_get_pattern_enum_map' ) ? taxa_facets_get_pattern_enum_map() : array();
    if ( ! empty( $pattern_map ) ) {
        $config['pattern'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'pattern',
                isset( $scope_enabled['pattern'] ) ? (bool) $scope_enabled['pattern'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label( 'pattern', 'Pattern', $overrides ),
            'key'     => 'pattern',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $pattern_map ),
        );
    }

    // PRIMARY TRAIT
    $trait_primary_map = function_exists( 'taxa_facets_get_trait_primary_enum_map' ) ? taxa_facets_get_trait_primary_enum_map() : array();
    if ( ! empty( $trait_primary_map ) ) {
        $config['trait_primary'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'trait_primary',
                isset( $scope_enabled['trait_primary'] ) ? (bool) $scope_enabled['trait_primary'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label(
                'trait_primary',
                isset( $scope_labels['trait_primary'] ) ? $scope_labels['trait_primary'] : 'Lifestyle',
                $overrides
            ),
            'key'     => 'trait_primary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $trait_primary_map ),
        );
    }

    // SECONDARY TRAIT
    $trait_secondary_map = function_exists( 'taxa_facets_get_trait_secondary_enum_map' ) ? taxa_facets_get_trait_secondary_enum_map() : array();
    if ( ! empty( $trait_secondary_map ) ) {
        $config['trait_secondary'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'trait_secondary',
                isset( $scope_enabled['trait_secondary'] ) ? (bool) $scope_enabled['trait_secondary'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label(
                'trait_secondary',
                isset( $scope_labels['trait_secondary'] ) ? $scope_labels['trait_secondary'] : 'Activity',
                $overrides
            ),
            'key'     => 'trait_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $trait_secondary_map ),
        );
    }

    // DIET (scope-restricted)
    $diet_map = function_exists( 'taxa_facets_get_diet_enum_map' ) ? taxa_facets_get_diet_enum_map() : array();
    if ( ! empty( $diet_map ) ) {

        $allowed = array();
        if ( 'snakes' === $scope ) {
            $allowed = array( 'mammals', 'birds', 'reptiles', 'amphibians', 'fish', 'eggs' );
        } elseif ( 'lizards' === $scope ) {
            $allowed = array( 'insectivore', 'herbivore', 'omnivore', 'carnivore', 'invertebrates', 'fish', 'eggs' );
        }

        $config['diet'] = array(
            'enabled' => snl_facets_get_override_enabled(
                'diet',
                isset( $scope_enabled['diet'] ) ? (bool) $scope_enabled['diet'] : true,
                $overrides
            ),
            'label'   => snl_facets_get_override_label(
                'diet',
                isset( $scope_labels['diet'] ) ? $scope_labels['diet'] : 'Diet',
                $overrides
            ),
            'key'     => 'diet',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $diet_map, $allowed ),
        );
    }

    /**
     * BEHAVIOR (bitmask) – MODIFY EXISTING SECTION if parent created it,
     * otherwise create it.
     */
    $behavior_map = function_exists( 'taxa_facets_get_behavior_map' ) ? taxa_facets_get_behavior_map() : array();
    if ( ! empty( $behavior_map ) ) {

        $allowed = array();
        if ( 'snakes' === $scope ) {
            $allowed = array( 'solitary', 'burrowing', 'climbing', 'basking', 'ambush', 'active_hunter', 'constricting', 'venom_strike' );
        } elseif ( 'lizards' === $scope ) {
            $allowed = array( 'solitary', 'burrowing', 'climbing', 'basking', 'active_hunter', 'tail_autotomy', 'gliding', 'chameleon_like' );
        }

        if ( isset( $config['behavior'] ) && is_array( $config['behavior'] ) ) {
            // Modify parent-defined behavior section (NO DUPLICATE)
            $config['behavior']['enabled'] = snl_facets_get_override_enabled(
                'behavior',
                isset( $scope_enabled['behavior'] ) ? (bool) $scope_enabled['behavior'] : ( isset( $config['behavior']['enabled'] ) ? (bool) $config['behavior']['enabled'] : true ),
                $overrides
            );
            $config['behavior']['label']   = snl_facets_get_override_label(
                'behavior',
                isset( $scope_labels['behavior'] ) ? $scope_labels['behavior'] : ( isset( $config['behavior']['label'] ) ? $config['behavior']['label'] : 'Behavior' ),
                $overrides
            );
            $config['behavior']['options'] = snakes_lizards_facets_enum_options_from_map( $behavior_map, $allowed );
            $config['behavior']['key']     = isset( $config['behavior']['key'] ) ? $config['behavior']['key'] : 'behavior';
            $config['behavior']['type']    = isset( $config['behavior']['type'] ) ? $config['behavior']['type'] : 'bitmask';
            $config['behavior']['multi']   = isset( $config['behavior']['multi'] ) ? (bool) $config['behavior']['multi'] : true;
        } else {
            // Parent did not define it; create it
            $config['behavior'] = array(
                'enabled' => snl_facets_get_override_enabled(
                    'behavior',
                    isset( $scope_enabled['behavior'] ) ? (bool) $scope_enabled['behavior'] : true,
                    $overrides
                ),
                'label'   => snl_facets_get_override_label(
                    'behavior',
                    isset( $scope_labels['behavior'] ) ? $scope_labels['behavior'] : 'Behavior',
                    $overrides
                ),
                'key'     => 'behavior',
                'type'    => 'bitmask',
                'multi'   => true,
                'options' => snakes_lizards_facets_enum_options_from_map( $behavior_map, $allowed ),
            );
        }
    }

    /**
     * HABITAT (bitmask) – MODIFY EXISTING SECTION if parent created it,
     * otherwise create it.
     */
    $habitat_map = function_exists( 'taxa_facets_get_habitat_map' ) ? taxa_facets_get_habitat_map() : array();
    if ( ! empty( $habitat_map ) ) {

        if ( isset( $config['habitat'] ) && is_array( $config['habitat'] ) ) {
            // Modify parent-defined habitat section (NO DUPLICATE)
            $config['habitat']['enabled'] = snl_facets_get_override_enabled(
                'habitat',
                isset( $scope_enabled['habitat'] ) ? (bool) $scope_enabled['habitat'] : ( isset( $config['habitat']['enabled'] ) ? (bool) $config['habitat']['enabled'] : true ),
                $overrides
            );
            $config['habitat']['label']   = snl_facets_get_override_label(
                'habitat',
                isset( $scope_labels['habitat'] ) ? $scope_labels['habitat'] : ( isset( $config['habitat']['label'] ) ? $config['habitat']['label'] : 'Habitat' ),
                $overrides
            );
            $config['habitat']['options'] = snakes_lizards_facets_enum_options_from_map( $habitat_map );
            $config['habitat']['key']     = isset( $config['habitat']['key'] ) ? $config['habitat']['key'] : 'habitat';
            $config['habitat']['type']    = isset( $config['habitat']['type'] ) ? $config['habitat']['type'] : 'bitmask';
            $config['habitat']['multi']   = isset( $config['habitat']['multi'] ) ? (bool) $config['habitat']['multi'] : true;
        } else {
            // Parent did not define it; create it
            $config['habitat'] = array(
                'enabled' => snl_facets_get_override_enabled(
                    'habitat',
                    isset( $scope_enabled['habitat'] ) ? (bool) $scope_enabled['habitat'] : true,
                    $overrides
                ),
                'label'   => snl_facets_get_override_label(
                    'habitat',
                    isset( $scope_labels['habitat'] ) ? $scope_labels['habitat'] : 'Habitat',
                    $overrides
                ),
                'key'     => 'habitat',
                'type'    => 'bitmask',
                'multi'   => true,
                'options' => snakes_lizards_facets_enum_options_from_map( $habitat_map ),
            );
        }
    }

    // VENOMOUS
    $venomous_map = function_exists( 'taxa_facets_get_venomous_enum_map' )
        ? taxa_facets_get_venomous_enum_map()
        : apply_filters( 'taxa_facets_venomous_enum_map', array() );

    if ( ! empty( $venomous_map ) ) {
        $enabled = snl_facets_get_override_enabled(
            'venomous',
            isset( $scope_enabled['venomous'] ) ? (bool) $scope_enabled['venomous'] : true,
            $overrides
        );

        $config['venomous'] = array(
            'enabled' => $enabled,
            'label'   => snl_facets_get_override_label(
                'venomous',
                isset( $scope_labels['venomous'] ) ? $scope_labels['venomous'] : 'Venom',
                $overrides
            ),
            'key'     => 'venomous',
            'type'    => 'enum',
            'multi'   => false,
            'options' => snakes_lizards_facets_enum_options_from_map( $venomous_map ),
        );
    }

    // Ensure call types are not shown for this site.
    if ( isset( $config['call_types'] ) ) {
        $config['call_types']['enabled'] = false;
    }

    if ( ! empty( $excluded_colors ) ) {
        if ( isset( $config['colors'] ) && isset( $config['colors']['options'] ) && is_array( $config['colors']['options'] ) ) {
            $config['colors']['options'] = array_values(
                array_filter(
                    $config['colors']['options'],
                    function( $option ) use ( $excluded_colors ) {
                        return empty( $option['slug'] ) || ! in_array( $option['slug'], $excluded_colors, true );
                    }
                )
            );
        }
        if ( isset( $config['color'] ) && isset( $config['color']['options'] ) && is_array( $config['color']['options'] ) ) {
            $config['color']['options'] = array_values(
                array_filter(
                    $config['color']['options'],
                    function( $option ) use ( $excluded_colors ) {
                        return empty( $option['slug'] ) || ! in_array( $option['slug'], $excluded_colors, true );
                    }
                )
            );
        }
    }

    return $config;
}

/**
 * Admin UI overrides for facet labels/enabled flags.
 */
function snl_facets_get_ui_overrides() {
    $value = get_option( SNL_FACETS_OPTION_UI_OVERRIDES, array() );
    return is_array( $value ) ? $value : array();
}

function snl_facets_get_override_enabled( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ] ) && array_key_exists( 'enabled', $overrides[ $key ] ) ) {
        return (bool) $overrides[ $key ]['enabled'];
    }
    return (bool) $default;
}

function snl_facets_get_override_label( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ] ) && isset( $overrides[ $key ]['label'] ) && $overrides[ $key ]['label'] !== '' ) {
        return (string) $overrides[ $key ]['label'];
    }
    return (string) $default;
}

function snl_facets_get_ui_overrides_schema() {
    return array(
        'size'           => array( 'label' => 'Size', 'enabled' => true ),
        'shape_primary'  => array( 'label' => 'Body Form', 'enabled' => true ),
        'shape_secondary'=> array( 'label' => 'Tail', 'enabled' => true ),
        'pattern'        => array( 'label' => 'Pattern', 'enabled' => true ),
        'trait_primary'  => array( 'label' => 'Lifestyle', 'enabled' => true ),
        'trait_secondary'=> array( 'label' => 'Activity', 'enabled' => true ),
        'diet'           => array( 'label' => 'Diet', 'enabled' => true ),
        'behavior'       => array( 'label' => 'Behavior', 'enabled' => true ),
        'habitat'        => array( 'label' => 'Habitat', 'enabled' => true ),
        'venomous'       => array( 'label' => 'Venom', 'enabled' => true ),
    );
}

function snl_facets_sanitize_ui_overrides( $value ) {
    $allowed = snl_facets_get_ui_overrides_schema();
    $clean   = array();

    if ( ! is_array( $value ) ) {
        return $clean;
    }

    foreach ( $allowed as $key => $defaults ) {
        $item = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();
        $clean[ $key ] = array(
            'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : (bool) $defaults['enabled'],
            'label'   => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : (string) $defaults['label'],
        );
    }

    return $clean;
}

/**
 * Color exclusions for Snakes & Lizards facets.
 */
function snl_facets_get_excluded_colors() {
    $raw = get_option( SNL_FACETS_OPTION_EXCLUDED_COLORS, '' );
    if ( ! is_string( $raw ) || $raw === '' ) {
        return array();
    }

    $parts = preg_split( '/[\r\n,]+/', $raw );
    $colors = array();

    foreach ( $parts as $part ) {
        $part = sanitize_key( trim( $part ) );
        if ( $part !== '' ) {
            $colors[] = $part;
        }
    }

    return array_values( array_unique( $colors ) );
}

function snl_facets_sanitize_excluded_colors( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }

    $parts = preg_split( '/[\r\n,]+/', $value );
    $colors = array();

    foreach ( $parts as $part ) {
        $part = sanitize_key( trim( $part ) );
        if ( $part !== '' ) {
            $colors[] = $part;
        }
    }

    return implode( "\n", array_values( array_unique( $colors ) ) );
}

function snl_facets_sanitize_color_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_COLOR_MAP_RAW );
}

function snl_facets_sanitize_locked_map_raw( $value, $option_key ) {
    if ( snl_facets_maps_locked() ) {
        $existing = get_option( $option_key, '' );
        return is_string( $existing ) ? $existing : '';
    }

    if ( ! is_string( $value ) ) {
        return '';
    }

    return implode( "\n", snl_facets_parse_slug_list( $value ) );
}

function snl_facets_sanitize_maps_locked( $value ) {
    return (bool) $value;
}

function snl_facets_sanitize_size_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_SIZE_MAP_RAW );
}

function snl_facets_sanitize_shape_primary_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW );
}

function snl_facets_sanitize_shape_secondary_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW );
}

function snl_facets_sanitize_pattern_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_PATTERN_MAP_RAW );
}

function snl_facets_sanitize_trait_primary_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW );
}

function snl_facets_sanitize_trait_secondary_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW );
}

function snl_facets_sanitize_diet_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_DIET_MAP_RAW );
}

function snl_facets_sanitize_venomous_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_VENOMOUS_MAP_RAW );
}

function snl_facets_sanitize_behavior_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW );
}

function snl_facets_sanitize_habitat_map_raw( $value ) {
    return snl_facets_sanitize_locked_map_raw( $value, SNL_FACETS_OPTION_HABITAT_MAP_RAW );
}

function snl_facets_provide_color_map( $colors ) {
    return snl_facets_get_color_map();
}

function snl_facets_provide_size_map( $defaults ) {
    return snl_facets_size_map();
}

function snl_facets_provide_shape_primary_map( $defaults ) {
    return snl_facets_shape_primary_map();
}

function snl_facets_provide_shape_secondary_map( $defaults ) {
    return snl_facets_shape_secondary_map();
}

function snl_facets_provide_pattern_map( $defaults ) {
    return snl_facets_pattern_map();
}

function snl_facets_provide_trait_primary_map( $defaults ) {
    return snl_facets_trait_primary_map();
}

function snl_facets_provide_trait_secondary_map( $defaults ) {
    return snl_facets_trait_secondary_map();
}

function snl_facets_provide_diet_map( $defaults ) {
    return snl_facets_diet_map();
}

function snl_facets_provide_venomous_map( $defaults ) {
    return snl_facets_venomous_map();
}

function snl_facets_provide_behavior_map( $defaults ) {
    return snl_facets_behavior_map();
}

function snl_facets_provide_habitat_map( $defaults ) {
    return snl_facets_habitat_map();
}

function snl_facets_filter_color_map( $colors ) {
    if ( ! is_array( $colors ) ) {
        return $colors;
    }

    $excluded = snl_facets_get_excluded_colors();
    if ( empty( $excluded ) ) {
        return $colors;
    }

    foreach ( $excluded as $slug ) {
        unset( $colors[ $slug ] );
    }

    return $colors;
}

/**
 * Default taxa rank for SnakesAndLizards (tweak if you want genus instead).
 */
add_filter( 'taxa_facets_default_taxa_rank', 'snakes_lizards_facets_default_taxa_rank' );
function snakes_lizards_facets_default_taxa_rank( $rank ) {
    return 'subfamily';
}

/**
 * Optional: rewrite facet labels in the /taxa/v1/search JSON response
 * via an admin-defined mapping.
 */
add_filter( 'rest_post_dispatch', 'snakes_lizards_facets_relabel_in_taxa_search', 10, 3 );
function snakes_lizards_facets_relabel_in_taxa_search( $result, $server, $request ) {

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

        $item['facets'] = snakes_lizards_facets_adjust_trait_labels(
            $item['facets'],
            ! empty( $item['id'] ) ? (int) $item['id'] : 0
        );
    }
    unset( $item );

    $result->set_data( $data );
    return $result;
}

/**
 * Settings page: Snakes & Lizards facet label remapping
 */
add_action( 'admin_menu', 'snakes_lizards_facets_add_settings_page' );
function snakes_lizards_facets_add_settings_page() {
    add_options_page(
        'Snakes & Lizards Facets',
        'S&L Facets',
        'manage_options',
        'snl-facet-labels',
        'snakes_lizards_facets_render_settings_page'
    );
}

add_action( 'admin_init', 'snakes_lizards_facets_register_settings' );
function snakes_lizards_facets_register_settings() {

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_LABEL_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snakes_lizards_facets_sanitize_label_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_UI_OVERRIDES,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'snl_facets_sanitize_ui_overrides',
            'default'           => array(),
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_EXCLUDED_COLORS,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_excluded_colors',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_COLOR_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_color_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_MAPS_LOCKED,
        array(
            'type'              => 'boolean',
            'sanitize_callback' => 'snl_facets_sanitize_maps_locked',
            'default'           => false,
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_SIZE_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_size_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_shape_primary_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_shape_secondary_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_PATTERN_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_pattern_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_trait_primary_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_trait_secondary_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_DIET_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_diet_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_VENOMOUS_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_venomous_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_behavior_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_HABITAT_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_habitat_map_raw',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_SHORTCODE_SCOPE,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_shortcode_scope',
            'default'           => '',
        )
    );

    register_setting(
        'snl_facets_labels_group',
        SNL_FACETS_OPTION_MAP_SCOPE,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'snl_facets_sanitize_map_scope',
            'default'           => '',
        )
    );

    $scoped_map_options = array(
        SNL_FACETS_OPTION_COLOR_MAP_RAW          => 'snl_facets_sanitize_color_map_raw',
        SNL_FACETS_OPTION_SIZE_MAP_RAW           => 'snl_facets_sanitize_size_map_raw',
        SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW  => 'snl_facets_sanitize_shape_primary_map_raw',
        SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW=> 'snl_facets_sanitize_shape_secondary_map_raw',
        SNL_FACETS_OPTION_PATTERN_MAP_RAW        => 'snl_facets_sanitize_pattern_map_raw',
        SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW  => 'snl_facets_sanitize_trait_primary_map_raw',
        SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW=> 'snl_facets_sanitize_trait_secondary_map_raw',
        SNL_FACETS_OPTION_DIET_MAP_RAW           => 'snl_facets_sanitize_diet_map_raw',
        SNL_FACETS_OPTION_VENOMOUS_MAP_RAW       => 'snl_facets_sanitize_venomous_map_raw',
        SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW       => 'snl_facets_sanitize_behavior_map_raw',
        SNL_FACETS_OPTION_HABITAT_MAP_RAW        => 'snl_facets_sanitize_habitat_map_raw',
    );

    foreach ( snl_facets_allowed_scopes() as $scope ) {
        foreach ( $scoped_map_options as $option_key => $sanitize_callback ) {
            register_setting(
                'snl_facets_labels_group',
                snl_facets_get_scoped_option_key( $option_key, $scope ),
                array(
                    'type'              => 'string',
                    'sanitize_callback' => $sanitize_callback,
                    'default'           => '',
                )
            );
        }
    }

    add_settings_section(
        'snl_facets_labels_section',
        'Facet Label Remapping',
        'snakes_lizards_facets_labels_section_intro',
        'snl-facet-labels'
    );

    add_settings_field(
        'snl_facets_label_map_raw_field',
        'Label Mapping Rules',
        'snakes_lizards_facets_label_map_raw_field_render',
        'snl-facet-labels',
        'snl_facets_labels_section'
    );

    add_settings_section(
        'snl_facets_ui_section',
        'Facet UI Controls',
        'snl_facets_ui_section_intro',
        'snl-facet-labels'
    );

    add_settings_field(
        'snl_facets_ui_overrides_field',
        'Facet Labels & Visibility',
        'snl_facets_ui_overrides_field_render',
        'snl-facet-labels',
        'snl_facets_ui_section'
    );

    add_settings_section(
        'snl_facets_color_section',
        'Color Controls',
        'snl_facets_color_section_intro',
        'snl-facet-labels'
    );

    add_settings_field(
        'snl_facets_color_map_field',
        'Color Map',
        'snl_facets_color_map_field_render',
        'snl-facet-labels',
        'snl_facets_color_section'
    );

    add_settings_field(
        'snl_facets_color_exclusions_field',
        'Exclude Colors',
        'snl_facets_color_exclusions_field_render',
        'snl-facet-labels',
        'snl_facets_color_section'
    );

    add_settings_section(
        'snl_facets_map_section',
        'Facet Map Controls',
        'snl_facets_map_section_intro',
        'snl-facet-labels'
    );

    add_settings_field(
        'snl_facets_maps_lock_field',
        'Lock Facet Maps',
        'snl_facets_maps_lock_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_map_scope_field',
        'Facet Map Scope',
        'snl_facets_map_scope_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_size_map_field',
        'Size Map',
        'snl_facets_size_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_shape_primary_map_field',
        'Shape Primary Map',
        'snl_facets_shape_primary_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_shape_secondary_map_field',
        'Shape Secondary Map',
        'snl_facets_shape_secondary_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_pattern_map_field',
        'Pattern Map',
        'snl_facets_pattern_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_trait_primary_map_field',
        'Trait Primary Map',
        'snl_facets_trait_primary_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_trait_secondary_map_field',
        'Trait Secondary Map',
        'snl_facets_trait_secondary_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_diet_map_field',
        'Diet Map',
        'snl_facets_diet_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_venomous_map_field',
        'Venomous Map',
        'snl_facets_venomous_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_behavior_map_field',
        'Behavior Map',
        'snl_facets_behavior_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_field(
        'snl_facets_habitat_map_field',
        'Habitat Map',
        'snl_facets_habitat_map_field_render',
        'snl-facet-labels',
        'snl_facets_map_section'
    );

    add_settings_section(
        'snl_facets_overview_section',
        'Facet Map Overview',
        'snl_facets_overview_section_intro',
        'snl-facet-labels'
    );

    add_settings_field(
        'snl_facets_overview_field',
        'Overrides & Additions',
        'snl_facets_overview_field_render',
        'snl-facet-labels',
        'snl_facets_overview_section'
    );

    add_settings_field(
        'snl_facets_shortcode_scope_field',
        'Explorer Shortcode Scope',
        'snl_facets_shortcode_scope_field_render',
        'snl-facet-labels',
        'snl_facets_overview_section'
    );
}

function snakes_lizards_facets_labels_section_intro() {
    ?>
    <p>Define how facet label prefixes should be renamed in layouts that show facet strings.</p>
    <p>Use one rule per line:</p>
    <p><code>Old Prefix|New Prefix</code></p>
    <p>Example:</p>
    <p><code>Shape:|Body Form:</code></p>
    <p><code>Shape (secondary):|Tail:</code></p>
    <?php
}

function snakes_lizards_facets_label_map_raw_field_render() {
    $value = get_option( SNL_FACETS_OPTION_LABEL_MAP_RAW, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( SNL_FACETS_OPTION_LABEL_MAP_RAW ); ?>"
        id="<?php echo esc_attr( SNL_FACETS_OPTION_LABEL_MAP_RAW ); ?>"
        rows="8"
        cols="60"
        class="large-text code"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <?php
}

function snl_facets_ui_section_intro() {
    ?>
    <p>Control which facet filters are visible in the UI and how they are labeled.</p>
    <p>These settings apply across all scopes.</p>
    <?php
}

function snl_facets_ui_overrides_field_render() {
    $schema    = snl_facets_get_ui_overrides_schema();
    $overrides = snl_facets_get_ui_overrides();
    ?>
    <table class="widefat striped" style="max-width: 900px;">
        <thead>
        <tr>
            <th>Facet Key</th>
            <th>Enabled</th>
            <th>Label</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $schema as $key => $defaults ) : ?>
            <?php
            $current = isset( $overrides[ $key ] ) && is_array( $overrides[ $key ] )
                ? $overrides[ $key ]
                : $defaults;
            $enabled = isset( $current['enabled'] ) ? (bool) $current['enabled'] : (bool) $defaults['enabled'];
            $label   = isset( $current['label'] ) ? (string) $current['label'] : (string) $defaults['label'];
            ?>
            <tr>
                <td><code><?php echo esc_html( $key ); ?></code></td>
                <td>
                    <label>
                        <input
                            type="hidden"
                            name="<?php echo esc_attr( SNL_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][enabled]"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr( SNL_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][enabled]"
                            value="1"
                            <?php checked( $enabled ); ?>
                        />
                    </label>
                </td>
                <td>
                    <input
                        type="text"
                        class="regular-text"
                        name="<?php echo esc_attr( SNL_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][label]"
                        value="<?php echo esc_attr( $label ); ?>"
                    />
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function snl_facets_color_section_intro() {
    ?>
    <p>Define the color facet slugs for this taxonomy and optionally exclude specific colors. Colors appear in the UI and GPT prompt lists.</p>
    <p><strong>Important:</strong> Do not reorder existing colors once in use. Only append new ones to preserve stored bit positions.</p>
    <?php
}

function snl_facets_color_map_field_render() {
    $scope = snl_facets_get_map_scope();
    $option_key = snl_facets_get_scoped_option_key( SNL_FACETS_OPTION_COLOR_MAP_RAW, $scope );
    $value = get_option( $option_key, '' );
    $locked = snl_facets_maps_locked();
    ?>
    <textarea
        name="<?php echo esc_attr( $option_key ); ?>"
        id="<?php echo esc_attr( $option_key ); ?>"
        rows="8"
        cols="60"
        class="large-text code"
        placeholder="e.g. red&#10;orange&#10;yellow"
        <?php echo $locked ? 'readonly' : ''; ?>
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">Enter one color slug per line or separate with commas. Leave blank to use the default list.</p>
    <?php if ( $locked ) : ?>
        <p class="description"><strong>Maps are locked.</strong> Unlock facet maps below and save before editing this list.</p>
    <?php endif; ?>
    <?php
}

function snl_facets_color_exclusions_field_render() {
    $value = get_option( SNL_FACETS_OPTION_EXCLUDED_COLORS, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( SNL_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        id="<?php echo esc_attr( SNL_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        rows="6"
        cols="60"
        class="large-text code"
        placeholder="e.g. pink&#10;purple&#10;gold"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">Enter one color slug per line or separate with commas.</p>
    <?php
}

function snl_facets_map_section_intro() {
    ?>
    <p>Define the slugs and ordering for each facet map. These values determine enum IDs and bit positions.</p>
    <p><strong>Important:</strong> Do not reorder existing values once in use. Only append new ones to preserve stored mappings.</p>
    <p><strong>Tip:</strong> Use the Facet Map Scope selector to edit per-scope maps or leave it blank for the site-wide defaults.</p>
    <?php
}

function snl_facets_maps_lock_field_render() {
    $locked = snl_facets_maps_locked();
    ?>
    <label>
        <input
            type="checkbox"
            name="<?php echo esc_attr( SNL_FACETS_OPTION_MAPS_LOCKED ); ?>"
            value="1"
            <?php checked( $locked ); ?>
        />
        Lock facet map editing to prevent accidental reordering.
    </label>
    <p class="description">When locked, map fields become read-only and changes are ignored until you unlock and save.</p>
    <?php
}

function snl_facets_map_scope_field_render() {
    $current = snl_facets_get_map_scope();
    $options = array(
        ''           => 'Site focus (no scope)',
        'snakes'     => 'Snakes',
        'lizards'    => 'Lizards',
        'amphibians' => 'Amphibians',
    );
    ?>
    <select id="snl_facets_map_scope" name="<?php echo esc_attr( SNL_FACETS_OPTION_MAP_SCOPE ); ?>">
        <?php foreach ( $options as $value => $label ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php esc_html_e( 'Choose which scope you are editing or seeding with GPT. Leave blank to edit the site-wide maps.', 'snakes-lizards-facets' ); ?>
    </p>
    <?php
}

function snl_facets_render_map_textarea( $option_key, $placeholder, $description, $rows = 6 ) {
    $scope = snl_facets_get_map_scope();
    $scoped_key = snl_facets_get_scoped_option_key( $option_key, $scope );
    $value  = get_option( $scoped_key, '' );
    $locked = snl_facets_maps_locked();
    ?>
    <textarea
        name="<?php echo esc_attr( $scoped_key ); ?>"
        id="<?php echo esc_attr( $scoped_key ); ?>"
        rows="<?php echo esc_attr( (string) $rows ); ?>"
        cols="60"
        class="large-text code"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        <?php echo $locked ? 'readonly' : ''; ?>
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php echo esc_html( $description ); ?></p>
    <?php if ( $locked ) : ?>
        <p class="description"><strong>Maps are locked.</strong> Unlock facet maps above and save before editing.</p>
    <?php endif; ?>
    <?php
}

function snl_facets_size_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_SIZE_MAP_RAW,
        'e.g. tiny&#10;small&#10;medium',
        'Enter one size slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_shape_primary_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW,
        'e.g. slender&#10;robust&#10;stocky',
        'Enter one body form slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_shape_secondary_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW,
        'e.g. short&#10;long&#10;prehensile',
        'Enter one tail form slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_pattern_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_PATTERN_MAP_RAW,
        'e.g. solid&#10;spotted&#10;striped',
        'Enter one pattern slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_trait_primary_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW,
        'e.g. terrestrial&#10;arboreal&#10;fossorial',
        'Enter one primary trait slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_trait_secondary_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW,
        'e.g. diurnal&#10;nocturnal&#10;crepuscular',
        'Enter one secondary trait slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_diet_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_DIET_MAP_RAW,
        'e.g. carnivore&#10;insectivore&#10;omnivore',
        'Enter one diet slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_venomous_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_VENOMOUS_MAP_RAW,
        'e.g. venomous&#10;non_venomous&#10;mildly_venomous',
        'Enter one venomous slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_behavior_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW,
        'e.g. constricting&#10;venom_strike&#10;tail_autotomy',
        'Enter one behavior slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_habitat_map_field_render() {
    snl_facets_render_map_textarea(
        SNL_FACETS_OPTION_HABITAT_MAP_RAW,
        'e.g. montane&#10;coastal',
        'Enter one habitat slug per line or separate with commas. Leave blank to use the default list.'
    );
}

function snl_facets_overview_section_intro() {
    ?>
    <p>These are the facet maps and aliases this plugin contributes on top of the core Taxonomy API plugin.</p>
    <?php
}

function snl_facets_sanitize_shortcode_scope( $value ) {
    return snl_facets_sanitize_scope_value( $value );
}

function snl_facets_sanitize_map_scope( $value ) {
    return snl_facets_sanitize_scope_value( $value );
}

function snl_facets_shortcode_scope_field_render() {
    $current = snl_facets_sanitize_scope_value( get_option( SNL_FACETS_OPTION_SHORTCODE_SCOPE, '' ) );
    $options = array(
        ''           => 'Site focus (no scope)',
        'snakes'     => 'Snakes',
        'lizards'    => 'Lizards',
        'amphibians' => 'Amphibians',
    );
    ?>
    <p>
        <label for="snl_facets_shortcode_scope">
            <?php esc_html_e( 'Select the default scope used by the explorer shortcode.', 'snakes-lizards-facets' ); ?>
        </label>
    </p>
    <select id="snl_facets_shortcode_scope" name="<?php echo esc_attr( SNL_FACETS_OPTION_SHORTCODE_SCOPE ); ?>">
        <?php foreach ( $options as $value => $label ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php esc_html_e( 'Use [snl_explorer] in content. Leave blank to use the site focus (no scope).', 'snakes-lizards-facets' ); ?>
    </p>
    <?php
}

function snl_facets_overview_field_render() {
    $previous_scope = function_exists( 'snl_get_current_scope' ) ? snl_get_current_scope() : '';
    $map_scope = snl_facets_get_map_scope();
    if ( '' !== $map_scope ) {
        snl_set_current_scope( $map_scope );
    }
    $excluded_colors = snl_facets_get_excluded_colors();
    $color_map = snl_facets_filter_color_map( snl_facets_get_color_map() );
    $sections = array(
        'Color map'        => $color_map,
        'Color aliases'    => snl_facets_color_aliases(),
        'Size enum'        => snl_facets_size_map(),
        'Shape primary'    => snl_facets_shape_primary_map(),
        'Shape secondary'  => snl_facets_shape_secondary_map(),
        'Pattern enum'     => snl_facets_pattern_map(),
        'Trait primary'    => snl_facets_trait_primary_map(),
        'Trait secondary'  => snl_facets_trait_secondary_map(),
        'Diet enum'        => snl_facets_diet_map(),
        'Venomous enum'    => snl_facets_venomous_map(),
        'Behavior map'     => snl_facets_behavior_map(),
        'Habitat map'      => snl_facets_habitat_map(),
        'Excluded colors'  => empty( $excluded_colors ) ? array() : array_combine( $excluded_colors, $excluded_colors ),
    );
    ?>
    <div style="max-width: 900px;">
        <?php foreach ( $sections as $label => $items ) : ?>
            <h4><?php echo esc_html( $label ); ?></h4>
            <?php if ( empty( $items ) ) : ?>
                <p><em>None</em></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $items as $slug => $value ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $slug ); ?></code></td>
                            <td><code><?php echo esc_html( (string) $value ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
    if ( $previous_scope !== $map_scope ) {
        snl_set_current_scope( $previous_scope );
    }
}

function snl_facets_build_site_focus_prompt( $focus_keyword, $scope ) {
    $focus_keyword = trim( (string) $focus_keyword );
    $focus_keyword = $focus_keyword !== '' ? $focus_keyword : 'the site focus keyword';
    $scope = snl_facets_sanitize_scope_value( $scope );
    $scope_label = $scope !== '' ? $scope : '';

    return <<<EOP
You are configuring taxonomy facet token lists for an interactive field guide.
If a scope is provided, generate facet lists for that scoped group. Otherwise,
use the site focus keyword as the primary taxon scope.
Return exhaustive, inclusive lists that cover the full diversity of the taxon
(tiny to huge sizes, all major habitats including marine/polar, and broad color
coverage including uncommon but valid colors). Include specialist and edge-case
groups when applicable.

Site focus keyword: {$focus_keyword}
Scope (if provided): {$scope_label}

Return JSON ONLY with lower-case slug tokens (use underscores).
Each key should be an array of slugs, ordered from most common to less common.
Favor completeness over brevity.

Required JSON keys:
- colors
- sizes
- shape_primary
- shape_secondary
- pattern
- trait_primary
- trait_secondary
- diet
- venomous
- behavior
- habitat

Example format:
{
  "colors": ["brown", "green", "black"],
  "sizes": ["small", "medium", "large"],
  "shape_primary": ["elongated", "stocky"],
  "shape_secondary": ["tapered", "blunt"],
  "pattern": ["solid", "banded"],
  "trait_primary": ["keeled_scales", "smooth_scales"],
  "trait_secondary": ["eye_stripe"],
  "diet": ["insectivorous", "rodentivore"],
  "venomous": ["venomous", "non_venomous"],
  "behavior": ["basking", "arboreal"],
  "habitat": ["desert", "forest"]
}
EOP;
}

function snl_facets_normalize_slug_list( $values ) {
    if ( ! is_array( $values ) ) {
        return array();
    }

    $normalized = array();
    foreach ( $values as $value ) {
        $slug = strtolower( trim( (string) $value ) );
        $slug = preg_replace( '/[^a-z0-9_\\s-]/', '', $slug );
        $slug = str_replace( array( ' ', '-' ), '_', $slug );
        $slug = preg_replace( '/_+/', '_', $slug );
        $slug = trim( $slug, '_' );
        if ( $slug === '' ) {
            continue;
        }
        $normalized[] = $slug;
    }

    return array_values( array_unique( $normalized ) );
}

function snl_facets_update_map_from_gpt( $option_key, $values, $scope ) {
    $normalized = snl_facets_normalize_slug_list( $values );
    if ( empty( $normalized ) ) {
        return;
    }

    $scoped_key = snl_facets_get_scoped_option_key( $option_key, $scope );
    update_option( $scoped_key, implode( "\n", $normalized ) );
}

function snl_facets_handle_gpt_seed_maps() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'snakes-lizards-facets' ) );
    }

    check_admin_referer( 'snl_facets_seed_maps' );

    if ( snl_facets_maps_locked() ) {
        wp_redirect( add_query_arg(
            array(
                'page'                     => 'snl-facet-labels',
                'snl_facets_seed_status'   => 'locked',
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    if ( ! function_exists( 'get_gpt_response' ) ) {
        wp_redirect( add_query_arg(
            array(
                'page'                     => 'snl-facet-labels',
                'snl_facets_seed_status'   => 'missing',
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    $gpt_enabled = get_option( 'gpt_enabled', '0' );
    if ( ! in_array( $gpt_enabled, array( 'true', true, '1', 1 ), true ) ) {
        wp_redirect( add_query_arg(
            array(
                'page'                     => 'snl-facet-labels',
                'snl_facets_seed_status'   => 'disabled',
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    $map_scope     = snl_facets_get_map_scope();
    $focus_keyword = get_option( 'site_focus_keyword', '' );
    $prompt        = snl_facets_build_site_focus_prompt( $focus_keyword, $map_scope );
    $response      = get_gpt_response( $prompt, 'gpt-4o-mini' );

    error_log( '[SNL FACETS][GPT SEED] Scope: ' . ( $map_scope !== '' ? $map_scope : 'site_focus' ) );
    error_log( '[SNL FACETS][GPT SEED] Prompt: ' . $prompt );

    if ( ! $response ) {
        error_log( '[SNL FACETS][GPT SEED] Empty response.' );
        wp_redirect( add_query_arg(
            array(
                'page'                     => 'snl-facet-labels',
                'snl_facets_seed_status'   => 'empty',
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    $raw = trim( (string) $response );
    error_log( '[SNL FACETS][GPT SEED] Raw response: ' . $raw );
    if ( strpos( $raw, '```' ) === 0 ) {
        $raw = preg_replace( '#^```(?:json)?#i', '', $raw );
        $raw = preg_replace( '#```$#', '', $raw );
        $raw = trim( $raw );
    }

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) {
        wp_redirect( add_query_arg(
            array(
                'page'                     => 'snl-facet-labels',
                'snl_facets_seed_status'   => 'invalid',
            ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_COLOR_MAP_RAW, $data['colors'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_SIZE_MAP_RAW, $data['sizes'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_SHAPE_PRIMARY_MAP_RAW, $data['shape_primary'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_SHAPE_SECONDARY_MAP_RAW, $data['shape_secondary'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_PATTERN_MAP_RAW, $data['pattern'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_TRAIT_PRIMARY_MAP_RAW, $data['trait_primary'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_TRAIT_SECONDARY_MAP_RAW, $data['trait_secondary'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_DIET_MAP_RAW, $data['diet'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_VENOMOUS_MAP_RAW, $data['venomous'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_BEHAVIOR_MAP_RAW, $data['behavior'] ?? array(), $map_scope );
    snl_facets_update_map_from_gpt( SNL_FACETS_OPTION_HABITAT_MAP_RAW, $data['habitat'] ?? array(), $map_scope );

    wp_redirect( add_query_arg(
        array(
            'page'                     => 'snl-facet-labels',
            'snl_facets_seed_status'   => 'success',
        ),
        admin_url( 'options-general.php' )
    ) );
    exit;
}
add_action( 'admin_post_snl_facets_seed_maps', 'snl_facets_handle_gpt_seed_maps' );

function snl_facets_seed_maps_admin_notice() {
    if ( ! isset( $_GET['page'], $_GET['snl_facets_seed_status'] ) || $_GET['page'] !== 'snl-facet-labels' ) {
        return;
    }

    $status = sanitize_text_field( wp_unslash( $_GET['snl_facets_seed_status'] ) );
    $messages = array(
        'success'  => array( 'success', 'Facet map values updated from GPT.' ),
        'locked'   => array( 'error', 'Facet maps are locked. Unlock them and save before generating.' ),
        'missing'  => array( 'error', 'GPT helper is unavailable. Ensure the parent plugin is active.' ),
        'disabled' => array( 'error', 'GPT is disabled. Enable it in the Taxonomy API settings.' ),
        'empty'    => array( 'error', 'GPT returned an empty response.' ),
        'invalid'  => array( 'error', 'GPT response could not be parsed as JSON.' ),
    );

    if ( ! isset( $messages[ $status ] ) ) {
        return;
    }

    $class = $messages[ $status ][0];
    $text  = $messages[ $status ][1];
    printf(
        '<div class="notice notice-%1$s"><p>%2$s</p></div>',
        esc_attr( $class ),
        esc_html( $text )
    );
}
add_action( 'admin_notices', 'snl_facets_seed_maps_admin_notice' );

function snakes_lizards_facets_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Snakes &amp; Lizards Facets</h1>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url(
                    admin_url( 'admin-post.php?action=snl_facets_seed_maps' ),
                    'snl_facets_seed_maps'
                ) ); ?>">
                    <?php esc_html_e( 'Generate facet maps from Site Focus Keyword (GPT)', 'snakes-lizards-facets' ); ?>
                </a>
            </p>
            <p class="description">
                <?php esc_html_e( 'Uses the selected Facet Map Scope (or Site Focus Keyword if blank) to ask GPT for the most appropriate facet values.', 'snakes-lizards-facets' ); ?>
            </p>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'snl_facets_labels_group' );
            do_settings_sections( 'snl-facet-labels' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snakes_lizards_facets_sanitize_label_map_raw( $value ) {
    return is_string( $value ) ? trim( $value ) : '';
}

function snakes_lizards_facets_get_label_map() {
    $raw = get_option( SNL_FACETS_OPTION_LABEL_MAP_RAW, '' );
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

function snakes_lizards_facets_adjust_trait_labels( $facets, $post_id ) {
    if ( ! is_array( $facets ) || empty( $facets ) ) {
        return $facets;
    }

    $map = snakes_lizards_facets_get_label_map();
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
 * Helper shortcodes:
 *   [snl_explorer]
 *   [snakes_explorer]
 *   [lizards_explorer]
 *   [amphibians_explorer]
 */
add_shortcode( 'snl_explorer', 'snl_explorer_shortcode' );
add_shortcode( 'snakes_explorer', 'snl_snakes_explorer_shortcode' );
add_shortcode( 'lizards_explorer', 'snl_lizards_explorer_shortcode' );
add_shortcode( 'amphibians_explorer', 'snl_amphibians_explorer_shortcode' );

function snl_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $scope = snl_facets_sanitize_scope_value( get_option( SNL_FACETS_OPTION_SHORTCODE_SCOPE, '' ) );
    if ( $scope !== '' ) {
        $atts['scope'] = $scope;
    }
    return taxa_facets_explorer_shortcode( $atts );
}

function snl_snakes_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $atts['scope'] = 'snakes';
    return taxa_facets_explorer_shortcode( $atts );
}

function snl_lizards_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $atts['scope'] = 'lizards';
    return taxa_facets_explorer_shortcode( $atts );
}

function snl_amphibians_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $atts['scope'] = 'amphibians';
    return taxa_facets_explorer_shortcode( $atts );
}

/**
 * Provide locked facets JSON based on scope.
 */
add_filter( 'taxa_facets_locked_facets_json', 'snl_locked_facets_from_scope', 10, 2 );
function snl_locked_facets_from_scope( $json, $atts ) {

    if ( ! empty( $atts['scope'] ) ) {
        snl_set_current_scope( $atts['scope'] );
    }

    if ( empty( $atts['scope'] ) ) {
        return $json;
    }

    if ( 'snakes' === $atts['scope'] ) {
        return wp_json_encode( array( 'group' => array( 'snakes-serpentes' ) ) );
    }

    if ( 'lizards' === $atts['scope'] ) {
        return wp_json_encode( array( 'group' => array( 'lizards-sauria' ) ) );
    }

    if ( 'amphibians' === $atts['scope'] ) {
        return wp_json_encode( array( 'group' => array( 'amphibians' ) ) );
    }

    return $json;
}

/**
 * Filter /taxa/v1/search results by `group` query param.
 */
add_filter( 'rest_post_dispatch', 'snl_filter_taxa_search_by_group_category', 20, 3 );
function snl_filter_taxa_search_by_group_category( $result, $server, $request ) {

    $route = $request->get_route();
    if ( strpos( $route, '/taxa/v1/search' ) === false ) {
        return $result;
    }

    if ( is_wp_error( $result ) || ! ( $result instanceof WP_REST_Response ) ) {
        return $result;
    }

    $group_param = $request->get_param( 'group' );
    if ( empty( $group_param ) ) {
        return $result;
    }

    $wanted_slugs = array_filter(
        array_map(
            'sanitize_title',
            explode( ',', (string) $group_param )
        )
    );

    if ( empty( $wanted_slugs ) ) {
        return $result;
    }

    $data = $result->get_data();
    if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
        return $result;
    }

    $filtered = array();

    foreach ( $data['items'] as $item ) {
        $post_id = 0;

        if ( ! empty( $item['id'] ) ) {
            $post_id = (int) $item['id'];
        } elseif ( ! empty( $item['link'] ) ) {
            $post_id = url_to_postid( $item['link'] );
        }

        if ( ! $post_id ) {
            $filtered[] = $item;
            continue;
        }

        $terms = get_the_terms( $post_id, 'category' );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            continue;
        }

        $match = false;
        foreach ( $terms as $term ) {
            if ( in_array( $term->slug, $wanted_slugs, true ) ) {
                $match = true;
                break;
            }
        }

        if ( $match ) {
            $filtered[] = $item;
        }
    }

    $data['items'] = $filtered;
    $data['total'] = count( $filtered );

    $result->set_data( $data );
    return $result;
}

/**
 * Adjust taxa dropdown options for snakes / lizards scoped explorers.
 */
add_filter( 'taxa_facets_taxa_dropdown_options', 'snl_filter_taxa_dropdown_for_scopes', 10, 2 );
function snl_filter_taxa_dropdown_for_scopes( $options, $atts ) {

    if ( empty( $atts['scope'] ) ) {
        return $options;
    }

    if ( ! in_array( $atts['scope'], array( 'snakes', 'lizards', 'amphibians' ), true ) ) {
        return $options;
    }

    $filtered = array();

    foreach ( $options as $opt ) {
        if ( isset( $opt['value'] ) && 'order' === $opt['value'] ) {
            continue;
        }
        $filtered[] = $opt;
    }

    return $filtered;
}

/**
 * Per-scope search placeholder.
 */
add_filter( 'taxa_facets_search_placeholder', 'snl_taxa_search_placeholder', 10, 2 );
function snl_taxa_search_placeholder( $placeholder, $atts ) {
    if ( ! empty( $atts['scope'] ) ) {
        if ( 'snakes' === $atts['scope'] ) {
            return 'Search snakes...';
        }
        if ( 'lizards' === $atts['scope'] ) {
            return 'Search lizards...';
        }
        if ( 'amphibians' === $atts['scope'] ) {
            return 'Search amphibians...';
        }
    }

    return 'Search species...';
}

// ---- Scope state ----
function snl_set_current_scope( $scope ) {
    $scope = is_string( $scope ) ? $scope : '';
    $scope = sanitize_key( $scope );
    $GLOBALS['snl_current_scope'] = $scope;
}

function snl_get_current_scope() {
    return isset( $GLOBALS['snl_current_scope'] ) ? (string) $GLOBALS['snl_current_scope'] : '';
}
