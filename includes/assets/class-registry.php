<?php
/**
 * Asset registry (custom table). Stores metadata only; files live in uploads.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets;

use DashWoo\Support\Filesystem;

defined( 'ABSPATH' ) || exit;

/**
 * Registry repository.
 */
final class Registry {

	const CACHE_GROUP = 'dashwoo';

	/**
	 * Singleton.
	 *
	 * @var Registry|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Fully qualified table name.
	 *
	 * @return string
	 */
	public function table() {
		global $wpdb;

		return $wpdb->prefix . 'dashwoo_assets';
	}

	/**
	 * Asset types handled by the registry.
	 *
	 * @return array<int,string>
	 */
	public function types() {
		return array( 'font', 'icon', 'image', 'svg', 'custom' );
	}

	/**
	 * Normalise a row for writing.
	 *
	 * @param array<string,mixed> $data    Raw row.
	 * @param bool                $partial Only normalize the keys that were given
	 *                                     (used by update(), so a partial write can
	 *                                     never blank out the other columns).
	 * @return array<string,mixed>
	 */
	public function normalize( array $data, $partial = false ) {
		$row = array(
			'type'       => isset( $data['type'] ) && in_array( $data['type'], $this->types(), true ) ? $data['type'] : 'font',
			'group_key'  => isset( $data['group_key'] ) ? sanitize_key( $data['group_key'] ) : '',
			'slug'       => isset( $data['slug'] ) ? $this->slug( $data['slug'] ) : '',
			'label'      => isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '',
			'provider'   => isset( $data['provider'] ) ? sanitize_key( $data['provider'] ) : 'local',
			'version'    => isset( $data['version'] ) ? sanitize_text_field( $data['version'] ) : '',
			'status'     => isset( $data['status'] ) && in_array( $data['status'], array( 'active', 'disabled', 'missing', 'updating' ), true ) ? $data['status'] : 'active',
			'is_default' => ! empty( $data['is_default'] ) ? 1 : 0,
			'role'       => isset( $data['role'] ) ? sanitize_key( $data['role'] ) : '',
			'path'       => isset( $data['path'] ) ? ltrim( str_replace( '\\', '/', (string) $data['path'] ), '/' ) : '',
			'url'        => isset( $data['url'] ) ? esc_url_raw( (string) $data['url'] ) : '',
			'size'       => isset( $data['size'] ) ? (int) $data['size'] : 0,
			'meta'       => isset( $data['meta'] ) ? ( is_array( $data['meta'] ) ? $data['meta'] : array() ) : array(),
		);

		if ( '' !== $row['path'] && false !== strpos( $row['path'], '..' ) ) {
			$row['path'] = '';
		}

		if ( $partial ) {
			$row = array_intersect_key( $row, $data );
		}

		return $row;
	}

	/**
	 * Slugify a family / asset name (keeps Persian letters, transliterates nothing).
	 *
	 * @param string $value Raw slug.
	 * @return string
	 */
	public function slug( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9\-\p{Arabic}\p{Cyrillic}]+/u', '-', $value );
		$value = preg_replace( '/-{2,}/', '-', (string) $value );
		$value = trim( (string) $value, '-' );

