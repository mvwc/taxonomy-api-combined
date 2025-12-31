<?php
/**
 * Plugin Name: AvianDiscovery – Taxonomy API Facets
 * Description: Avian-specific facet maps (size, shapes, patterns, behavior, habitat, diet, call types) for the Taxonomy API plugin.
 * Author: Brandon Bartlett
 * Version: 1.1.1
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ensure the parent "Taxonomy API" facet helpers are available.
 * If not, show an admin notice and bail.
 */
add_action( 'plugins_loaded', 'avian_facets_init', 20 );
function avian_facets_init() {

    if ( ! function_exists( 'taxa_facets_get_color_map' ) ) {
        add_action( 'admin_notices', 'avian_facets_missing_parent_notice' );
        return;
    }

    // Register all facet maps / aliases / helpers.
    avian_facets_register_maps();
}

/**
 * Option keys.
 */
define( 'AVIAN_FACETS_OPTION_UI_OVERRIDES', 'avian_facets_ui_overrides' );
define( 'AVIAN_FACETS_OPTION_EXCLUDED_COLORS', 'avian_facets_excluded_colors' );
define( 'AVIAN_FACETS_OPTION_COLOR_MAP_RAW', 'avian_facets_color_map_raw' );

/**
 * Admin notice if parent plugin (Taxonomy API) isn't loaded.
 */
function avian_facets_missing_parent_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__(
        'AvianDiscovery – Taxonomy API Facets requires the "Taxonomy API" plugin to be active.',
        'avian-discovery-facets'
    );
    echo '</p></div>';
}

/**
 * Avian-specific maps and aliases (used in filters + admin overview).
 */
function avian_facets_color_aliases() {
    return array(
        'rufous'      => 'brown',
        'buff'        => 'tan',
        'chestnut'    => 'brown',
        'slaty'       => 'gray',
        'offwhite'    => 'white',
        'off white'   => 'white',
        'off-white'   => 'white',
    );
}

