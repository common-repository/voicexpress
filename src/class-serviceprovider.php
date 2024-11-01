<?php
/**
 * Service provider for Voicexpress
 *
 * @category Voicexpress
 * @package  Voicexpress
 * @author   ER Soluções Web LTDA <contato@ersolucoesweb.com.br>
 * @license  MIT  https://ersolucoesweb.com.br
 * @link     https://ersolucoesweb.com.br
 */

namespace Voicexpress;

/**
 * Voicexpress service provider
 *
 * @category Voicexpress
 * @package  Voicexpress
 * @author   ER Soluções Web LTDA <contato@ersolucoesweb.com.br>
 * @license  MIT  https://ersolucoesweb.com.br
 * @link     https://ersolucoesweb.com.br
 */
class ServiceProvider {

	/**
	 * Voicexpress app url
	 *
	 * @var string
	 */

	public $app_url = 'https://voicexpress.app';

	/**
	 * Initialize class
	 */
	public function __construct() {
		$app_url = getenv( 'VOICEXPRESS_APP_URL' );
		if ( $app_url ) {
			$this->app_url = $app_url;
		}
	}

	/**
	 * Start plugin
	 *
	 * @return void
	 */
	public function boot() {
		load_plugin_textdomain(
			'voicexpress',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

		add_filter(
			'bulk_actions-edit-post',
			function ( $bulk_actions ) {
				$bulk_actions['voicexpress_update_audio'] = 'Atualizar áudios';
				return $bulk_actions;
			}
		);

		add_filter(
			'handle_bulk_actions-edit-post',
			function ( $redirect_to, $doaction, $post_ids ) {
				if ( 'voicexpress_update_audio' !== $doaction ) {
					return $redirect_to;
				}

				foreach ( $post_ids as $post_id ) {
					$this->voicexpress_send_post( $post_id, true );
				}

				$redirect_to = add_query_arg( 'bulk_voicexpress_update_audio', count( $post_ids ), $redirect_to );
				return $redirect_to;
			},
			10,
			3
		);

		add_action(
			'admin_notices',
			function () {
				if ( isset( $_REQUEST['bulk_voicexpress_update_audio'] ) && ! empty( $_REQUEST['bulk_voicexpress_update_audio'] ) ) {
					$processed_count = intval( $_REQUEST['bulk_voicexpress_update_audio'] );
					echo wp_kses_post(
						sprintf(
							'<div id="message" class="updated fade"><p>' .
							// translators: %s: numeros de áudios a gerar.
							_n(
								'%s áudio está  sendo atualizado.',
								'%s áudios estão  sendo atualizados.',
								$processed_count,
								'text-domain'
							) . '</p></div>',
							$processed_count
						)
					);
				}
			}
		);

		add_action( 'wp_ajax_voicexpress_notification', array( $this, 'voicexpress_notification' ) );
		add_action( 'wp_ajax_nopriv_voicexpress_notification', array( $this, 'voicexpress_notification' ) );

		add_action(
			'wp_ajax_voicexpress_get_post',
			function () {
				if ( ! isset( $_GET['nonce'] ) ) {
					die( 'nonce not is set' );
				}
				$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'voicexpress' ) ) {
					die( 'invalid nonce' );
				}
				if ( ! isset( $_GET['post_id'] ) ) {
					return;
				}
				$post_id = wp_unslash( (int) $_GET['post_id'] );
				$version = get_post_meta( $post_id, '_voicexpress_audio', true );
				$url     = 'https://cdn.voicexpress.app/audios/' . $version . '.mp3';
				$credits = $this->get_credits();
				wp_send_json_success(
					array(
						'url'     => $url,
						'credits' => $credits,
					)
				);
			}
		);

