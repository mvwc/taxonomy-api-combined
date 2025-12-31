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

function snl_facets_size_map() {
    return array(
        'tiny'       => 1,
        'small'      => 2,
        'medium'     => 3,
        'large'      => 4,
        'very_large' => 5,
    );
}

function snl_facets_shape_primary_map() {
    return array(
        'slender'   => 1,
        'robust'    => 2,
        'stocky'    => 3,
        'bulky'     => 4,
        'flattened' => 5,

        // APPEND ONLY
        'heavy_bodied'         => 6,
        'laterally_compressed' => 7,
    );
}

function snl_facets_shape_secondary_map() {
    return array(
        'short'      => 1,
        'long'       => 2,
        'prehensile' => 3,
        'reduced'    => 4,
        'clubbed'    => 5,
        'rattle'     => 6,

        // APPEND ONLY
        'blunt'      => 7,
        'tapered'    => 8,
    );
}

function snl_facets_pattern_map() {
    return array(
        'solid'    => 1,
        'spotted'  => 2,
        'striped'  => 3,
        'banded'   => 4,
        'blotched' => 5,
        'mottled'  => 6,
        'speckled' => 7,

        // APPEND ONLY
        'reticulated' => 8,
    );
}

function snl_facets_trait_primary_map() {
    return array(
        'terrestrial'  => 1,
        'arboreal'     => 2,
        'fossorial'    => 3,
        'aquatic'      => 4,
        'semi_aquatic' => 5,
        'saxicolous'   => 6,
    );
}

function snl_facets_trait_secondary_map() {
    return array(
        'diurnal'     => 1,
        'nocturnal'   => 2,
        'crepuscular' => 3,
    );
}

function snl_facets_diet_map() {
    return array(
        'carnivore'   => 1,
        'insectivore' => 2,
        'omnivore'    => 3,
        'herbivore'   => 4,
        'piscivore'   => 5,

        // APPEND ONLY (snake prey / more specific)
        'mammals'       => 6,
        'birds'         => 7,
        'reptiles'      => 8,
        'amphibians'    => 9,
        'fish'          => 10,
        'eggs'          => 11,
        'invertebrates' => 12,
    );
}

function snl_facets_venomous_map() {
    return array(
        'venomous'        => 1,
        'non_venomous'    => 2,

        // APPEND ONLY
        'mildly_venomous' => 3,
    );
}

function snl_facets_behavior_additions() {
    return array(
        // keep existing if any; only append bits not present
        'constricting'   => 1 << 8,
        'venom_strike'   => 1 << 9,
        'tail_autotomy'  => 1 << 10,
        'gliding'        => 1 << 11,
        'chameleon_like' => 1 << 12,
    );
}