		return substr( $value, 0, 180 );
	}

	/**
	 * Insert a row.
	 *
	 * @param array<string,mixed> $data Row.
	 * @return int New id, or 0.
	 */
	public function insert( array $data ) {
		global $wpdb;

		$row               = $this->normalize( $data );
		$row['meta']       = wp_json_encode( $row['meta'] );
		$row['created_at'] = gmdate( 'Y-m-d H:i:s' );
		$row['updated_at'] = $row['created_at'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert( $this->table(), $row );

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update a row.
	 *
	 * @param int                 $id   Row id.
	 * @param array<string,mixed> $data Partial row.
	 * @return bool
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$row = $this->normalize( $data, true );

		if ( array_key_exists( 'meta', $data ) ) {
			$row['meta'] = wp_json_encode( is_array( $data['meta'] ) ? $data['meta'] : array() );
		} else {
			unset( $row['meta'] );
		}

		$row['updated_at'] = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update( $this->table(), $row, array( 'id' => (int) $id ) );
	}

	/**
	 * Find one row by id.
	 *
	 * @param int $id Row id.
	 * @return array<string,mixed>|null
	 */
	public function find( $id ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE id = %d LIMIT 1', (int) $id ); // phpcs:ignore WordPress.DB.PreparedSQL

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$row = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? $this->hydrate( $row ) : null;
	}

	/**
	 * Find by type + slug.
	 *
	 * @param string $type Asset type.
	 * @param string $slug Slug.
	 * @return array<string,mixed>|null
	 */
	public function find_by_slug( $type, $slug ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT * FROM ' . $this->table() . ' WHERE type = %s AND slug = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL
			(string) $type,
			$this->slug( $slug )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$row = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? $this->hydrate( $row ) : null;
	}

	/**
	 * Query rows.
	 *
	 * @param array<string,mixed> $args type, status, provider, role, search, is_default, per_page, page, orderby, order.
	 * @return array<int,array<string,mixed>>
	 */
	public function query( array $args = array() ) {
		global $wpdb;

		$args = array_merge(
			array(
				'type'       => '',
				'status'     => '',
				'provider'   => '',
				'role'       => '',
				'search'     => '',
				'is_default' => null,
				'per_page'   => 50,
				'page'       => 1,
				'orderby'    => 'label',
				'order'      => 'ASC',
			),
			$args
		);

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $args['type'] ) {
			$where[]  = 'type = %s';
			$params[] = (string) $args['type'];
		}
		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}
		if ( '' !== $args['provider'] ) {
			$where[]  = 'provider = %s';
			$params[] = (string) $args['provider'];
		}
		if ( '' !== $args['role'] ) {
			$where[]  = 'role = %s';
			$params[] = (string) $args['role'];
		}
		if ( null !== $args['is_default'] ) {
			$where[]  = 'is_default = %d';
			$params[] = $args['is_default'] ? 1 : 0;
		}
		if ( '' !== $args['search'] ) {
			$where[]  = '(label LIKE %s OR slug LIKE %s)';
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$orderby = in_array( strtolower( (string) $args['orderby'] ), array( 'id', 'label', 'slug', 'type', 'updated_at', 'created_at', 'size' ), true )
			? strtolower( (string) $args['orderby'] )
			: 'label';
		$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$per_page = max( 1, min( 500, (int) $args['per_page'] ) );
		$offset   = max( 0, ( ( max( 1, (int) $args['page'] ) - 1 ) * $per_page ) );

		$sql = 'SELECT * FROM ' . $this->table() . ' WHERE ' . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL
		$prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $prepared, ARRAY_A );

		return array_map( array( $this, 'hydrate' ), (array) $rows );
	}

	/**
	 * Count rows for a query.
	 *
	 * @param array<string,mixed> $args Same as query() without pagination.
	 * @return int
	 */
	public function count( array $args = array() ) {
		$args['per_page'] = 500;
		$args['page']     = 1;
		$all              = $this->query( $args );

		return count( $all );
	}

	/**
	 * Delete a row (and optionally its files).
	 *
	 * @param int  $id         Row id.
	 * @param bool $delete_files Remove files from disk too.
	 * @return bool
	 */
	public function delete( $id, $delete_files = true ) {
		global $wpdb;

		$row = $this->find( $id );
		if ( ! $row ) {
			return false;
		}

		if ( $delete_files ) {
			$storage = Storage::instance();
			$abs     = $storage->absolute( $row['path'] );

			if ( '' !== $abs && Filesystem::is_inside( $storage->basedir(), $abs ) ) {
				$folder = dirname( $abs );

				if ( is_dir( $abs ) ) {
					Filesystem::delete_dir( $abs );
				} else {
					Filesystem::delete( $abs );
				}

				// Assets live in their own {type}/{slug}/ folder: remove it too, but
				// only when it really belongs to this asset (never a type root).
				if ( is_dir( $folder ) && basename( $folder ) === $row['slug'] && $folder !== $storage->basedir() ) {
					Filesystem::delete_dir( $folder );
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->delete( $this->table(), array( 'id' => (int) $id ) );
	}

	/**
	 * Mark one asset as the default of its group and clear the others.
	 *
	 * @param int    $id        Row id.
	 * @param string $group_key Optional explicit group.
	 * @return bool
	 */
	public function set_default( $id, $group_key = '' ) {
		global $wpdb;

		$row = $this->find( $id );
		if ( ! $row ) {
			return false;
		}

		$group = '' !== $group_key ? $group_key : $row['group_key'];
		$type  = $row['type'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $this->table(), array( 'is_default' => 0 ), array( 'type' => $type ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update(
			$this->table(),
			array(
				'is_default' => 1,
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => (int) $id )
		) && ( '' === $group || true );
	}

	/**
	 * Upsert by (type, slug).
	 *
	 * @param array<string,mixed> $data Row.
	 * @return int Row id.
	 */
	public function upsert( array $data ) {
		$existing = $this->find_by_slug( $data['type'] ?? 'font', $data['slug'] ?? '' );

		if ( $existing ) {
			$this->update( $existing['id'], $data );

			return (int) $existing['id'];
		}

		return $this->insert( $data );
	}

	/**
	 * Hydrate a DB row (decode meta, cast types).
	 *
	 * @param array<string,mixed> $row Raw row.
	 * @return array<string,mixed>
	 */
	public function hydrate( $row ) {
		$out               = (array) $row;
		$out['id']         = (int) ( $out['id'] ?? 0 );
		$out['is_default'] = ! empty( $out['is_default'] );
		$out['size']       = (int) ( $out['size'] ?? 0 );

		$meta = isset( $out['meta'] ) ? $out['meta'] : '';
		if ( is_string( $meta ) && '' !== $meta ) {
			$decoded = json_decode( $meta, true );
			$meta    = is_array( $decoded ) ? $decoded : array();
		}

		$out['meta'] = is_array( $meta ) ? $meta : array();

		return $out;
	}
}