function avian_facets_default_color_slugs() {
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

function avian_facets_get_color_slugs() {
    $raw = get_option( AVIAN_FACETS_OPTION_COLOR_MAP_RAW, '' );

    if ( ! is_string( $raw ) || $raw === '' ) {
        return avian_facets_default_color_slugs();
    }

    $parts  = preg_split( '/[\r\n,]+/', $raw );
    $colors = array();

    foreach ( $parts as $part ) {
        $part = sanitize_key( trim( $part ) );
        if ( $part !== '' ) {
            $colors[] = $part;
        }
    }

    return array_values( array_unique( $colors ) );
}

function avian_facets_get_color_map() {
    $slugs = avian_facets_get_color_slugs();
    $map   = array();

    foreach ( $slugs as $index => $slug ) {
        $map[ $slug ] = 1 << $index;
    }

    return $map;
}

function avian_facets_size_map() {
    return array(
        'tiny'       => 1,
        'small'      => 2,
        'medium'     => 3,
        'large'      => 4,
        'very_large' => 5,
    );
}

function avian_facets_shape_primary_map() {
    return array(
        'slender' => 1,
        'broad'   => 2,
        'stocky'  => 3,
        'pointed' => 4,
        'rounded' => 5,
    );
}

function avian_facets_shape_secondary_map() {
    return array(
        'short'   => 1,
        'long'    => 2,
        'forked'  => 3,
        'rounded' => 4,
        'wedged'  => 5,
    );
}

function avian_facets_pattern_map() {
    return array(
        'solid'    => 1,
        'spotted'  => 2,
        'striped'  => 3,
        'barred'   => 4,
        'mottled'  => 5,
        'streaked' => 6,
    );
}

function avian_facets_trait_primary_map() {
    return array(
        'perching'        => 1,
        'soaring'         => 2,
        'wading'          => 3,
        'diving'          => 4,
        'ground_foraging' => 5,
    );
}

function avian_facets_trait_secondary_map() {
    return array(
        'vocal'       => 1,
        'nocturnal'   => 2,
        'diurnal'     => 3,
        'crepuscular' => 4,
    );
}

function avian_facets_diet_map() {
    return array(
        'carnivore'   => 1,
        'insectivore' => 2,
        'omnivore'    => 3,
        'herbivore'   => 4,
        'granivore'   => 5,
        'nectarivore' => 6,
        'frugivore'   => 7,
    );
}

function avian_facets_call_type_map() {
    return array(
        'song'     => 1 << 0,
        'whistle'  => 1 << 1,
        'call'     => 1 << 2,
        'scream'   => 1 << 3,
        'laughing' => 1 << 4,
        'drumming' => 1 << 5,
    );
}

function avian_facets_behavior_map() {
    return array(
        'solitary'    => 1 << 0,
        'social'      => 1 << 1,
        'territorial' => 1 << 2,
        'migratory'   => 1 << 3,
        'resident'    => 1 << 4,
        'colonial'    => 1 << 5,
    );
}

function avian_facets_habitat_map() {
    return array(
        'forest'    => 1 << 0,
        'woodland'  => 1 << 1,
        'savanna'   => 1 << 2,
        'wetland'   => 1 << 3,
        'grassland' => 1 << 4,
        'coastal'   => 1 << 5,
        'urban'     => 1 << 6,
    );
}

/**
 * Register all Avian-specific facet maps & aliases.
 *
 * IMPORTANT:
 *  - Once this is live on production with real data, DO NOT REORDER the arrays.
 *  - Only APPEND new values at the end to preserve existing enum IDs / bit positions.
 */
function avian_facets_register_maps() {

    /**
     * ---------------------------------
     * COLOR: add a few avian-specific aliases
     * (core already defines the color map and a base alias set)
     * ---------------------------------
     */
    add_filter( 'taxa_facets_color_aliases', function( $aliases ) {

        // You already have some aliases in core; we just extend.
        return array_merge( $aliases, avian_facets_color_aliases() );
    } );

    add_filter( 'taxa_facets_color_map', 'avian_facets_provide_color_map', 5 );
    add_filter( 'taxa_facets_color_map', 'avian_facets_filter_color_map', 20 );

    /**
     * ---------------------------------
     * SIZE (enum)
     * ---------------------------------
     *
     * Maps GPT tokens like "medium" to small ints.
     */
    add_filter( 'taxa_facets_size_enum_map', function( $defaults ) {
        // DO NOT REORDER once live; only append.
        // If there were existing values in $defaults, we keep their IDs.
        return $defaults + avian_facets_size_map();
    } );

    /**
     * ---------------------------------
     * SHAPE PRIMARY (enum) – body / wing form
     * ---------------------------------
     *
     */
    add_filter( 'taxa_facets_shape_primary_enum_map', function( $defaults ) {
        return $defaults + avian_facets_shape_primary_map();
    } );

    /**
     * ---------------------------------
     * SHAPE SECONDARY (enum) – tail form
     * ---------------------------------
     *
     */
    add_filter( 'taxa_facets_shape_secondary_enum_map', function( $defaults ) {
        return $defaults + avian_facets_shape_secondary_map();
    } );

    /**
     * ---------------------------------
     * PATTERN (enum)
     * ---------------------------------
     *
     */
    add_filter( 'taxa_facets_pattern_enum_map', function( $defaults ) {
        return $defaults + avian_facets_pattern_map();
    } );

    /**
     * ---------------------------------
     * TRAIT PRIMARY (enum)
     * ---------------------------------
     *
     */
    add_filter( 'taxa_facets_trait_primary_enum_map', function( $defaults ) {
        return $defaults + avian_facets_trait_primary_map();
    } );

    /**
     * ---------------------------------
     * TRAIT SECONDARY (enum)
     * ---------------------------------
     *
     * Secondary traits like activity pattern or vocal emphasis.
     */
    add_filter( 'taxa_facets_trait_secondary_enum_map', function( $defaults ) {
        return $defaults + avian_facets_trait_secondary_map();
    } );

    /**
     * ---------------------------------
     * DIET (enum)
     * ---------------------------------
     *
     * Diet traits such as "carnivore" and "insectivore".
     */
    add_filter( 'taxa_facets_diet_enum_map', function( $defaults ) {
        return $defaults + avian_facets_diet_map();
    } );

    /**
     * ---------------------------------
     * CALL TYPE (bitmask)
     * ---------------------------------
     *
     * GPT: "laughing" 
     */
    add_filter( 'taxa_facets_call_type_map', function( $defaults ) {
        // DO NOT REORDER once live; append only.
        // Preserve any pre-existing entries in $defaults.
        return $defaults + avian_facets_call_type_map();
    } );

    /**
     * ---------------------------------
     * BEHAVIOR (bitmask)
     * ---------------------------------
     *
     * GPT used "social" and "territorial".
     */
    add_filter( 'taxa_facets_behavior_map', function( $defaults ) {
        return $defaults + avian_facets_behavior_map();
    } );

    /**
     * ---------------------------------
     * HABITAT (bitmask)
     * ---------------------------------
     *
     * GPT used "woodland" and "savanna" in your log.
     */
    add_filter( 'taxa_facets_habitat_map', function( $defaults ) {
        return $defaults + avian_facets_habitat_map();
    } );

    /**
     * ---------------------------------
     * (Optional) FAMILY / REGION enums
     * ---------------------------------
     *
     * Your core currently has placeholder functions that return [].
     * If you later want to facet by family/region, we can refactor those
     * to be filter-based as well. For now we leave them as-is so GPT can
     * still output "kingfisher", "australia" but they’re not stored.
     */
}

/**
 * Override the generic taxa_facets_gpt_prompt with an Avian-specific one.
 *
 * This controls how GPT chooses facet values for birds on AvianDiscovery.
 */
add_filter( 'taxa_facets_gpt_prompt', 'avian_facets_customize_gpt_prompt', 10, 3 );

/**
 * Build an Avian-specific GPT prompt for facet generation.
 *
 * @param string $prompt Original prompt built by the core plugin.
 * @param string $title  Post / species title (usually "Common Name (Scientific name)").
 * @param array  $lists  Assoc array of allowed tokens as strings:
 *                       [
 *                         'colors'          => 'red, orange, yellow, ...',
 *                         'sizes'           => 'tiny, small, medium, ...',
 *                         'shape_primary'   => 'slender, broad, stocky, ...',
 *                         'shape_secondary' => 'short, long, forked, ...',
 *                         'patterns'        => 'solid, spotted, striped, ...',
 *                         'traits_primary'  => 'perching, soaring, ...',
 *                         'traits_secondary'=> 'vocal, nocturnal, ...',
 *                         'diets'           => 'carnivore, insectivore, ...',
 *                         'calltypes'       => 'song, whistle, laughing, ...',
 *                         'behaviors'       => 'social, territorial, ...',
 *                         'habitats'        => 'forest, woodland, savanna, ...',
 *                         'families'        => '',
 *                         'regions'         => ''
 *                       ]
 *
 * @return string New Avian-specific prompt.
 */
function avian_facets_customize_gpt_prompt( $prompt, $title, array $lists ) {

    $colors          = $lists['colors'];
    $sizes           = $lists['sizes'];
    $shape_primary   = $lists['shape_primary'];
    $shape_secondary = $lists['shape_secondary'];
    $patterns        = $lists['patterns'];
    $traits_primary  = $lists['traits_primary'];
    $traits_secondary= $lists['traits_secondary'];
    $diets           = $lists['diets'];
    $calltypes       = $lists['calltypes'];
    $behaviors       = $lists['behaviors'];
    $habitats        = $lists['habitats'];
    $families        = $lists['families'];
    $regions         = $lists['regions'];

    $prompt = <<<EOP
You are an ornithology assistant helping build an interactive bird field guide
for AvianDiscovery.com.

Your job is to assign **concise facet tags** to each bird species using ONLY
the allowed tokens listed below. These facets will power filters such as size,
body/wing shape, tail shape, plumage pattern, behavior, call type, habitat,
and diet.

CRITICAL RULES:

1. **Only use tokens from the allowed lists below.**
   - Do NOT invent new tokens.
   - If no suitable token exists, use null (for single-value fields) or [] (for arrays).

2. Think like a birder:
   - Base your choices on a typical adult of the species.
   - If the common name clearly implies traits (e.g., "Laughing Kookaburra"),
     you can infer call_type = ["laughing"], habitat = ["woodland"] or similar,
     as long as those tokens exist in the allowed lists.

3. Keep the output conservative:
   - If you are unsure, prefer null or [] over guessing.
   - Avoid assigning everything; choose the most characteristic traits.

4. Use the fields with these meanings:
   - size: overall body size category of the bird.
   - shape_primary: general body/wing form (slender, broad, stocky, etc.).
   - shape_secondary: tail form (short, long, forked, rounded, etc.).
   - pattern: main plumage pattern (solid, spotted, striped, barred, etc.).
   - trait_primary: key locomotion/foraging trait (perching, soaring, diving, etc.).
   - trait_secondary: secondary trait such as vocal focus or activity pattern (vocal, nocturnal, etc.).
   - diet: primary diet type (carnivore, insectivore, omnivore, etc.).
   - colors: main visible plumage colors.
   - call_type: characteristic vocalization type(s) (song, whistle, laughing, etc.).
   - behavior: social/territorial/migratory behavior.
   - habitat: typical habitat(s) where the species is commonly found.
   - family: higher-level taxonomic family, if a matching token exists.
   - region: broad geographic region, if a matching token exists.

ALLOWED TOKENS (you must choose ONLY from these):

- colors (multi): {$colors}
- size (single): {$sizes}
- shape_primary (single): {$shape_primary}
- shape_secondary (single): {$shape_secondary}
- pattern (single): {$patterns}
- trait_primary (single): {$traits_primary}
- trait_secondary (single): {$traits_secondary}
- diet (single): {$diets}
- call_type (multi): {$calltypes}
- behavior (multi): {$behaviors}
- habitat (multi): {$habitats}
- family (single): {$families}
- region (single): {$regions}

OUTPUT FORMAT (STRICT):

Respond with **JSON only**, no explanation, no markdown, in EXACTLY this structure:

{
  "size": "size_token or null",
  "shape_primary": "shape_primary_token or null",
  "shape_secondary": "shape_secondary_token or null",
  "pattern": "pattern_token or null",
  "trait_primary": "trait_primary_token or null",
  "trait_secondary": "trait_secondary_token or null",
  "diet": "diet_token or null",
  "colors": ["color_token1", "color_token2"],
  "call_type": ["call_type_token1"],
  "behavior": ["behavior_token1", "behavior_token2"],
  "habitat": ["habitat_token1", "habitat_token2"],
  "family": "family_token or null",
  "region": "region_token or null"
}

If a field has no suitable token, use null (for single values) or [] (for arrays).

EXAMPLE (for a species like "Laughing Kookaburra (Dacelo novaeguineae)"):

{
  "size": "medium",
  "shape_primary": "stocky",
  "shape_secondary": "long",
  "pattern": "spotted",
  "trait_primary": "perching",
  "trait_secondary": "vocal",
  "diet": "carnivore",
  "colors": ["brown", "white", "blue"],
  "call_type": ["laughing"],
  "behavior": ["social", "territorial"],
  "habitat": ["woodland"],
  "family": null,
  "region": null
}

Now assign facets for this bird species:

{$title}
EOP;

    return $prompt;
}

/**
 * AvianDiscovery facets UI config
 *
 * This assumes the parent plugin already builds a base $config and passes it
 * through the `taxa_facets_frontend_options` filter.
 */

add_filter( 'taxa_facets_frontend_options', 'avian_facets_frontend_options' );
function avian_facets_frontend_options( $config ) {

    // Helper: build [ [slug, label], ... ] from a slug => int enum map.
    if ( ! function_exists( 'avian_facets_enum_options_from_map' ) ) {
        function avian_facets_enum_options_from_map( $map ) {
            $out = array();
            foreach ( $map as $slug => $id ) {
                $out[] = array(
                    'slug'  => $slug,
                    'label' => taxa_facets_pretty_slug( $slug ),
                );
            }
            return $out;
        }
    }

    $overrides = avian_facets_get_ui_overrides();
    $excluded_colors = avian_facets_get_excluded_colors();

    //
    // 1) SIZE – keep using the parent’s, but make sure it’s enabled & labelled
    //
    if ( isset( $config['size'] ) ) {
        $config['size']['enabled'] = avian_facets_get_override_enabled( 'size', true, $overrides );
        $config['size']['label']   = avian_facets_get_override_label( 'size', 'Size similar to a', $overrides );
        // You can also reorder options here if you want.
    }

    //
    // 2) SHAPE PRIMARY – e.g., body build (stocky, slender, etc.)
    //
    $shape_primary_map = taxa_facets_get_shape_primary_enum_map(); // slug => int
    if ( ! empty( $shape_primary_map ) ) {
        $config['shape_primary'] = array(
            'enabled' => avian_facets_get_override_enabled( 'shape_primary', true, $overrides ),
            'label'   => avian_facets_get_override_label( 'shape_primary', 'Body Shape', $overrides ),
            'key'     => 'shape_primary',   // REST param + data-facet
            'type'    => 'enum',
            'multi'   => false,             // one choice at a time
            'options' => avian_facets_enum_options_from_map( $shape_primary_map ),
        );
    }

    //
    // 3) SHAPE SECONDARY – e.g., tail shape
    //
    $shape_secondary_map = taxa_facets_get_shape_secondary_enum_map();
    if ( ! empty( $shape_secondary_map ) ) {
        $config['shape_secondary'] = array(
            'enabled' => avian_facets_get_override_enabled( 'shape_secondary', true, $overrides ),
            'label'   => avian_facets_get_override_label( 'shape_secondary', 'Tail Shape', $overrides ),
            'key'     => 'shape_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => avian_facets_enum_options_from_map( $shape_secondary_map ),
        );
    }

    //
    // 4) PATTERN – spots, stripes, barred, etc.
    //
    $pattern_map = taxa_facets_get_pattern_enum_map();
    if ( ! empty( $pattern_map ) ) {
        $config['pattern'] = array(
            'enabled' => avian_facets_get_override_enabled( 'pattern', false, $overrides ),
            'label'   => avian_facets_get_override_label( 'pattern', 'Pattern', $overrides ),
            'key'     => 'pattern',
            'type'    => 'enum',
            'multi'   => false,
            'options' => avian_facets_enum_options_from_map( $pattern_map ),
        );
    }

    //
    // 5) TRAIT PRIMARY – e.g., perching, wading, aerial, etc.
    //
    $trait_primary_map = taxa_facets_get_trait_primary_enum_map();
    if ( ! empty( $trait_primary_map ) ) {
        $config['trait_primary'] = array(
            'enabled' => avian_facets_get_override_enabled( 'trait_primary', false, $overrides ),
            'label'   => avian_facets_get_override_label( 'trait_primary', 'Primary Trait', $overrides ),
            'key'     => 'trait_primary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => avian_facets_enum_options_from_map( $trait_primary_map ),
        );
    }

    //
    // 6) TRAIT SECONDARY – e.g., vocal, nocturnal, etc.
    //
    $trait_secondary_map = taxa_facets_get_trait_secondary_enum_map();
    if ( ! empty( $trait_secondary_map ) ) {
        $config['trait_secondary'] = array(
            'enabled' => avian_facets_get_override_enabled( 'trait_secondary', false, $overrides ),
            'label'   => avian_facets_get_override_label( 'trait_secondary', 'Secondary Trait', $overrides ),
            'key'     => 'trait_secondary',
            'type'    => 'enum',
            'multi'   => false,
            'options' => avian_facets_enum_options_from_map( $trait_secondary_map ),
        );
    }

    //
    // 7) DIET – carnivore, insectivore, omnivore, etc.
    //
    $diet_map = taxa_facets_get_diet_enum_map();
    if ( ! empty( $diet_map ) ) {
        $config['diet'] = array(
            'enabled' => avian_facets_get_override_enabled( 'diet', false, $overrides ),
            'label'   => avian_facets_get_override_label( 'diet', 'Diet', $overrides ),
            'key'     => 'diet',
            'type'    => 'enum',
            'multi'   => false,
            'options' => avian_facets_enum_options_from_map( $diet_map ),
        );
    }

    //
    // 8) CALL TYPE – laughing, whistle, trill, etc. (bitmask, multi-select)
    //
    $call_type_map = taxa_facets_get_call_type_map();
    if ( ! empty( $call_type_map ) ) {
        $options = array();
        foreach ( $call_type_map as $slug => $bit ) {
            $options[] = array(
                'slug'  => $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
            );
        }

        $config['call_types'] = array(
            'enabled' => avian_facets_get_override_enabled( 'call_types', false, $overrides ),
            'label'   => avian_facets_get_override_label( 'call_types', 'Call Type', $overrides ),
            'key'     => 'call_types',
            'type'    => 'bitmask',
            'multi'   => true,
            'options' => $options,
        );
    }

    //
    // 9) HABITAT – multi-select (bitmask)
    // (parent no longer defines bird-specific defaults)
    //
    $habitat_map = taxa_facets_get_habitat_map(); // slug => bit
    if ( ! empty( $habitat_map ) ) {
        $options = array();
        foreach ( $habitat_map as $slug => $bit ) {
            $options[] = array(
                'slug'  => $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
            );
        }

        $config['habitats'] = array(
            'enabled' => avian_facets_get_override_enabled( 'habitats', true, $overrides ),
            'label'   => avian_facets_get_override_label( 'habitats', 'Habitat', $overrides ),
            'key'     => 'habitats',   // REST param + data-facet
            'type'    => 'bitmask',
            'multi'   => true,
            'options' => $options,
        );
    }

    //
    // 10) BEHAVIOR – multi-select (bitmask)
    // (parent no longer defines bird-specific defaults)
    //
    $behavior_map = taxa_facets_get_behavior_map(); // slug => bit
    if ( ! empty( $behavior_map ) ) {
        $options = array();
        foreach ( $behavior_map as $slug => $bit ) {
            $options[] = array(
                'slug'  => $slug,
                'label' => taxa_facets_pretty_slug( $slug ),
            );
        }

        $config['behaviors'] = array(
            'enabled' => avian_facets_get_override_enabled( 'behaviors', true, $overrides ),
            'label'   => avian_facets_get_override_label( 'behaviors', 'Behavior', $overrides ),
            'key'     => 'behaviors',  // REST param + data-facet
            'type'    => 'bitmask',
            'multi'   => true,
            'options' => $options,
        );
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
function avian_facets_get_ui_overrides() {
    $value = get_option( AVIAN_FACETS_OPTION_UI_OVERRIDES, array() );
    return is_array( $value ) ? $value : array();
}

function avian_facets_get_override_enabled( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ] ) && array_key_exists( 'enabled', $overrides[ $key ] ) ) {
        return (bool) $overrides[ $key ]['enabled'];
    }
    return (bool) $default;
}

