<?php

/**
 * Class SixTenPressSermonsMigrate
 */
class SixTenPressSermonsMigrate {

	/**
	 * @var string
	 */
	protected $post_type = 'sermon';

	/**
	 * @var string
	 */
	protected $prefix = '_sixtenpresssermons';

	/**
	 * @var string
	 */
	protected $key = 'sixtenpresssermons_migration';

	/**
	 *
	 */
	public function run() {
		if ( ! $this->get_old_sermons() ) {
			deactivate_plugins( SIXTENPRESSSERMONSMIGRATE_BASENAME );
			$message = sprintf( __( 'It looks like you don\'t have any sermons to convert, so Six/Ten Press Sermons Migration has been deactivated. <a href="%s">Return to the plugins page.</a>', 'sixtenpress-sermons-migrate' ), esc_url( admin_url() . 'plugins.php' ) );
			wp_die( wp_kses_post( $message ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$this->convert_sermon_manager();
		$this->delete_option();
//		$this->update_options();
		add_action( 'admin_notices', array( $this, 'message' ) );
	}

	/**
	 * Admin message.
	 */
	public function message() {
		if ( ! $this->get_old_sermons() ) {
			update_option( $this->key, 1 );
			$message = '<p>' . __( 'Woo hoo! Looks like there is nothing left to do here.', 'sixtenpress-sermons' ) . '</p>';
			echo '<div class="notice notice-info">' . wp_kses_post( $message ) . '</div>';
			return;
		} else {
			$message  = '<p>' . sprintf( __( 'Six/Ten Press Sermons Migration will attempt to migrate your sermons over to Six/Ten Press. This process will modify your database, so please make sure that your site is fully backed up before you do anything else. <strong>Additionally, you\'ll want to use the <a href="%s">Export tool</a> to export all of your Sermons, just in case.</strong>', 'sixtenpress-sermons-migrate' ), esc_url( admin_url() . 'export.php' ) ) . '</p>';
			$message .= '<p>' . sprintf( __( 'Before you click the migration button, make sure that both Sermon Manager and Six/Ten Press Sermons are active. Also, please <a href="%s">enable all of the Six/Ten taxonomies</a>, even if you are not planning on using them. You can disable the unneeded ones later.', 'sixtenpress-sermons-migrate' ), esc_url( admin_url() . 'edit.php?post_type=sermon&page=sixtenpresssermons&tab=taxonomies' ) ) . '</p>';
			$message .= '<p>' . __( 'You can test the plugin by migrating just one sermon, so you can make sure it worked properly, migrate sermons in batches of 25 at a time, or copy all sermons from one content type to the other.', 'sixtenpress-sermons-migration' ) . '</p>';
			$message .= '<p>' . sprintf( __( 'What\'s the difference between migrating and copying sermons? Migrating will actually move the existing sermons from Sermon Manager; copying will simply copy them instead. Copying is more resource intensive, since it will go through each and every sermon, but it may be safer because it will leave the originals intact. If you choose to copy sermons, please <a href="%s">visit the Sermon Manager settings page</a> and change your archive slug to something other than sermons.', 'sixtenpress-sermons-migration' ), esc_url( admin_url() . 'edit.php?post_type=wpfc_sermon&page=sermon-manager-for-wordpress%2Fincludes%2Foptions.php' ) ) . '</p>';
		}
		echo '<div class="notice notice-info">' . wp_kses_post( $message );
		echo '<form action="" method="post">';
		wp_nonce_field( $this->key . '_metanonce', $this->key . '_metanonce', false );
		$buttons = array(
			array(
				'value' => __( 'Migrate One Sermon', 'sixtenpress-sermons-migrate' ),
				'name'  => 'sixtenpresssermonsmigrate_test',
				'class' => 'button-primary',
			),
			array(
				'value' => __( 'Migrate Sermon Batch', 'sixtenpress-sermons-migrate' ),
				'name'  => 'sixtenpresssermonsmigrate_go',
				'class' => 'button-secondary',
			),
			array(
				'value' => __( 'Copy All Sermons', 'sixtenpress-sermons-migrate' ),
				'name'  => 'sixtenpresssermonsmigrate_copy',
				'class' => 'button-secondary',
			),
		);
		$setting = get_option( $this->key, false );
		if ( $setting ) {
			$buttons[] = array(
				'value' => __( 'Reset the Migration Process', 'sixtenpress-sermons-migrate' ),
				'name'  => 'sixtenpresssermonsmigrate_reset',
				'class' => 'button-secondary',
			);
		}
		if ( class_exists( 'SermonManager' ) ) {
			echo $this->do_buttons( $buttons );
		}
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Build the buttons for output.
	 *
	 * @param $buttons
	 *
	 * @return string
	 */
	protected function do_buttons( $buttons ) {
		$output = '<p>';
		foreach ( $buttons as $button ) {
			$output .= sprintf( '<input type="submit" class="%s" name="%s" value="%s" style="margin-right:12px;" />',
				esc_attr( $button['class'] ),
				esc_attr( $button['name'] ),
				esc_attr( $button['value'] )
			);
		}
		$output .= '</p>';

		return $output;
	}

	public function delete_option() {
		if ( ! isset( $_POST['sixtenpresssermonsmigrate_reset'] ) ) {
			return;
		}
		if ( ! check_admin_referer( $this->key . '_metanonce', $this->key . '_metanonce' ) ) {
			return;
		}
		delete_option( $this->key );
	}

	/**
	 * Convert the old sermons to Six/Ten Press.
	 */
	public function convert_sermon_manager() {

		if ( ! isset( $_POST['sixtenpresssermonsmigrate_go'] ) && ! isset( $_POST['sixtenpresssermonsmigrate_test'] ) && ! isset( $_POST['sixtenpresssermonsmigrate_copy'] ) ) {
			return;
		}
		if ( ! check_admin_referer( $this->key . '_metanonce', $this->key . '_metanonce' ) ) {
			return;
		}

		$method = 'migrate';
		$number = isset( $_POST['sixtenpresssermonsmigrate_test'] ) ? 1 : 25;
		if ( isset( $_POST['sixtenpresssermonsmigrate_copy'] ) ) {
			$method = 'insert';
			$number = -1;
		}
		$items  = $this->get_old_sermons( $number );
		if ( ! $items ) {
			update_option( $this->key, 1 );
			$this->do_description( __( 'Could not find any sermons to convert.', 'sixtenpress-sermons' ), 'error' );

			return;
		}

		foreach ( $items as $post ) {
			$do = $method . '_posts';
			$this->$do( $post );
		}

		$this->do_description( __( 'Sermons converted, thank you!', 'sixtenpress-sermons' ), 'updated' );
	}

	protected function migrate_posts( $post ) {
		$this->update_sermon_date( $post->ID );
		$this->update_post_meta( $post->ID );
		set_post_type( $post->ID, $this->post_type );
		$this->update_description( $post->ID );
		$this->update_terms( $post->ID );
		$this->update_occasion( $post->ID );
		$this->delete_post_meta( $post->ID );
	}

	public function update_options() {
		if ( ! isset( $_POST['sixtenpresssermonsmigrate_options'] ) ) {
			return;
		}
		if ( ! check_admin_referer( $this->key . '_metanonce', $this->key . '_metanonce' ) ) {
			return;
		}
		$old_options = get_option( 'wpfc_options', array() );
		if ( ! $old_options ) {
			return;
		}
		$settings = array(
			'webmaster_email'    => 'email',
			'itunes_subtitle'    => 'subtitle',
			'itunes_summary'     => 'summary',
			'itunes_owner_email' => 'email',
			'itunes_cover_image' => 'image',
		);
		foreach ( $settings as $key => $value ) {
			if ( isset( $old_options[ $key ] ) ) {
				if ( 'itunes_cover_image' === $key ) {
					$old_options[ $key ] = url_to_postid( $old_options[ $key ] );
				}
				$this->update_settings( array(
						$value => $old_options[ $key ],
					)
				);
			}
		}
	}

	/**
	 * Get the Sermon Manager sermons as an array.
	 * @return array
	 */
	protected function get_old_sermons( $number = 1 ) {
		$query = array(
			'posts_per_page' => $number,
			'post_status'    => 'any',
			'post_type'      => 'wpfc_sermon',
			'order'          => 'ASC',
		);

		return get_posts( $query );
	}

	/**
	 * Not used.
	 *
	 * @param $post
	 */
	protected function insert_posts( $post ) {
		$post_meta = get_post_meta( $post->ID );
		$new_meta  = array();
		foreach ( $post_meta as $key => $value ) {
			foreach ( $value as $m ) {
				$new_meta[ $key ] = $m ;
			}
		}
		$id = wp_insert_post( array(
			'post_title'     => $post->post_title,
			'post_type'      => $this->post_type,
			'post_content'   => $this->get_description( $post->ID ),
			'post_status'    => $post->post_status,
			'meta_input'     => $new_meta,
			'import_id'      => $post->post_id,
			'post_author'    => $post->post_author,
			'post_date'      => $post->post_date,
			'post_date_gmt'  => $post->post_date_gmt,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'guid'           => $post->guid,
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
			'post_password'  => $post->post_password,
		) );

		$this->update_sermon_date( $id, $post->ID );
		$this->update_post_meta( $id, $post->ID );
		$this->update_terms( $id, $post->ID );
		$this->update_occasion( $id, $post->ID );
		$this->delete_post_meta( $id );

	}

	/**
	 * Update the sermon date.
	 *
	 * @param $post_id
	 */
	protected function update_sermon_date( $post_id, $old_id = '' ) {
		$old  = $old_id ? $old_id : $post_id;
		$meta = get_post_meta( $old, 'sermon_date', true );
		update_post_meta( $post_id, $this->prefix . '_date', $meta );
	}

	/**
	 * Update the main sermon custom field.
	 *
	 * @param $post_id
	 */
	protected function update_post_meta( $post_id, $old_id = '' ) {
		$old   = $old_id ? $old_id : $post_id;
		$array = $this->get_post_meta( $old );
		update_post_meta( $post_id, $this->prefix, $array );
	}

	/**
	 * Get the Sermon Manager post meta fields which correspond to 6/10.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	protected function get_post_meta( $post_id ) {
		return array(
			'mp3'       => get_post_meta( $post_id, 'sermon_audio', true ),
			'mp3_id'    => attachment_url_to_postid( get_post_meta( $post_id, 'sermon_audio', true ) ),
			'video'     => '',
			'video_id'  => '',
			'file'      => get_post_meta( $post_id, 'sermon_notes', true ),
			'file_id'   => attachment_url_to_postid( get_post_meta( $post_id, 'sermon_notes', true ) ),
			'scripture' => get_post_meta( $post_id, 'bible_passage', true ),
		);
	}

	/**
	 * Copy the sermon description and embedded video to the post content.
	 *
	 * @param $post_id
	 */
	protected function update_description( $post_id ) {
		$description = $this->get_description( $post_id );
		if ( ! $description ) {
			return;
		}
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => $description,
		) );
	}

	/**
	 * Get the old post's sermon description and embedded video.
	 *
	 * @param $post_id
	 *
	 * @return mixed|string
	 */
	protected function get_description( $post_id ) {
		$description = get_post_meta( $post_id, 'sermon_description', true );
		$video       = get_post_meta( $post_id, 'sermon_video', true );
		if ( $video ) {
			$description .= '<br />' . $video;
		}

		return $description;
	}

	/**
	 * Update the terms.
	 *
	 * @param $post_id
	 */
	protected function update_terms( $post_id, $old_id = '' ) {
		$old        = $old_id ? $old_id : $post_id;
		$taxonomies = array(
			'book'     => 'wpfc_bible_book',
			'preacher' => 'wpfc_preacher',
			'series'   => 'wpfc_sermon_series',
			'topic'    => 'wpfc_sermon_topics',
		);
		foreach ( $taxonomies as $key => $value ) {
			$terms = wp_get_object_terms( $old, $value, array( 'fields' => 'slugs' ) );
			foreach ( $terms as $term ) {
				wp_set_object_terms( $post_id, $term, $key, false );
			}
		}
	}

	/**
	 * Convert the service type (custom field) to occasion (taxonomy).
	 *
	 * @param $post_id
	 */
	protected function update_occasion( $post_id, $old_id = '' ) {
		$old      = $old_id ? $old_id : $post_id;
		$occasion = get_post_meta( $old, 'wpfc_service_type_select', true );
		if ( ! $occasion ) {
			return;
		}
		foreach ( $occasion as $o ) {
			$term = get_term_by( 'id', (int) $o, 'wpfc_service_type', 'ARRAY_A' );
			wp_set_object_terms( $post_id, $term['slug'], 'occasion', false );
		}
	}

	protected function delete_post_meta( $post_id ) {
		$array = array( 'sermon_date', 'sermon_audio', 'Views', 'sermon_description', 'bible_passage', 'wpfc_service_type_select' );
		foreach ( $array as $a ) {
			delete_post_meta( $post_id, $a );
		}
	}

	/**
	 * Print the admin notice.
	 *
	 * @param $description
	 * @param string $class
	 */
	protected function do_description( $description, $class = 'notice-info' ) {
		printf( '<div class="notice%s"><p>%s</p></div>', ' ' . esc_attr( $class ), esc_html( $description ) );
	}

	/**
	 * Update a key of an option.
	 *
	 * @param string $new
	 * @param string $setting
	 *
	 * @return bool
	 */
	protected function update_settings( $new = '', $setting = 'sixtenpresssermons' ) {
		return update_option( $setting, wp_parse_args( $new, get_option( $setting ) ) );
	}
}