		add_action(
			'wp_ajax_voicexpress_send_post',
			function () {
				if ( ! isset( $_GET['nonce'] ) ) {
					die( 'nonce not is set' );
				}
				$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'voicexpress' ) ) {
					die( 'invalid nonce' );
				}
				if ( ! isset( $_GET['post_id'] ) ) {
					return;
				}
				$post_id = wp_unslash( (int) $_GET['post_id'] );
				$this->voicexpress_send_post( $post_id );
				$version = get_post_meta( $post_id, '_voicexpress_audio', true );
				$url     = 'https://cdn.voicexpress.app/audios/' . $version . '.mp3';
				wp_send_json_success(
					array(
						'url' => $url,
					)
				);
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_enqueue_script( 'voicexpress', $this->app_url . '/app.js', array( 'jquery' ), time(), true );
				wp_localize_script(
					'voicexpress',
					'voicexpress_ajax_var',
					array(
						'url'   => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'voicexpress' ),
					)
				);
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_script( 'voicexpress', $this->app_url . '/admin.js', array( 'jquery' ), time(), true );
				wp_localize_script(
					'voicexpress',
					'voicexpress_ajax_var',
					array(
						'url'   => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'voicexpress' ),
					)
				);
			}
		);

		add_filter(
			'the_content',
			function ( $content ) {
				global $post;
				if ( is_single() && 'post' == $post->post_type ) {
					$post_id = get_the_ID();
					if ( get_option( 'voicexpress_position' ) == 'after' ) {
						$content = $content . do_shortcode( "[voicexpress post_id='{$post_id}']" );
					} else {
						$content = do_shortcode( "[voicexpress post_id='{$post_id}']" ) . $content;
					}
				}
				return $content;
			}
		);

		add_shortcode(
			'voicexpress',
			function ( $atts ) {
				if ( ! isset( $atts['post_id'] ) ) {
					$atts['post_id'] = get_the_ID();
				}
				if ( empty( $atts['post_id'] ) ) {
					return;
				}
				$version = get_post_meta( $atts['post_id'], '_voicexpress_audio', true );
				if ( empty( $version ) || 'Compre mais créditos' == $version ) {
					return;
				}
				$audio = 'https://cdn.voicexpress.app/audios/' . $version . '.mp3';
				return '<div id="voicexpress" style="margin:15px 0;position:relative;"><audio style="width:100%;z-index:1;position:relative;" controls><source src="' . $audio . '" type="audio/mpeg"></audio><a style="display:table!important;letter-spacing:0.5px;vibility:visible!important;width:auto;margin:-22px auto 0 auto;z-index:2;font-size:9px;line-height:9px;left:0;text-transform:uppercase;text-align:center;color:#333;text-decoration:none;position:relative;" href="' . $this->app_url . '" target="_blank" title="Voicexpress">audio by <strong>Voicexpress</strong></a></div>';
			}
		);

		add_action(
			'save_post',
			function ( $post_id ) {

				$nonce = isset( $_POST['voicexpress_metabox_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['voicexpress_metabox_nonce'] ) ) : false;

				if ( ! $nonce || ! wp_verify_nonce( $nonce, 'voicexpress_save_metabox' ) ) {
					return;
				}

				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}

				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}

				if ( isset( $_POST['_voicexpress_gerar_audio'] ) && 1 == $_POST['_voicexpress_gerar_audio'] ) {
					update_post_meta( $post_id, '_voicexpress_gerar_audio', 1 );
				} else {
					update_post_meta( $post_id, '_voicexpress_gerar_audio', 0 );
				}

				$this->voicexpress_send_post( $post_id );
			}
		);

		add_action(
			'add_meta_boxes',
			function () {
				add_meta_box(
					'voicexpress',
					'Voicexpress',
					function ( $post ) {
						$gerar_audio = get_post_meta( $post->ID, '_voicexpress_gerar_audio', true );
						if ( ! metadata_exists( 'post', $post->ID, '_voicexpress_gerar_audio' ) ) {
							$gerar_audio = get_option( 'voicexpress_auto', 1 );
						}
						$credits = get_option( 'voicexpress_credits', 0 );
						$post_id = (int) $post->ID;
						wp_nonce_field( 'voicexpress_save_metabox', 'voicexpress_metabox_nonce' );
						echo esc_html__( 'Créditos', 'voicexpress' ) . ': <span id="voicexpress-credits">' . esc_html( $credits ? $credits : 0 ) . '</span><br/><br/>';
						echo 'Shortcode: <input readonly style="border:none;height:30px;padding:0 15px 0 15px;border-radius:5px;width:100%;" value=\'[voicexpress post_id="' . esc_html( $post_id ) . '"]\' /><br/><br/>';
						echo '<label style="display:block;margin-bottom:15px;"><input ' . checked( 1, $gerar_audio, false ) . ' type="checkbox" name="_voicexpress_gerar_audio" value="1" /> Gerar áudio</label>';
						echo do_shortcode( "[voicexpress post_id='" . esc_html( $post_id ) . "']" );
						echo '<a class="button button-large button-primary" style="width:100%;background:#111;text-align:center;border:none;" href="' . esc_html( $this->app_url ) . '?source=' . esc_html( get_home_url() ) . '" target="_blank">' . esc_html__( 'Adicionar créditos', 'voicexpress' ) . '</a>';
					},
					'post',
					'side',
					'default'
				);
			}
		);

		add_action(
			'admin_head',
			function () {
				if ( empty( get_option( 'voicexpress_secret_key', '' ) ) ) {
					$this->voicexpress_install();
				}
			}
		);

		add_action( 'admin_menu', array( $this, 'voicexpress_options_page' ) );

		add_action( 'admin_init', array( $this, 'voicexpress_register_settings' ) );
	}

	/**
	 * Settings
	 *
	 * @return void
	 */
	public function voicexpress_register_settings() {
		register_setting( 'voicexpress-settings', 'voicexpress_position' );
		register_setting( 'voicexpress-settings', 'voicexpress_auto' );

		add_settings_section(
			'voicexpress_general_settings',
			'Configurações Gerais',
			array( $this, 'voicexpress_general_settings_callback' ),
			'voicexpress-settings'
		);

		add_settings_field(
			'voicexpress_position',
			'Posição',
			array( $this, 'voicexpress_position_callback' ),
			'voicexpress-settings',
			'voicexpress_general_settings'
		);

		add_settings_field(
			'voicexpress_auto',
			'Gerar áudio automaticamente',
			array( $this, 'voicexpress_auto_callback' ),
			'voicexpress-settings',
			'voicexpress_general_settings'
		);
	}

	/**
	 * Options page
	 *
	 * @return void
	 */
	public function voicexpress_options_page() {
		add_options_page(
			'Configurações do Voicexpress',
			'Voicexpress',
			'manage_options',
			'voicexpress-settings',
			array( $this, 'voicexpress_render_options_page' )
		);
	}

	/**
	 * Options page render
	 *
	 * @return void
	 */
	public function voicexpress_render_options_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Configurações do Voicexpress', 'voicexpress' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'voicexpress-settings' );
				do_settings_sections( 'voicexpress-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Helptext
	 *
	 * @return void
	 */
	public function voicexpress_general_settings_callback() {
		echo '<p>' . esc_html__( 'Configure as opções gerais do Voicexpress', 'voicexpress' ) . '</p>';
		echo '<p>Shortcode: [voicexpress post_id="123"]</p><br/>';
	}

	/**
	 * Save options
	 *
	 * @return void
	 */
	public function voicexpress_position_callback() {
		$position = get_option( 'voicexpress_position', 'before' );
		?>
		<label>
			<input type="radio" name="voicexpress_position" value="before" <?php checked( $position, 'before' ); ?> />
			<?php echo esc_html__( 'Antes', 'voicexpress' ); ?>
		</label>
		<label>
			<input type="radio" name="voicexpress_position" value="after" <?php checked( $position, 'after' ); ?> />
			<?php echo esc_html__( 'Depois', 'voicexpress' ); ?>
		</label>
		<?php
	}

	/**
	 * Autogenerate audios
	 *
	 * @return void
	 */
	public function voicexpress_auto_callback() {
		$auto = get_option( 'voicexpress_auto', 1 );
		?>
		<label>
			<input type="checkbox" name="voicexpress_auto" value="1" <?php checked( $auto, 1 ); ?> />
		</label>
		<?php
	}

	/**
	 * Install sitein API
	 *
	 * @return void
	 */
	public function voicexpress_install() {
		$req = wp_remote_request(
			$this->app_url . '/install/',
			array(
				'sslverify' => false,
				'timeout'   => 10,
				'method'    => 'POST',
				'body'      => wp_json_encode( array( 'url' => home_url() ) ),
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
			)
		);
		$res = wp_remote_retrieve_body( $req );
		update_option( 'voicexpress_secret_key', $res );
		$this->get_credits();
	}

	/**
	 * Send post data to API
	 *
	 * @param  int  $post_id ID do post.
	 * @param  bool $force Forçar atualização mesmo que o conteúdo não tenha sido alterado.
	 * @return void
	 */
	public function voicexpress_send_post( $post_id, $force = false ) {
		$post = get_post( $post_id );
		if ( 'publish' != $post->post_status && ! $force ) {
			return;
		}
		if ( get_post_meta( $post_id, '_voicexpress_gerar_audio', true ) != 1 ) {
			return;
		}
		$voicexpress_url      = $this->app_url . '/insert/';
		$content              = get_post_field( 'post_content', $post_id );
		$content_without_html = trim( strip_shortcodes( wp_strip_all_tags( html_entity_decode( $content ), true ) ) );

		if ( empty( $content_without_html ) ) {
			return;
		}
		$credits = get_option( 'voicexpress_credits' );
		if ( empty( $credits ) || 0 == $credits ) {
			return;
		}
		$secret_key = get_option( 'voicexpress_secret_key', '' );
		$data       = array(
			'notification_url' => admin_url( "admin-ajax.php?action=voicexpress_notification&post_id={$post_id}&key={$secret_key}" ),
			'url'              => get_permalink( $post_id ),
			'title'            => get_the_title( $post_id ),
			'content'          => $content_without_html,
			'image'            => get_the_post_thumbnail_url( $post_id ),
			'categories'       => array_map(
				function ( $cat ) {
					return $cat->name;
				},
				get_the_category( $post_id )
			),
		);

		$hash = get_post_meta( $post_id, 'voicexpress_hash', 1 );

		$content_hashed = preg_replace( "/[\s\r\n\t ]+/", '', html_entity_decode( $data['content'] ) );

		if ( md5( $content_hashed ) == $hash && ! $force ) {
			return;
		}

		update_post_meta( $post_id, 'voicexpress_hash', md5( $content_hashed ) );

		$req = wp_remote_request(
			$voicexpress_url,
			array(
				'sslverify' => false,
				'timeout'   => 10,
				'method'    => 'POST',
				'body'      => wp_json_encode( $data ),
				'headers'   => array(
					'Content-Type' => 'application/json',
					'X-Api-Key'    => $secret_key,
				),
			)
		);
		$res = wp_remote_retrieve_body( $req );
		update_post_meta( $post_id, '_voicexpress_audio', $res );
		update_post_meta( $post_id, 'voicexpress_sent', 1 );
		$this->get_credits();

		return $res;
	}

	/**
	 * Recebe notificações da API
	 *
	 * @return void
	 */
	public function voicexpress_notification() {
		if ( ! isset( $_GET['key'] ) || get_option( 'voicexpress_secret_key', '' ) != $_GET['key'] ) {
			wp_send_json_error();
		}

		$audio_key = isset( $_GET['audio_key'] ) ? sanitize_text_field( wp_unslash( $_GET['audio_key'] ) ) : '';
		$post_id   = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

		if ( ! $audio_key || ! $post_id ) {
			return;
		}

		update_post_meta( $post_id, '_voicexpress_audio', $audio_key );
		wp_send_json_success();
	}

	/**
	 * Get credits for site
	 *
	 * @return int $credits Créditos disponíveis
	 */
	public function get_credits() {
		$url     = $this->app_url . '/creditos/' . get_option( 'voicexpress_secret_key', '' );
		$req     = wp_remote_request(
			$url,
			array(
				'sslverify' => false,
				'timeout'   => 10,
				'method'    => 'GET',
				'headers'   => array(
					'Content-Type' => 'application/json',
					'X-Api-Key'    => get_option( 'voicexpress_secret_key', '' ),
				),
			)
		);
		$credits = wp_remote_retrieve_body( $req );
		update_option( 'voicexpress_credits', $credits );
		return (int) $credits;
	}
}