function avian_facets_get_override_label( $key, $default, array $overrides ) {
    if ( isset( $overrides[ $key ] ) && isset( $overrides[ $key ]['label'] ) && $overrides[ $key ]['label'] !== '' ) {
        return (string) $overrides[ $key ]['label'];
    }
    return (string) $default;
}

function avian_facets_sanitize_ui_overrides( $value ) {
    $allowed = avian_facets_get_ui_overrides_schema();
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

function avian_facets_get_ui_overrides_schema() {
    return array(
        'size'           => array( 'label' => 'Size similar to a', 'enabled' => true ),
        'shape_primary'  => array( 'label' => 'Body Shape', 'enabled' => true ),
        'shape_secondary'=> array( 'label' => 'Tail Shape', 'enabled' => true ),
        'pattern'        => array( 'label' => 'Pattern', 'enabled' => false ),
        'trait_primary'  => array( 'label' => 'Primary Trait', 'enabled' => false ),
        'trait_secondary'=> array( 'label' => 'Secondary Trait', 'enabled' => false ),
        'diet'           => array( 'label' => 'Diet', 'enabled' => false ),
        'call_types'     => array( 'label' => 'Call Type', 'enabled' => false ),
        'habitats'       => array( 'label' => 'Habitat', 'enabled' => true ),
        'behaviors'      => array( 'label' => 'Behavior', 'enabled' => true ),
    );
}

/**
 * Color exclusions for Avian facets.
 */
function avian_facets_get_excluded_colors() {
    $raw = get_option( AVIAN_FACETS_OPTION_EXCLUDED_COLORS, '' );
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

function avian_facets_sanitize_excluded_colors( $value ) {
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

function avian_facets_sanitize_color_map_raw( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }

    $parts  = preg_split( '/[\r\n,]+/', $value );
    $colors = array();

    foreach ( $parts as $part ) {
        $part = sanitize_key( trim( $part ) );
        if ( $part !== '' && ! in_array( $part, $colors, true ) ) {
            $colors[] = $part;
        }
    }

    return implode( "\n", $colors );
}

function avian_facets_provide_color_map( $colors ) {
    return avian_facets_get_color_map();
}

function avian_facets_filter_color_map( $colors ) {
    if ( ! is_array( $colors ) ) {
        return $colors;
    }

    $excluded = avian_facets_get_excluded_colors();
    if ( empty( $excluded ) ) {
        return $colors;
    }

    foreach ( $excluded as $slug ) {
        unset( $colors[ $slug ] );
    }

    return $colors;
}

/**
 * AvianDiscovery: default taxa rank = genus
 */
add_filter( 'taxa_facets_default_taxa_rank', function( $rank ) {
    // You could add logic here (different pages, etc.) if needed.
    return 'family';
});


/**
 * Rewrite facet labels in the /taxa/v1/search JSON response
 * so "Shape" becomes "Body Shape" and
 * "Shape (secondary)" becomes "Tail Shape".
 */
add_filter( 'rest_post_dispatch', 'avian_relabel_traits_in_taxa_search', 10, 3 );
function avian_relabel_traits_in_taxa_search( $result, $server, $request ) {
    // Only touch our custom search route.
    $route = $request->get_route();
    if ( strpos( $route, '/taxa/v1/search' ) === false ) {
        return $result;
    }

    // Bail if something went wrong upstream.
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

        // Use the admin-driven mapping instead of hard-coded replacements.
        $item['facets'] = avian_facets_adjust_trait_labels(
            $item['facets'],
            ! empty( $item['id'] ) ? (int) $item['id'] : 0
        );
    }
    unset( $item );

    $result->set_data( $data );
    return $result;
}


/**
 * Avian Facets – Admin settings for trait label remapping.
 */

// 1) Add a Settings page under "Settings" menu.
add_action( 'admin_menu', 'avian_facets_add_settings_page' );
function avian_facets_add_settings_page() {
    add_options_page(
        'Avian Facets',                // Page title
        'Avian Facets',                // Menu title
        'manage_options',              // Capability
        'avian-facet-labels',          // Menu slug
        'avian_facets_render_settings_page' // Callback
    );
}

// 2) Register the setting.
add_action( 'admin_init', 'avian_facets_register_settings' );
function avian_facets_register_settings() {
    // This will store the raw textarea text.
    register_setting(
        'avian_facets_labels_group',
        'avian_facets_label_map_raw',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'avian_facets_sanitize_label_map_raw',
            'default'           => '',
        )
    );

    add_settings_section(
        'avian_facets_labels_section',
        'Trait Label Remapping',
        'avian_facets_labels_section_intro',
        'avian-facet-labels'
    );

    add_settings_field(
        'avian_facets_label_map_raw_field',
        'Label Mapping Rules',
        'avian_facets_label_map_raw_field_render',
        'avian-facet-labels',
        'avian_facets_labels_section'
    );

    register_setting(
        'avian_facets_labels_group',
        AVIAN_FACETS_OPTION_UI_OVERRIDES,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'avian_facets_sanitize_ui_overrides',
            'default'           => array(),
        )
    );

    register_setting(
        'avian_facets_labels_group',
        AVIAN_FACETS_OPTION_EXCLUDED_COLORS,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'avian_facets_sanitize_excluded_colors',
            'default'           => '',
        )
    );

    register_setting(
        'avian_facets_labels_group',
        AVIAN_FACETS_OPTION_COLOR_MAP_RAW,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'avian_facets_sanitize_color_map_raw',
            'default'           => '',
        )
    );

    add_settings_section(
        'avian_facets_ui_section',
        'Facet UI Controls',
        'avian_facets_ui_section_intro',
        'avian-facet-labels'
    );

    add_settings_field(
        'avian_facets_ui_overrides_field',
        'Facet Labels & Visibility',
        'avian_facets_ui_overrides_field_render',
        'avian-facet-labels',
        'avian_facets_ui_section'
    );

    add_settings_section(
        'avian_facets_color_section',
        'Color Controls',
        'avian_facets_color_section_intro',
        'avian-facet-labels'
    );

    add_settings_field(
        'avian_facets_color_map_field',
        'Color Map',
        'avian_facets_color_map_field_render',
        'avian-facet-labels',
        'avian_facets_color_section'
    );

    add_settings_field(
        'avian_facets_color_exclusions_field',
        'Exclude Colors',
        'avian_facets_color_exclusions_field_render',
        'avian-facet-labels',
        'avian_facets_color_section'
    );

    add_settings_section(
        'avian_facets_overview_section',
        'Facet Map Overview',
        'avian_facets_overview_section_intro',
        'avian-facet-labels'
    );

    add_settings_field(
        'avian_facets_overview_field',
        'Overrides & Additions',
        'avian_facets_overview_field_render',
        'avian-facet-labels',
        'avian_facets_overview_section'
    );
}

