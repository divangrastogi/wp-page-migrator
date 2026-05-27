<?php
/**
 * File: wp-content/plugins/wp-page-migrator/includes/Handlers/URLRewriter.php
 * Hooks: None (Utility class)
 */

/* ── BACKEND CONTRACT FULFILLED ─────────────────────
   Utility  : \WPM\Handlers\URLRewriter::rewrite
   Purpose  : Rewrite URLs and remap media IDs during migration.
   Input    : mixed $value, string $old_url, string $new_url, array $media_id_map
   Output   : mixed (processed value, maintains serialization/format)
   Features : Serialized data, Elementor JSON, Recursive Arrays, Plain Strings.
   ─────────────────────────────────────────────── */

namespace WPM\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class URLRewriter
 *
 * Handles rewriting URLs and remapping media IDs in various data formats.
 *
 * @package WPM\Handlers
 */
class URLRewriter {

	/**
	 * Rewrites URLs and remaps media IDs in a given value.
	 *
	 * Handles serialized data, JSON strings (Elementor), arrays, and plain strings.
	 *
	 * @param mixed  $value        The value to process.
	 * @param string $old_url      The source site URL.
	 * @param string $new_url      The destination site URL.
	 * @param array  $media_id_map Optional. Map of old media IDs to new ones [old_id => new_id].
	 *
	 * @return mixed The processed value.
	 */
	public static function rewrite( $value, $old_url, $new_url, $media_id_map = array() ) {
		if ( empty( $value ) ) {
			return $value;
		}

		// 1. Unserialize if needed.
		$unserialized = maybe_unserialize( $value );

		// 2. If it's an array, recurse.
		if ( is_array( $unserialized ) ) {
			foreach ( $unserialized as $key => $val ) {
				$unserialized[ $key ] = self::rewrite( $val, $old_url, $new_url, $media_id_map );
			}
			return is_serialized( $value ) ? serialize( $unserialized ) : $unserialized;
		}

		// 3. If it's a string, handle JSON and plain text.
		if ( is_string( $unserialized ) ) {
			$trimmed = trim( $unserialized );

			// Check for JSON (specifically for Elementor data which starts with [ or {).
			if ( ! empty( $trimmed ) && ( '[' === $trimmed[0] || '{' === $trimmed[0] ) ) {
				$decoded = json_decode( $unserialized, true );

				if ( json_last_error() === JSON_ERROR_NONE ) {
					// For JSON, we do string replacement on the re-encoded string as per spec.
					// We use JSON_UNESCAPED_SLASHES to ensure URLs match exactly.
					$json_str = json_encode( $decoded, JSON_UNESCAPED_SLASHES );
					$json_str = str_replace( $old_url, $new_url, $json_str );

					if ( ! empty( $media_id_map ) ) {
						foreach ( $media_id_map as $old_id => $new_id ) {
							$json_str = str_replace( '"' . $old_id . '"', '"' . $new_id . '"', $json_str );
						}
					}

					return is_serialized( $value ) ? serialize( $json_str ) : $json_str;
				}
			}

			// Plain string replacement for URL.
			$processed = str_replace( $old_url, $new_url, $unserialized );

			// Remap attachment IDs if they appear in strings (e.g., as part of shortcodes).
			if ( ! empty( $media_id_map ) ) {
				foreach ( $media_id_map as $old_id => $new_id ) {
					$processed = str_replace( '"' . $old_id . '"', '"' . $new_id . '"', $processed );
				}
			}

			return is_serialized( $value ) ? serialize( $processed ) : $processed;
		}

		// 4. Return as is if not a string or array (e.g., integer).
		if ( ! empty( $media_id_map ) && ( is_int( $unserialized ) || is_numeric( $unserialized ) ) ) {
			if ( isset( $media_id_map[ $unserialized ] ) ) {
				$new_val = $media_id_map[ $unserialized ];
				$unserialized = is_int( $unserialized ) ? (int) $new_val : $new_val;
			}
		}

		return is_serialized( $value ) ? serialize( $unserialized ) : $unserialized;
	}
}
