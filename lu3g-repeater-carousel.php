<?php
/**
 * Plugin Name:       LU3G Repeater Carousel
 * Plugin URI:        https://lu3g.it
 * Description:       Widget Elementor che trasforma un repeater JetEngine in un carosello a scroll orizzontale con snap, freccia di navigazione e animazione d'ingresso.
 * Version:           1.16.0
 * Author:            LU3G Agenzia Web
 * Author URI:        https://lu3g.it
 * Text Domain:       lu3g-carousel
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package LU3G_Repeater_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LU3G_CAROUSEL_VERSION', '1.16.0' );
define( 'LU3G_CAROUSEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'LU3G_CAROUSEL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Aggiornamenti automatici da GitHub.
 *
 * Ogni sito controlla periodicamente l'ultima Release del repository e, se
 * la versione è più nuova di quella installata, mostra l'aggiornamento in
 * bacheca come per qualsiasi plugin. Lo zip scaricato è quello allegato
 * alla Release dalla GitHub Action, che ha la cartella con il nome giusto.
 *
 * Sta fuori dalla classe e prima dei controlli sulle dipendenze: il plugin
 * deve potersi aggiornare anche su un sito dove Elementor è disattivato.
 *
 * Repository privato: definire il token in wp-config.php, mai qui.
 *     define( 'LU3G_GITHUB_TOKEN', 'github_pat_...' );
 */
if ( file_exists( LU3G_CAROUSEL_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once LU3G_CAROUSEL_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

	$lu3g_carousel_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/violix96/lu3g-repeater-carousel/',
		__FILE__,
		'lu3g-repeater-carousel'
	);

	// Solo lo zip allegato alla Release, non quello generato da GitHub, che
	// ha una cartella con il numero di versione nel nome.
	$lu3g_carousel_updater->getVcsApi()->enableReleaseAssets( '/lu3g-repeater-carousel\.zip($|[?&#])/i' );

	if ( defined( 'LU3G_GITHUB_TOKEN' ) && LU3G_GITHUB_TOKEN ) {
		$lu3g_carousel_updater->setAuthentication( LU3G_GITHUB_TOKEN );
	}
}

/**
 * Bootstrap del plugin.
 *
 * Verifica le dipendenze e registra widget e asset. Se Elementor o JetEngine
 * non sono attivi mostra un avviso in bacheca e non fa altro.
 */
final class LU3G_Repeater_Carousel {

	/**
	 * Versione minima di Elementor supportata.
	 */
	const MIN_ELEMENTOR_VERSION = '3.5.0';

	/**
	 * Versione minima di PHP supportata.
	 */
	const MIN_PHP_VERSION = '7.4';

	/**
	 * Istanza singola.
	 *
	 * @var LU3G_Repeater_Carousel|null
	 */
	private static $instance = null;

	/**
	 * Restituisce l'istanza del plugin.
	 *
	 * @return LU3G_Repeater_Carousel
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Costruttore: aggancia l'init dopo il caricamento dei plugin.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init: controlla le dipendenze e registra gli hook.
	 *
	 * @return void
	 */
	public function init() {

		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_elementor' ) );
			return;
		}

		if ( ! version_compare( ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_elementor_version' ) );
			return;
		}

		if ( ! version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_php_version' ) );
			return;
		}

		if ( ! function_exists( 'jet_engine' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_jetengine' ) );
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// Gli asset vengono registrati, non accodati: il widget li dichiara
		// in get_style_depends() / get_script_depends() e Elementor li carica
		// solo nelle pagine dove il widget è effettivamente presente.
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Registra il widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager dei widget.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once LU3G_CAROUSEL_PATH . 'includes/class-widget-repeater-carousel.php';
		$widgets_manager->register( new \LU3G\Widgets\Repeater_Carousel() );
	}

	/**
	 * Registra una categoria dedicata nel pannello di Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Manager degli elementi.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'lu3g',
			array(
				'title' => __( 'LU3G', 'lu3g-carousel' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Registra il foglio di stile.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'lu3g-carousel',
			LU3G_CAROUSEL_URL . 'assets/carousel.css',
			array(),
			LU3G_CAROUSEL_VERSION
		);
	}

	/**
	 * Registra lo script.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'lu3g-carousel',
			LU3G_CAROUSEL_URL . 'assets/carousel.js',
			array(),
			LU3G_CAROUSEL_VERSION,
			true
		);
	}

	/**
	 * Avviso: Elementor non attivo.
	 *
	 * @return void
	 */
	public function notice_missing_elementor() {
		$this->render_notice(
			sprintf(
				/* translators: %s: nome del plugin richiesto */
				__( 'LU3G Repeater Carousel richiede %s per funzionare.', 'lu3g-carousel' ),
				'<strong>Elementor</strong>'
			)
		);
	}

	/**
	 * Avviso: versione di Elementor troppo vecchia.
	 *
	 * @return void
	 */
	public function notice_elementor_version() {
		$this->render_notice(
			sprintf(
				/* translators: %s: versione minima richiesta */
				__( 'LU3G Repeater Carousel richiede Elementor %s o superiore.', 'lu3g-carousel' ),
				'<strong>' . self::MIN_ELEMENTOR_VERSION . '</strong>'
			)
		);
	}

	/**
	 * Avviso: versione di PHP troppo vecchia.
	 *
	 * @return void
	 */
	public function notice_php_version() {
		$this->render_notice(
			sprintf(
				/* translators: %s: versione minima richiesta */
				__( 'LU3G Repeater Carousel richiede PHP %s o superiore.', 'lu3g-carousel' ),
				'<strong>' . self::MIN_PHP_VERSION . '</strong>'
			)
		);
	}

	/**
	 * Avviso: JetEngine non attivo.
	 *
	 * @return void
	 */
	public function notice_missing_jetengine() {
		$this->render_notice(
			sprintf(
				/* translators: %s: nome del plugin richiesto */
				__( 'LU3G Repeater Carousel richiede %s per leggere i campi repeater.', 'lu3g-carousel' ),
				'<strong>JetEngine</strong>'
			)
		);
	}

	/**
	 * Stampa un avviso in bacheca.
	 *
	 * @param string $message Messaggio già tradotto, può contenere tag base.
	 * @return void
	 */
	private function render_notice( $message ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			wp_kses( $message, array( 'strong' => array() ) )
		);
	}
}

LU3G_Repeater_Carousel::instance();