function avian_facets_labels_section_intro() {
    ?>
    <p>
        Define how facet trait prefixes should be renamed in the
        Large Card layout (and anywhere else we use these labels).
    </p>
    <p>
        Use one rule per line in this format:<br>
        <code>Old Prefix|New Prefix</code>
    </p>
    <p>
        For example:<br>
        <code>Shape:|Body Shape:</code><br>
        <code>Shape (secondary):|Tail Shape:</code>
    </p>
    <?php
}

function avian_facets_label_map_raw_field_render() {
    $value = get_option( 'avian_facets_label_map_raw', '' );
    ?>
    <textarea
        name="avian_facets_label_map_raw"
        id="avian_facets_label_map_raw"
        rows="8"
        cols="60"
        class="large-text code"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <?php
}

function avian_facets_ui_section_intro() {
    ?>
    <p>Control which facet filters are visible in the UI and how they are labeled.</p>
    <p>These settings override the defaults defined in this plugin.</p>
    <?php
}

function avian_facets_ui_overrides_field_render() {
    $schema    = avian_facets_get_ui_overrides_schema();
    $overrides = avian_facets_get_ui_overrides();
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
                            name="<?php echo esc_attr( AVIAN_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][enabled]"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr( AVIAN_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][enabled]"
                            value="1"
                            <?php checked( $enabled ); ?>
                        />
                    </label>
                </td>
                <td>
                    <input
                        type="text"
                        class="regular-text"
                        name="<?php echo esc_attr( AVIAN_FACETS_OPTION_UI_OVERRIDES ); ?>[<?php echo esc_attr( $key ); ?>][label]"
                        value="<?php echo esc_attr( $label ); ?>"
                    />
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function avian_facets_color_section_intro() {
    ?>
    <p>Define the color facet slugs for this taxonomy and optionally exclude specific colors. Colors appear in the UI and GPT prompt lists.</p>
    <p><strong>Important:</strong> Do not reorder existing colors once in use. Only append new ones to preserve stored bit positions.</p>
    <?php
}

function avian_facets_color_map_field_render() {
    $value = get_option( AVIAN_FACETS_OPTION_COLOR_MAP_RAW, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( AVIAN_FACETS_OPTION_COLOR_MAP_RAW ); ?>"
        id="<?php echo esc_attr( AVIAN_FACETS_OPTION_COLOR_MAP_RAW ); ?>"
        rows="8"
        cols="60"
        class="large-text code"
        placeholder="e.g. red&#10;orange&#10;yellow"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">Enter one color slug per line or separate with commas. Leave blank to use the default list.</p>
    <?php
}

function avian_facets_color_exclusions_field_render() {
    $value = get_option( AVIAN_FACETS_OPTION_EXCLUDED_COLORS, '' );
    ?>
    <textarea
        name="<?php echo esc_attr( AVIAN_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        id="<?php echo esc_attr( AVIAN_FACETS_OPTION_EXCLUDED_COLORS ); ?>"
        rows="6"
        cols="60"
        class="large-text code"
        placeholder="e.g. pink&#10;purple&#10;gold"
    ><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">Enter one color slug per line or separate with commas.</p>
    <?php
}

function avian_facets_overview_section_intro() {
    ?>
    <p>These are the facet maps and aliases this plugin contributes on top of the core Taxonomy API plugin.</p>
    <?php
}

function avian_facets_overview_field_render() {
    $excluded_colors = avian_facets_get_excluded_colors();
    $color_map = avian_facets_filter_color_map( avian_facets_get_color_map() );
    $sections = array(
        'Color map'       => $color_map,
        'Color aliases'   => avian_facets_color_aliases(),
        'Size enum'       => avian_facets_size_map(),
        'Shape primary'   => avian_facets_shape_primary_map(),
        'Shape secondary' => avian_facets_shape_secondary_map(),
        'Pattern enum'    => avian_facets_pattern_map(),
        'Trait primary'   => avian_facets_trait_primary_map(),
        'Trait secondary' => avian_facets_trait_secondary_map(),
        'Diet enum'       => avian_facets_diet_map(),
        'Call types'      => avian_facets_call_type_map(),
        'Behavior'        => avian_facets_behavior_map(),
        'Habitat'         => avian_facets_habitat_map(),
        'Excluded colors' => empty( $excluded_colors ) ? array() : array_combine( $excluded_colors, $excluded_colors ),
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

function avian_facets_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Avian Facets</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'avian_facets_labels_group' );
            do_settings_sections( 'avian-facet-labels' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Sanitize + normalize the raw textarea text.
// We just trim it; parsing is done at runtime where we need it.
function avian_facets_sanitize_label_map_raw( $value ) {
    $value = is_string( $value ) ? trim( $value ) : '';
    return $value;
}

/**
 * Parse the raw mapping rules from the option into an associative array.
 *
 * Each line should look like:
 *   Old Prefix|New Prefix
 *
 * Example:
 *   Shape:|Body Shape:
 *
 * Returns:
 *   [ 'Shape:' => 'Body Shape:', 'Shape (secondary):' => 'Tail Shape:' ]
 */
function avian_facets_get_label_map() {
    $raw = get_option( 'avian_facets_label_map_raw', '' );
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

        // Expect "old|new"
        $parts = explode( '|', $line, 2 );
        if ( count( $parts ) !== 2 ) {
            continue;
        }

        $old = trim( $parts[0] );
        $new = trim( $parts[1] );

        if ( $old === '' || $new === '' ) {
            continue;
        }

        // We want exact prefix matching, so keep the colon or punctuation as given.
        $map[ $old ] = $new;
    }

    return $map;
}

/**
 * Adjust facet trait labels in the REST response using the admin-defined map.
 *
 * IMPORTANT: Keep the same function name + filter hook you already used
 * when we made the "Shape" → "Body Shape" tweak that worked.
 */
function avian_facets_adjust_trait_labels( $facets, $post_id ) {
    // $facets is expected to be an array of strings like:
    // "Shape: Slender", "Shape (secondary): Long", "Diet: Insectivore", etc.
    if ( ! is_array( $facets ) || empty( $facets ) ) {
        return $facets;
    }

    $map = avian_facets_get_label_map();
    if ( empty( $map ) ) {
        return $facets;
    }

    $out = array();

    foreach ( $facets as $line ) {
        $new_line = $line;

        // For each mapping rule, if the line begins with the "old" prefix,
        // replace that prefix with the "new" one.
        foreach ( $map as $old_prefix => $new_prefix ) {
            // Exact prefix match.
            if ( strpos( $new_line, $old_prefix ) === 0 ) {
                $new_line = $new_prefix . substr( $new_line, strlen( $old_prefix ) );
                // Once one rule matches, we can stop for this line.
                break;
            }
        }

        $out[] = $new_line;
    }

    return $out;
}