function snl_facets_habitat_additions() {
    return array(
        'montane' => 1 << 9,
        'coastal' => 1 << 10,
    );
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

    add_filter( 'taxa_facets_color_map', 'snl_facets_filter_color_map' );

    /**
     * ---------------------------------
     * SIZE (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_size_enum_map', function( $defaults ) {
        return $defaults + snl_facets_size_map();
    } );

    /**
     * ---------------------------------
     * SHAPE PRIMARY (enum) – body build / form
     * ---------------------------------
     */
    add_filter( 'taxa_facets_shape_primary_enum_map', function( $defaults ) {
        return $defaults + snl_facets_shape_primary_map();
    } );

    /**
     * ---------------------------------
     * SHAPE SECONDARY (enum) – tail feature/type
     * ---------------------------------
     */
    add_filter( 'taxa_facets_shape_secondary_enum_map', function( $defaults ) {
        return $defaults + snl_facets_shape_secondary_map();
    } );

    /**
     * ---------------------------------
     * PATTERN (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_pattern_enum_map', function( $defaults ) {
        return $defaults + snl_facets_pattern_map();
    } );

    /**
     * ---------------------------------
     * TRAIT PRIMARY (enum) – lifestyle/ecology
     * ---------------------------------
     */
    add_filter( 'taxa_facets_trait_primary_enum_map', function( $defaults ) {
        return $defaults + snl_facets_trait_primary_map();
    } );

    /**
     * ---------------------------------
     * TRAIT SECONDARY (enum) – activity pattern
     * ---------------------------------
     */
    add_filter( 'taxa_facets_trait_secondary_enum_map', function( $defaults ) {
        return $defaults + snl_facets_trait_secondary_map();
    } );

    /**
     * ---------------------------------
     * DIET (enum)
     * ---------------------------------
     * Keep broad tokens for lizards; APPEND prey-type tokens for snakes.
     */
    add_filter( 'taxa_facets_diet_enum_map', function( $defaults ) {
        return $defaults + snl_facets_diet_map();
    } );

    /**
     * ---------------------------------
     * VENOMOUS (enum)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_venomous_enum_map', function( $defaults ) {
        return $defaults + snl_facets_venomous_map();
    } );

    /**
     * ---------------------------------
     * BEHAVIOR (bitmask) – extend core map, but UI will not duplicate section
     * ---------------------------------
     */
    add_filter( 'taxa_facets_behavior_map', function( $defaults ) {
        $additions = snl_facets_behavior_additions();

        // Only append slugs that don't exist yet (safety).
        foreach ( $additions as $slug => $bit ) {
            if ( ! isset( $defaults[ $slug ] ) ) {
                $defaults[ $slug ] = $bit;
            }
        }

        return $defaults;
    } );

    /**
     * ---------------------------------
     * HABITAT (bitmask) – extend core map, but UI will not duplicate section
     * ---------------------------------
     */
    add_filter( 'taxa_facets_habitat_map', function( $defaults ) {
        $additions = snl_facets_habitat_additions();

        foreach ( $additions as $slug => $bit ) {
            if ( ! isset( $defaults[ $slug ] ) ) {
                $defaults[ $slug ] = $bit;
            }
        }

        return $defaults;
    } );
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
        'snl_facets_color_exclusions_field',
        'Exclude Colors',
        'snl_facets_color_exclusions_field_render',
        'snl-facet-labels',
        'snl_facets_color_section'
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
    <p>These settings apply to both the Snakes and Lizards scopes.</p>
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
    <p>Remove colors that do not apply to this taxonomy. Excluded colors will not appear in the UI or GPT prompt lists.</p>
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

function snl_facets_overview_section_intro() {
    ?>
    <p>These are the facet maps and aliases this plugin contributes on top of the core Taxonomy API plugin.</p>
    <?php
}

function snl_facets_overview_field_render() {
    $excluded_colors = snl_facets_get_excluded_colors();
    $sections = array(
        'Color aliases'      => snl_facets_color_aliases(),
        'Size enum'          => snl_facets_size_map(),
        'Shape primary'      => snl_facets_shape_primary_map(),
        'Shape secondary'    => snl_facets_shape_secondary_map(),
        'Pattern enum'       => snl_facets_pattern_map(),
        'Trait primary'      => snl_facets_trait_primary_map(),
        'Trait secondary'    => snl_facets_trait_secondary_map(),
        'Diet enum'          => snl_facets_diet_map(),
        'Venomous enum'      => snl_facets_venomous_map(),
        'Behavior additions' => snl_facets_behavior_additions(),
        'Habitat additions'  => snl_facets_habitat_additions(),
        'Excluded colors'    => empty( $excluded_colors ) ? array() : array_combine( $excluded_colors, $excluded_colors ),
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
}

function snakes_lizards_facets_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Snakes &amp; Lizards Facets</h1>
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
 *   [snakes_explorer]
 *   [lizards_explorer]
 */
add_shortcode( 'snakes_explorer', 'snl_snakes_explorer_shortcode' );
add_shortcode( 'lizards_explorer', 'snl_lizards_explorer_shortcode' );

function snl_snakes_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $atts['scope'] = 'snakes';
    return taxa_facets_explorer_shortcode( $atts );
}

function snl_lizards_explorer_shortcode( $atts = array(), $content = '', $tag = '' ) {
    $atts['scope'] = 'lizards';
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

    if ( ! in_array( $atts['scope'], array( 'snakes', 'lizards' ), true ) ) {
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
