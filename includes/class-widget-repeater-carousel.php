<?php
/**
 * Widget Elementor: carosello da repeater JetEngine.
 *
 * @package LU3G_Repeater_Carousel
 */

namespace LU3G\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carosello a scroll orizzontale alimentato da un campo repeater JetEngine
 * o da card scritte direttamente in Elementor.
 *
 * Gli helper interni hanno tutti il prefisso lu3g_: Widget_Base eredita da
 * Base_Object, che dichiara alcuni metodi come final — get_items() fra questi
 * — e un nome scelto senza prefisso può collidere ora o al prossimo
 * aggiornamento di Elementor, con un errore fatale.
 */
class Repeater_Carousel extends Widget_Base {

	/**
	 * Blocchi di testo aggiuntivi per card, oltre al primo.
	 *
	 * Per alzarlo basta cambiare questo numero: controlli, catena degli
	 * interruttori e render si adattano da soli.
	 */
	const LU3G_MAX_EXTRA_BLOCKS = 4;

	/**
	 * Nome interno del widget.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'lu3g-repeater-carousel';
	}

	/**
	 * Titolo mostrato nel pannello.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Carosello repeater', 'lu3g-carousel' );
	}

	/**
	 * Icona del widget.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Categorie in cui compare.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'lu3g' );
	}

	/**
	 * Parole chiave per la ricerca nel pannello.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'carosello', 'carousel', 'repeater', 'jetengine', 'card', 'slider', 'lu3g' );
	}

	/**
	 * Stili da caricare.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'lu3g-carousel' );
	}

	/**
	 * Script da caricare.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'lu3g-carousel' );
	}

	/**
	 * Elenca i campi repeater registrati in JetEngine.
	 *
	 * JetEngine non espone un'API stabile per questo, quindi la lettura è
	 * difensiva: se la struttura interna cambia si ricade sull'inserimento
	 * manuale del nome del campo.
	 *
	 * @return array Coppie nome_campo => etichetta.
	 */
	private function lu3g_get_repeater_fields() {
		$options = array( '' => __( '— Inserisci il nome manualmente —', 'lu3g-carousel' ) );

		if ( ! function_exists( 'jet_engine' ) ) {
			return $options;
		}

		$engine = jet_engine();

		if ( empty( $engine->meta_boxes ) || ! is_object( $engine->meta_boxes ) ) {
			return $options;
		}

		$boxes = array();

		if ( ! empty( $engine->meta_boxes->data ) && method_exists( $engine->meta_boxes->data, 'get_items' ) ) {
			$boxes = $engine->meta_boxes->data->get_items();
		} elseif ( method_exists( $engine->meta_boxes, 'get_raw' ) ) {
			$boxes = $engine->meta_boxes->get_raw();
		}

		if ( empty( $boxes ) || ! is_array( $boxes ) ) {
			return $options;
		}

		foreach ( $boxes as $box ) {
			$box_name = isset( $box['name'] ) ? $box['name'] : '';
			$fields   = isset( $box['meta_fields'] ) ? $box['meta_fields'] : array();

			if ( is_string( $fields ) ) {
				$fields = json_decode( $fields, true );
			}

			if ( empty( $fields ) || ! is_array( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				if ( empty( $field['type'] ) || 'repeater' !== $field['type'] || empty( $field['name'] ) ) {
					continue;
				}

				$label = ! empty( $field['title'] ) ? $field['title'] : $field['name'];

				if ( $box_name ) {
					$label .= ' (' . $box_name . ')';
				}

				$options[ $field['name'] ] = $label;
			}
		}

		return $options;
	}

	/**
	 * Registra i controlli del pannello.
	 *
	 * L'ordine segue il modo in cui si costruisce un carosello: prima il
	 * contenuto, poi come si dispone, come si muove, come si naviga; nella
	 * tab Stile, gli elementi della card dall'alto verso il basso.
	 *
	 * Gli ID dei controlli non vanno mai rinominati: sono le chiavi con cui
	 * Elementor salva i valori nelle pagine. Spostarli tra sezioni è sicuro.
	 *
	 * @return void
	 */
	protected function register_controls() {
		// Tab Contenuto.
		$this->register_content_controls();
		$this->register_content_options_controls();
		$this->register_layout_controls();
		$this->register_scroll_controls();
		$this->register_nav_controls();
		$this->register_indicator_controls();

		// Tab Stile.
		$this->register_card_style_controls();
		$this->register_image_style_controls();
		$this->register_icon_style_controls();

		$this->lu3g_register_block_style(
			'kicker',
			__( 'Etichetta', 'lu3g-carousel' ),
			'.lu3g-carousel__kicker',
			'--lu3g-kicker-gap',
			__( 'Distanza dal titolo', 'lu3g-carousel' ),
			12
		);

		$this->register_text_style_controls();
		$this->register_description_style_controls();

		$this->lu3g_register_block_style(
			'extra_1',
			__( 'Stile testo 2', 'lu3g-carousel' ),
			'.lu3g-carousel__block--style-2',
			'--lu3g-extra-1-gap',
			__( 'Distanza dal blocco sopra', 'lu3g-carousel' ),
			12
		);

		$this->lu3g_register_block_style(
			'extra_2',
			__( 'Stile testo 3', 'lu3g-carousel' ),
			'.lu3g-carousel__block--style-3',
			'--lu3g-extra-2-gap',
			__( 'Distanza dal blocco sopra', 'lu3g-carousel' ),
			12
		);

		$this->register_toggle_style_controls();
		$this->register_button_style_controls();
		$this->register_nav_style_controls();
		$this->register_indicator_style_controls();
		$this->register_animation_controls();
	}

	/**
	 * Tab Contenuto — Card: da dove arrivano e cosa contengono.
	 *
	 * @return void
	 */
	private function register_content_controls() {

		$this->start_controls_section(
			'section_source',
			array(
				'label' => __( 'Card', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);


		$this->add_control(
			'source_type',
			array(
				'label'   => __( 'Tipo di contenuto', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dynamic',
				'options' => array(
					'dynamic' => __( 'Dinamico — repeater JetEngine', 'lu3g-carousel' ),
					'manual'  => __( 'Statico — inserito in Elementor', 'lu3g-carousel' ),
					'gallery' => __( 'Immagini', 'lu3g-carousel' ),
				),
				'description' => __( 'Il contenuto statico non richiede JetEngine e resta salvato nella pagina, non nel post.', 'lu3g-carousel' ),
			)
		);


		$manual = new Repeater();

		// Due tab per riga: il contenuto e i colori propri della card. Le tab
		// dentro il repeater tengono il pannello leggibile anche con molti
		// campi, perché mostrano solo metà dei controlli alla volta.
		$manual->start_controls_tabs( 'card_item_tabs' );

		$manual->start_controls_tab(
			'card_item_tab_content',
			array( 'label' => __( 'Contenuto', 'lu3g-carousel' ) )
		);

		$manual->add_control(
			'card_kicker',
			array(
				'label'       => __( 'Etichetta (facoltativa)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'La mia formazione', 'lu3g-carousel' ),
				'description' => __( 'Testo breve sopra il titolo.', 'lu3g-carousel' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		// La chiave resta card_text anche se ora è il titolo: rinominarla
		// svuoterebbe le card già inserite nelle pagine esistenti.
		$manual->add_control(
			'card_text',
			array(
				'label'       => __( 'Titolo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'Titolo della card', 'lu3g-carousel' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		// Menu condiviso da tutti i blocchi: ogni blocco sceglie uno dei tre
		// stili di testo definiti nello Stile del widget. Il numero di
		// sezioni nel pannello resta fisso anche con molti blocchi.
		$style_options = array(
			'1' => __( 'Stile testo 1', 'lu3g-carousel' ),
			'2' => __( 'Stile testo 2', 'lu3g-carousel' ),
			'3' => __( 'Stile testo 3', 'lu3g-carousel' ),
		);

		// Editor visuale: più paragrafi, grassetto, elenchi. Il tipo è
		// cambiato da textarea, ma il valore salvato resta una stringa e le
		// descrizioni già inserite vengono lette senza perdite.
		$manual->add_control(
			'card_description',
			array(
				'label'   => __( 'Testo', 'lu3g-carousel' ),
				'type'    => Controls_Manager::WYSIWYG,
				'dynamic' => array( 'active' => true ),
			)
		);

		$manual->add_control(
			'card_description_style',
			array(
				'label'   => __( 'Stile di questo testo', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => $style_options,
			)
		);

		/*
		 * Blocchi aggiuntivi a catena: ogni blocco compare solo se è attivo
		 * l'interruttore "Aggiungi" del blocco precedente, e porta con sé
		 * l'interruttore per il successivo. Spegnere un interruttore nasconde
		 * quel blocco e tutti quelli dopo, e li toglie dalla pagina.
		 */
		$chain = array();

		for ( $n = 1; $n <= self::LU3G_MAX_EXTRA_BLOCKS; $n++ ) {

			$add_args = array(
				'label'        => __( 'Aggiungi un altro blocco di testo', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
			);

			// Il primo interruttore è sempre visibile: niente condizione vuota.
			if ( $chain ) {
				$add_args['condition'] = $chain;
			}

			$manual->add_control( 'card_add_' . $n, $add_args );

			$chain[ 'card_add_' . $n ] = 'yes';

			$manual->add_control(
				'card_extra_' . $n,
				array(
					/* translators: %d: numero del blocco di testo */
					'label'     => sprintf( __( 'Testo %d', 'lu3g-carousel' ), $n + 1 ),
					'type'      => Controls_Manager::WYSIWYG,
					'dynamic'   => array( 'active' => true ),
					'condition' => $chain,
				)
			);

			// I primi due blocchi partono dagli stili 2 e 3, come nella
			// versione precedente, dove avevano ognuno uno stile proprio.
			$manual->add_control(
				'card_extra_' . $n . '_style',
				array(
					'label'     => __( 'Stile di questo testo', 'lu3g-carousel' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => ( 1 === $n ) ? '2' : ( ( 2 === $n ) ? '3' : '1' ),
					'options'   => $style_options,
					'condition' => $chain,
				)
			);
		}

		$manual->add_control(
			'card_icon',
			array(
				'label'     => __( 'Icona', 'lu3g-carousel' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(),
				'separator' => 'before',
			)
		);

		$manual->add_control(
			'card_button_text',
			array(
				'label'       => __( 'Testo del pulsante (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Scopri', 'lu3g-carousel' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$manual->add_control(
			'card_link',
			array(
				'label'       => __( 'Link (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'description' => __( 'Con il testo del pulsante compilato il link va sul pulsante; senza, rende cliccabile l\'intera card.', 'lu3g-carousel' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$manual->end_controls_tab();

		$manual->start_controls_tab(
			'card_item_tab_style',
			array( 'label' => __( 'Stile', 'lu3g-carousel' ) )
		);

		$manual->add_control(
			'item_custom_colors',
			array(
				'label'        => __( 'Colori propri', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Questa card usa i colori qui sotto invece di quelli generali. I campi lasciati vuoti restano quelli generali.', 'lu3g-carousel' ),
			)
		);

		/*
		 * {{CURRENT_ITEM}} diventa la classe unica di questa riga, che il
		 * render stampa sulla card. I selettori hanno un livello in più di
		 * quelli generali — anche di quelli in hover — così il colore della
		 * singola card vince sempre. I cloni del loop copiano la classe, e
		 * quindi anche i colori.
		 */
		$card = '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__track .lu3g-carousel__card{{CURRENT_ITEM}}';

		$manual->add_control(
			'item_bg',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'item_custom_colors' => 'yes' ),
				'selectors' => array(
					$card => 'background-color: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_bg_hover',
			array(
				'label'     => __( 'Sfondo in hover', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'item_custom_colors' => 'yes' ),
				'selectors' => array(
					$card . ':hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_border',
			array(
				'label'     => __( 'Bordo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'item_custom_colors' => 'yes' ),
				'selectors' => array(
					$card           => 'border-color: {{VALUE}};',
					$card . ':hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_text',
			array(
				'label'       => __( 'Testi e icona', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Etichetta, titolo, tutti i blocchi di testo, "Mostra di più" e icona.', 'lu3g-carousel' ),
				'condition'   => array( 'item_custom_colors' => 'yes' ),
				'selectors'   => array(
					$card . ' .lu3g-carousel__kicker, ' .
					$card . ' .lu3g-carousel__text, ' .
					$card . ' .lu3g-carousel__block, ' .
					$card . ' .lu3g-carousel__toggle, ' .
					$card . ' .lu3g-carousel__icon' => 'color: {{VALUE}};',
					$card . ' .lu3g-carousel__icon svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_button_color',
			array(
				'label'     => __( 'Testo del pulsante', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'item_custom_colors' => 'yes' ),
				'selectors' => array(
					$card . ' .lu3g-carousel__button' => 'color: {{VALUE}};',
					$card . ' .lu3g-carousel__button svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_button_bg',
			array(
				'label'     => __( 'Sfondo del pulsante', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'item_custom_colors' => 'yes' ),
				'selectors' => array(
					$card . ' .lu3g-carousel__button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_bg_enable',
			array(
				'label'        => __( 'Immagine di sfondo', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		/*
		 * Tutti i selettori dello sfondo richiedono la classe --has-bg, che il
		 * render stampa solo con l'interruttore acceso: spegnendolo l'immagine
		 * sparisce anche se resta salvata. Dimensione, posizione e ripetizione
		 * arrivano da qui e non dal CSS di base, perché lo sfondo generale
		 * delle card usa la forma abbreviata "background:", che le azzera.
		 */
		$bg    = $card . '.lu3g-carousel__card--has-bg';
		$bg_on = array( 'item_bg_enable' => 'yes' );

		$manual->add_control(
			'item_bg_image',
			array(
				'label'     => __( 'Immagine', 'lu3g-carousel' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => array( 'active' => true ),
				'condition' => $bg_on,
				'selectors' => array(
					$bg => 'background-image: url("{{URL}}");',
				),
			)
		);

		$manual->add_responsive_control(
			'item_bg_size',
			array(
				'label'                => __( 'Dimensioni', 'lu3g-carousel' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'cover',
				'options'              => array(
					'cover'   => __( 'Cover — riempie, ritagliando', 'lu3g-carousel' ),
					'contain' => __( 'Contenitore — intera', 'lu3g-carousel' ),
					'auto'    => __( 'Automatica — dimensione originale', 'lu3g-carousel' ),
					'custom'  => __( 'Personalizzato', 'lu3g-carousel' ),
				),
				'selectors_dictionary' => array(
					'cover'   => 'background-size: cover;',
					'contain' => 'background-size: contain;',
					'auto'    => 'background-size: auto;',
					'custom'  => 'background-size: var(--lu3g-bg-w, 100%) auto;',
				),
				'condition'            => $bg_on,
				'selectors'            => array(
					$bg => '{{VALUE}}',
				),
			)
		);

		$manual->add_responsive_control(
			'item_bg_width',
			array(
				'label'      => __( 'Larghezza', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range'      => array(
					'%'  => array( 'min' => 10, 'max' => 300 ),
					'px' => array( 'min' => 20, 'max' => 1500 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 100 ),
				'condition'  => array(
					'item_bg_enable' => 'yes',
					'item_bg_size'   => 'custom',
				),
				'selectors'  => array(
					$bg => '--lu3g-bg-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$manual->add_responsive_control(
			'item_bg_position',
			array(
				'label'                => __( 'Posizione', 'lu3g-carousel' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'center center',
				'options'              => array(
					'center center' => __( 'Centrato', 'lu3g-carousel' ),
					'center left'   => __( 'Centrato a sinistra', 'lu3g-carousel' ),
					'center right'  => __( 'Centrato a destra', 'lu3g-carousel' ),
					'top center'    => __( 'Sopra al centro', 'lu3g-carousel' ),
					'top left'      => __( 'Sopra a sinistra', 'lu3g-carousel' ),
					'top right'     => __( 'Sopra a destra', 'lu3g-carousel' ),
					'bottom center' => __( 'Sotto al centro', 'lu3g-carousel' ),
					'bottom left'   => __( 'In basso a sinistra', 'lu3g-carousel' ),
					'bottom right'  => __( 'In basso a destra', 'lu3g-carousel' ),
					'custom'        => __( 'Personalizzato', 'lu3g-carousel' ),
				),
				'selectors_dictionary' => array(
					'center center' => 'background-position: center center;',
					'center left'   => 'background-position: center left;',
					'center right'  => 'background-position: center right;',
					'top center'    => 'background-position: top center;',
					'top left'      => 'background-position: top left;',
					'top right'     => 'background-position: top right;',
					'bottom center' => 'background-position: bottom center;',
					'bottom left'   => 'background-position: bottom left;',
					'bottom right'  => 'background-position: bottom right;',
					'custom'        => 'background-position: var(--lu3g-bg-x, 50%) var(--lu3g-bg-y, 50%);',
				),
				'condition'            => $bg_on,
				'selectors'            => array(
					$bg => '{{VALUE}}',
				),
			)
		);

		$manual->add_responsive_control(
			'item_bg_x',
			array(
				'label'      => __( 'Posizione orizzontale', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range'      => array(
					'%'  => array( 'min' => -50, 'max' => 150 ),
					'px' => array( 'min' => -500, 'max' => 500 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
				'condition'  => array(
					'item_bg_enable'   => 'yes',
					'item_bg_position' => 'custom',
				),
				'selectors'  => array(
					$bg => '--lu3g-bg-x: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$manual->add_responsive_control(
			'item_bg_y',
			array(
				'label'      => __( 'Posizione verticale', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range'      => array(
					'%'  => array( 'min' => -50, 'max' => 150 ),
					'px' => array( 'min' => -500, 'max' => 500 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
				'condition'  => array(
					'item_bg_enable'   => 'yes',
					'item_bg_position' => 'custom',
				),
				'selectors'  => array(
					$bg => '--lu3g-bg-y: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$manual->add_control(
			'item_bg_repeat',
			array(
				'label'     => __( 'Ripetizione', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'no-repeat',
				'options'   => array(
					'no-repeat' => __( 'Non ripetere', 'lu3g-carousel' ),
					'repeat'    => __( 'Ripeti', 'lu3g-carousel' ),
					'repeat-x'  => __( 'Ripeti orizzontalmente', 'lu3g-carousel' ),
					'repeat-y'  => __( 'Ripeti verticalmente', 'lu3g-carousel' ),
				),
				'condition' => $bg_on,
				'selectors' => array(
					$bg => 'background-repeat: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_overlay_color',
			array(
				'label'       => __( 'Colore della velatura', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#000000',
				'description' => __( 'Scurisce o colora la foto per rendere leggibile il testo. Sta sopra l\'immagine e sotto i testi.', 'lu3g-carousel' ),
				'separator'   => 'before',
				'condition'   => $bg_on,
				'selectors'   => array(
					$bg => '--lu3g-overlay-color: {{VALUE}};',
				),
			)
		);

		$manual->add_control(
			'item_overlay_opacity',
			array(
				'label'      => __( 'Intensità della velatura', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array( '' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'default'    => array( 'size' => 0.4 ),
				'condition'  => $bg_on,
				'selectors'  => array(
					$bg => '--lu3g-overlay-opacity: {{SIZE}};',
				),
			)
		);

		$manual->add_control(
			'item_overlay_opacity_hover',
			array(
				'label'       => __( 'Intensità in hover', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array( '' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'description' => __( 'Vuoto per lasciarla uguale. Più bassa per schiarire la foto al passaggio del mouse, più alta per scurirla.', 'lu3g-carousel' ),
				'condition'   => $bg_on,
				'selectors'   => array(
					$bg . ':hover' => '--lu3g-overlay-opacity: {{SIZE}};',
				),
			)
		);

		$manual->end_controls_tab();

		$manual->end_controls_tabs();

		$this->add_control(
			'manual_items',
			array(
				'label'       => __( 'Card', 'lu3g-carousel' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $manual->get_controls(),
				'title_field' => '{{{ card_text }}}',
				'condition'   => array( 'source_type' => 'manual' ),
				'default'     => array(
					array( 'card_text' => __( 'Prima card', 'lu3g-carousel' ) ),
					array( 'card_text' => __( 'Seconda card', 'lu3g-carousel' ) ),
					array( 'card_text' => __( 'Terza card', 'lu3g-carousel' ) ),
				),
			)
		);

		$this->add_control(
			'gallery_images',
			array(
				'label'       => __( 'Immagini', 'lu3g-carousel' ),
				'type'        => Controls_Manager::GALLERY,
				'default'     => array(),
				'show_label'  => false,
				'dynamic'     => array( 'active' => true ),
				'description' => __( 'Una card per immagine, nell\'ordine della galleria. Accetta anche una galleria dinamica, per esempio un campo galleria di JetEngine.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type' => 'gallery' ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'gallery_image',
				'default'   => 'large',
				'condition' => array( 'source_type' => 'gallery' ),
			)
		);

		$this->add_control(
			'gallery_link',
			array(
				'label'     => __( 'Al click', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => array(
					'none'     => __( 'Nessuna azione', 'lu3g-carousel' ),
					'lightbox' => __( 'Apri nel lightbox', 'lu3g-carousel' ),
					'file'     => __( 'Apri il file dell\'immagine', 'lu3g-carousel' ),
				),
				'condition' => array( 'source_type' => 'gallery' ),
			)
		);

		$this->add_control(
			'repeater_select',
			array(
				'label'       => __( 'Campo repeater', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->lu3g_get_repeater_fields(),
				'default'     => '',
				'condition'   => array( 'source_type' => 'dynamic' ),
				'description' => __( 'Elenco dei repeater registrati in JetEngine. Se il campo non compare, scegli l\'inserimento manuale.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'repeater_name',
			array(
				'label'       => __( 'Nome del campo (manuale)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'campi_applicazione',
				'condition'   => array(
					'source_type'     => 'dynamic',
					'repeater_select' => '',
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'sub_field',
			array(
				'label'       => __( 'Sottocampo da mostrare', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'testo',
				'placeholder' => 'testo',
				'condition'   => array( 'source_type' => 'dynamic' ),
				'description' => __( 'Il "name" del sottocampo del titolo, come appare nel meta box di JetEngine (es. titolo_card_ruolo). "testo" è solo un esempio.', 'lu3g-carousel' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'kicker_field',
			array(
				'label'       => __( 'Sottocampo etichetta (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Testo breve sopra il titolo.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type' => 'dynamic' ),
				'label_block' => true,
			)
		);

		// Qui un repeater è possibile: è a livello di widget, non dentro un
		// altro repeater. Ogni riga collega un sottocampo JetEngine a uno dei
		// tre stili di testo, con la stessa logica delle card statiche.
		$blocks = new Repeater();

		$blocks->add_control(
			'block_field',
			array(
				'label'       => __( 'Sottocampo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'descrizione',
				'label_block' => true,
			)
		);

		$blocks->add_control(
			'block_style',
			array(
				'label'   => __( 'Stile di questo testo', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => array(
					'1' => __( 'Stile testo 1', 'lu3g-carousel' ),
					'2' => __( 'Stile testo 2', 'lu3g-carousel' ),
					'3' => __( 'Stile testo 3', 'lu3g-carousel' ),
				),
			)
		);

		$this->add_control(
			'dynamic_blocks',
			array(
				'label'         => __( 'Blocchi di testo', 'lu3g-carousel' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $blocks->get_controls(),
				'title_field'   => '{{{ block_field }}}',
				'prevent_empty' => false,
				'default'       => array(),
				'description'   => __( 'Un blocco per ogni sottocampo da mostrare sotto il titolo, nell\'ordine. Il troncamento, se attivo sui testi, agisce sul primo.', 'lu3g-carousel' ),
				'condition'     => array( 'source_type' => 'dynamic' ),
			)
		);

		$this->add_control(
			'dynamic_button_text',
			array(
				'label'       => __( 'Testo del pulsante (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Scopri', 'lu3g-carousel' ),
				'description' => __( 'Uguale per tutte le card, oppure %nome_sottocampo% per leggerlo dal repeater. Con il pulsante, il link va sul pulsante invece che sull\'intera card.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type' => 'dynamic' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'icon_field',
			array(
				'label'       => __( 'Sottocampo icona (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'condition'   => array( 'source_type' => 'dynamic' ),
				'description' => __( 'Nome di un sottocampo media. Gli SVG caricati nella libreria vengono inseriti nel markup, così ereditano il colore impostato qui sotto; gli altri formati restano immagini.', 'lu3g-carousel' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'dynamic_link_source',
			array(
				'label'       => __( 'Link della card', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'field',
				'options'     => array(
					'field' => __( 'Da un sottocampo link', 'lu3g-carousel' ),
					'text'  => __( 'Dal primo link nel testo', 'lu3g-carousel' ),
				),
				'description' => __( '"Dal primo link nel testo" cerca un link nel titolo e nei blocchi di testo di ogni riga e lo sposta sul pulsante: il pulsante compare solo sulle card che hanno un link.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type' => 'dynamic' ),
			)
		);

		$this->add_control(
			'link_field',
			array(
				'label'       => __( 'Sottocampo link (facoltativo)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Se valorizzato, il link va sul pulsante; senza pulsante rende cliccabile l\'intera card.', 'lu3g-carousel' ),
				'condition'   => array(
					'source_type'          => 'dynamic',
					'dynamic_link_source!' => 'text',
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'link_target',
			array(
				'label'     => __( 'Apri in una nuova scheda', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array( 'link_field!' => '' ),
			)
		);

		$this->add_control(
			'item_format',
			array(
				'label'       => __( 'Formato avanzato', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'placeholder' => '<strong>%titolo%</strong><br>%descrizione%',
				'condition'   => array( 'source_type' => 'dynamic' ),
				'description' => __( 'Facoltativo. Usa %nome_sottocampo% per stampare più valori. Se compilato sostituisce il sottocampo qui sopra.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'source_post_id',
			array(
				'label'       => __( 'ID del post', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Post corrente', 'lu3g-carousel' ),
				'description' => __( 'Lascia vuoto per leggere il post in cui il template viene renderizzato.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type' => 'dynamic' ),
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Contenuto — Opzioni del contenuto: semantica, pulizia e troncamento.
	 *
	 * @return void
	 */
	private function register_content_options_controls() {

		$this->start_controls_section(
			'section_content_options',
			array(
				'label' => __( 'Opzioni del contenuto', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);


		$this->add_control(
			'title_tag',
			array(
				'label'       => __( 'Tag del titolo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'span',
				'options'     => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'p'    => 'p',
					'span' => 'span',
					'div'  => 'div',
				),
				'description' => __( 'Solo semantica: l\'aspetto lo decide la tipografia del titolo. Per le card con descrizione conviene un H3.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'     => __( 'Icona del pulsante', 'lu3g-carousel' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'strip_tags',
			array(
				'label'        => __( 'Solo testo', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( 'Rimuove i tag HTML dal titolo e dai campi del repeater JetEngine. Descrizione e testi aggiuntivi delle card manuali mantengono sempre la formattazione dell\'editor.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'truncate',
			array(
				'label'        => __( 'Tronca il testo', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
				'description'  => __( 'Limita il testo a un numero di righe e aggiunge un pulsante per espanderlo. Non disponibile se l\'intera card è cliccabile; con il link sul pulsante invece funziona.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'truncate_target',
			array(
				'label'       => __( 'Cosa troncare', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'title',
				'options'     => array(
					'title'       => __( 'Il titolo', 'lu3g-carousel' ),
					'description' => __( 'Il primo blocco di testo', 'lu3g-carousel' ),
				),
				'description' => __( 'Si tronca il primo blocco di testo. Sulle card che non ce l\'hanno viene troncato il titolo.', 'lu3g-carousel' ),
				'condition'   => array( 'truncate' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'truncate_lines',
			array(
				'label'      => __( 'Righe visibili', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'default'        => array( 'size' => 3 ),
				'mobile_default' => array( 'size' => 2 ),
				'condition'      => array( 'truncate' => 'yes' ),
				'selectors'      => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-lines: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'expand_label',
			array(
				'label'       => __( 'Etichetta per espandere', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Mostra di più', 'lu3g-carousel' ),
				'condition'   => array( 'truncate' => 'yes' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'collapse_label',
			array(
				'label'       => __( 'Etichetta per richiudere', 'lu3g-carousel' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Mostra meno', 'lu3g-carousel' ),
				'condition'   => array( 'truncate' => 'yes' ),
				'label_block' => true,
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Contenuto — Layout: dimensioni delle card e disposizione interna.
	 *
	 * @return void
	 */
	private function register_layout_controls() {

		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);


		$this->add_control(
			'card_width_mode',
			array(
				'label'   => __( 'Larghezza delle card', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'  => __( 'Calcolata dalle card visibili', 'lu3g-carousel' ),
					'fixed' => __( 'Fissa', 'lu3g-carousel' ),
				),
				'description' => __( 'Con la larghezza fissa il numero di card a schermo dipende dallo spazio disponibile.', 'lu3g-carousel' ),
			)
		);

		$this->add_responsive_control(
			'card_width',
			array(
				'label'      => __( 'Larghezza', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 100,
						'max' => 800,
					),
					'vw' => array(
						'min' => 10,
						'max' => 90,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 320,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 240,
				),
				'condition'  => array( 'card_width_mode' => 'fixed' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-card-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'visible_cards',
			array(
				'label'      => __( 'Card visibili', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 1,
						'max'  => 8,
						'step' => 1,
					),
				),
				'default'        => array( 'size' => 4 ),
				'tablet_default' => array( 'size' => 2 ),
				'mobile_default' => array( 'size' => 1 ),
				'condition'      => array( 'card_width_mode' => 'auto' ),
				'selectors'      => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-visible: {{SIZE}};',
				),
			)
		);

		$this->add_responsive_control(
			'peek',
			array(
				'label'      => __( 'Anteprima card successiva', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
					'%'  => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 60,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 40,
				),
				'condition'   => array( 'card_width_mode' => 'auto' ),
				'description' => __( 'Spazio riservato sul bordo destro: fa sporgere la card successiva, come indizio che si può scorrere. Zero per card esatte senza taglio.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-peek: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Spazio tra le card', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 24,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// Con le foto la proporzione è più naturale dell'altezza: la card
		// mantiene la sua forma a qualunque larghezza. Quando è impostata,
		// l'altezza delle card viene ignorata.
		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => __( 'Proporzione', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '3 / 4',
				'options'   => array(
					'none'   => __( 'Usa l\'altezza delle card', 'lu3g-carousel' ),
					'1 / 1'  => '1:1 — quadrata',
					'4 / 5'  => '4:5 — verticale',
					'3 / 4'  => '3:4 — verticale',
					'2 / 3'  => '2:3 — verticale alta',
					'9 / 16' => '9:16 — storia',
					'4 / 3'  => '4:3 — orizzontale',
					'3 / 2'  => '3:2 — orizzontale',
					'16 / 9' => '16:9 — panoramica',
				),
				'condition' => array( 'source_type' => 'gallery' ),
				// Ogni opzione diventa una dichiarazione completa: con una
				// proporzione l'altezza della card viene annullata (il selettore
				// col wrapper batte le regole dell'altezza); con "Usa l'altezza"
				// non si scrive nulla e restano valide quelle.
				'selectors_dictionary' => array(
					'none'   => 'aspect-ratio: auto;',
					'1 / 1'  => 'aspect-ratio: 1 / 1; height: auto; min-height: 0;',
					'4 / 5'  => 'aspect-ratio: 4 / 5; height: auto; min-height: 0;',
					'3 / 4'  => 'aspect-ratio: 3 / 4; height: auto; min-height: 0;',
					'2 / 3'  => 'aspect-ratio: 2 / 3; height: auto; min-height: 0;',
					'9 / 16' => 'aspect-ratio: 9 / 16; height: auto; min-height: 0;',
					'4 / 3'  => 'aspect-ratio: 4 / 3; height: auto; min-height: 0;',
					'3 / 2'  => 'aspect-ratio: 3 / 2; height: auto; min-height: 0;',
					'16 / 9' => 'aspect-ratio: 16 / 9; height: auto; min-height: 0;',
				),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card--image' => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'card_height',
			array(
				'label'      => __( 'Altezza delle card', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 120,
						'max' => 600,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 340,
				),
				'tablet_default' => array(
					'unit' => 'px',
					'size' => 280,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 240,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-card-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'height_mode',
			array(
				'label'        => __( 'Altezza fissa', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => __( 'Sì', 'lu3g-carousel' ),
				'label_off'    => __( 'No', 'lu3g-carousel' ),
				'description'  => __( 'Attivo: tutte le card hanno esattamente l\'altezza indicata, il testo lungo può uscire. Disattivo: l\'altezza indicata è il minimo e le card crescono insieme, restando comunque tutte uguali.', 'lu3g-carousel' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'center_mode',
			array(
				'label'        => __( 'Modalità centrata', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
				'description'  => __( 'La card di mezzo del gruppo visibile sta al centro, in rilievo; quelle ai lati sono più piccole e sbiadite. Usa un numero dispari di card visibili: con 3 vedi piccola, grande, piccola. L\'anteprima si divide tra i due lati.', 'lu3g-carousel' ),
			)
		);

		$this->add_responsive_control(
			'center_side_scale',
			array(
				'label'      => __( 'Dimensione delle card laterali', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array( '' => array( 'min' => 0.6, 'max' => 1, 'step' => 0.01 ) ),
				'default'    => array( 'size' => 0.88 ),
				'condition'  => array( 'center_mode' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-center-scale: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'center_side_opacity',
			array(
				'label'      => __( 'Opacità delle card laterali', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array( '' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'default'    => array( 'size' => 0.5 ),
				'condition'  => array( 'center_mode' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-center-opacity: {{SIZE}};',
				),
			)
		);

		$this->add_responsive_control(
			'center_main_scale',
			array(
				'label'       => __( 'Dimensione della card centrale', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array( '' => array( 'min' => 1, 'max' => 1.2, 'step' => 0.01 ) ),
				'default'     => array( 'size' => 1.04 ),
				'description' => __( 'Sopra 1 la card centrale sporge leggermente sulle vicine.', 'lu3g-carousel' ),
				'condition'   => array( 'center_mode' => 'yes' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-center-main-scale: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'           => 'center_shadow',
				'label'          => __( 'Ombra della card centrale', 'lu3g-carousel' ),
				'selector'       => '{{WRAPPER}} .lu3g-carousel--center .lu3g-carousel__track .lu3g-carousel__card.is-center',
				'condition'      => array( 'center_mode' => 'yes' ),
				'fields_options' => array(
					'box_shadow_type' => array( 'default' => 'yes' ),
					'box_shadow'      => array(
						'default' => array(
							'horizontal' => 0,
							'vertical'   => 18,
							'blur'       => 40,
							'spread'     => -10,
							'color'      => 'rgba(0,0,0,0.28)',
						),
					),
				),
			)
		);

		$this->add_control(
			'content_layout',
			array(
				'label'       => __( 'Distribuzione del contenuto', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'grouped',
				'options'     => array(
					'grouped' => __( 'Raggruppato', 'lu3g-carousel' ),
					'spread'  => __( 'Distribuito: contenuto in alto, pulsante in fondo', 'lu3g-carousel' ),
				),
				'description' => __( 'Distribuito allinea tra le card icone, titoli e pulsanti, indipendentemente dalla lunghezza dei testi.', 'lu3g-carousel' ),
				'condition'   => array( 'source_type!' => 'gallery' ),
			)
		);

		$this->add_responsive_control(
			'text_position',
			array(
				'label'     => __( 'Posizione del testo', 'lu3g-carousel' ),
				'condition' => array( 'source_type!' => 'gallery', 'content_layout' => 'grouped' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Alto', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-top',
					),
					'center'     => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-middle',
					),
					'flex-end'   => array(
						'title' => __( 'Basso', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'default'        => 'flex-end',
				'mobile_default' => 'center',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card' => 'align-items: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Contenuto — Scorrimento: velocità, aggancio, loop e autoplay.
	 *
	 * @return void
	 */
	private function register_scroll_controls() {

		$this->start_controls_section(
			'section_scroll',
			array(
				'label' => __( 'Scorrimento', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);


		$this->add_control(
			'scroll_speed',
			array(
				'label'       => __( 'Velocità di scorrimento (ms)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array(
					'' => array(
						'min'  => 100,
						'max'  => 2000,
						'step' => 50,
					),
				),
				'default'     => array( 'size' => 600 ),
				'description' => __( 'Durata dell\'animazione a ogni scorrimento, sia con le frecce sia in autoplay.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'scroll_snap',
			array(
				'label'        => __( 'Aggancio allo scroll', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( 'Le card si allineano al bordo quando lo scorrimento si ferma.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Scorrimento infinito', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Dopo l\'ultima card riparte dalla prima senza tornare indietro. Servono almeno due card.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Si disattiva da solo per chi ha chiesto meno animazioni nelle impostazioni di sistema.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'      => __( 'Tempo tra uno scorrimento e l\'altro (s)', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 1,
						'max'  => 15,
						'step' => 0.5,
					),
				),
				'default'    => array( 'size' => 4 ),
				'condition'  => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Metti in pausa al passaggio del mouse', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Contenuto — Frecce: se ci sono, quante, dove.
	 *
	 * @return void
	 */
	private function register_nav_controls() {

		$this->start_controls_section(
			'section_nav',
			array(
				'label' => __( 'Frecce', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);


		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Mostra le frecce', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'arrows_mode',
			array(
				'label'     => __( 'Numero di frecce', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'single',
				'options'   => array(
					'single' => __( 'Una', 'lu3g-carousel' ),
					'double' => __( 'Due', 'lu3g-carousel' ),
				),
				'description' => __( 'Con una sola freccia, questa cambia verso quando il carosello arriva in fondo.', 'lu3g-carousel' ),
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'arrows_position',
			array(
				'label'     => __( 'Posizione', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'start',
				'options'   => array(
					'start'   => __( 'A sinistra del carosello', 'lu3g-carousel' ),
					'end'     => __( 'A destra del carosello', 'lu3g-carousel' ),
					'split'   => __( 'Ai due lati', 'lu3g-carousel' ),
					'above'   => __( 'Sopra il carosello', 'lu3g-carousel' ),
					'below'   => __( 'Sotto il carosello', 'lu3g-carousel' ),
					'overlay' => __( 'Sovrapposte alle card', 'lu3g-carousel' ),
				),
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);
		// Allineamento orizzontale, solo sopra o sotto. Il selettore è
		// limitato a quelle due posizioni: se si cambia posizione, il valore
		// rimasto salvato non ha effetto sulle altre.
		$this->add_responsive_control(
			'arrows_align',
			array(
				'label'     => __( 'Allineamento', 'lu3g-carousel' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array(
						'title' => __( 'Sinistra', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'        => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'      => array(
						'title' => __( 'Destra', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-right',
					),
					'space-between' => array(
						'title' => __( 'Ai due estremi', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-stretch',
					),
				),
				'default'   => 'flex-start',
				'condition' => array(
					'show_arrows'     => 'yes',
					'arrows_position' => array( 'above', 'below' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel--arrows-above .lu3g-carousel__nav, {{WRAPPER}} .lu3g-carousel--arrows-below .lu3g-carousel__nav' => 'justify-content: {{VALUE}};',
				),
			)
		);

		// Allineamento verticale, per le posizioni laterali e sovrapposte.
		$this->add_responsive_control(
			'arrows_valign',
			array(
				'label'     => __( 'Allineamento verticale', 'lu3g-carousel' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Alto', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-top',
					),
					'center'     => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-middle',
					),
					'flex-end'   => array(
						'title' => __( 'Basso', 'lu3g-carousel' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'default'   => 'center',
				'condition' => array(
					'show_arrows'     => 'yes',
					'arrows_position' => array( 'start', 'end', 'split', 'overlay' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel--arrows-start .lu3g-carousel__nav, {{WRAPPER}} .lu3g-carousel--arrows-end .lu3g-carousel__nav, {{WRAPPER}} .lu3g-carousel--arrows-split .lu3g-carousel__nav' => 'align-self: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel--arrows-overlay .lu3g-carousel__nav' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_offset',
			array(
				'label'       => __( 'Distanza dal bordo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => -100,
						'max' => 100,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 16,
				),
				'description' => __( 'Valori negativi portano le frecce fuori dalle card.', 'lu3g-carousel' ),
				'condition'   => array(
					'show_arrows'     => 'yes',
					'arrows_position' => 'overlay',
				),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-arrow-offset: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_spacing',
			array(
				'label'      => __( 'Distanza tra le frecce', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 12,
				),
				'condition'  => array(
					'show_arrows'     => 'yes',
					'arrows_mode'     => 'double',
					'arrows_position' => array( 'start', 'end', 'above', 'below' ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel__nav' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);


		$this->add_control(
			'arrow_prev_icon',
			array(
				'label'     => __( 'Icona indietro', 'lu3g-carousel' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'eicon-arrow-left',
					'library' => 'eicons',
				),
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'arrow_next_icon',
			array(
				'label'     => __( 'Icona avanti', 'lu3g-carousel' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'eicon-arrow-right',
					'library' => 'eicons',
				),
				'condition' => array(
					'show_arrows' => 'yes',
					'arrows_mode' => 'double',
				),
			)
		);

		$this->add_control(
			'hide_arrows_mobile',
			array(
				'label'        => __( 'Nascondi su mobile', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Su touch lo swipe è già sufficiente.', 'lu3g-carousel' ),
				'condition'    => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'hide_idle_arrows',
			array(
				'label'        => __( 'Nascondi se non c\'è niente da scorrere', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Quando tutte le card sono già visibili, le frecce spariscono invece di restare spente. Si ricalcola al ridimensionamento: su mobile, dove le card visibili sono meno, ricompaiono.', 'lu3g-carousel' ),
				'condition'    => array( 'show_arrows' => 'yes' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Registra una sezione di stile per un blocco di testo.
	 *
	 * Etichetta e stili testo 2 e 3 hanno gli stessi controlli: tipografia,
	 * distanza e colore con le tab Normale / Hover. Una sola funzione li
	 * genera tutti, così restano coerenti tra loro.
	 *
	 * @param string $id          Prefisso univoco dei controlli.
	 * @param string $label       Titolo della sezione.
	 * @param string $selector    Classe del blocco, senza {{WRAPPER}}.
	 * @param string $gap_var     Variabile CSS della distanza.
	 * @param string $gap_label   Etichetta del controllo distanza.
	 * @param int    $gap_default Distanza predefinita in px.
	 * @return void
	 */
	private function lu3g_register_block_style( $id, $label, $selector, $gap_var, $gap_label, $gap_default ) {

		$this->start_controls_section(
			'section_' . $id . '_style',
			array(
				'label'     => $label,
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $id . '_typography',
				'selector' => '{{WRAPPER}} .lu3g-carousel ' . $selector,
			)
		);

		$this->add_responsive_control(
			$id . '_spacing',
			array(
				'label'      => $gap_label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => $gap_default,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => $gap_var . ': {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( $id . '_state_tabs' );

		$this->start_controls_tab(
			$id . '_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);

		$this->add_control(
			$id . '_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			$id . '_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);

		$this->add_control(
			$id . '_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover ' . $selector => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Tab Contenuto — Indicatori di posizione: puntini, barra o numeri.
	 *
	 * @return void
	 */
	private function register_indicator_controls() {

		$this->start_controls_section(
			'section_indicators',
			array(
				'label' => __( 'Indicatori di posizione', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'indicator_type',
			array(
				'label'   => __( 'Tipo', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'     => __( 'Nessuno', 'lu3g-carousel' ),
					'dots'     => __( 'Puntini', 'lu3g-carousel' ),
					'progress' => __( 'Barra di avanzamento', 'lu3g-carousel' ),
					'fraction' => __( 'Numeri (es. 2 / 6)', 'lu3g-carousel' ),
				),
				'description' => __( 'Si possono usare insieme alle frecce o al loro posto. Compaiono solo se c\'è qualcosa da scorrere.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'indicator_position',
			array(
				'label'       => __( 'Posizione', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'below',
				'options'     => array(
					'below'   => __( 'Sotto il carosello', 'lu3g-carousel' ),
					'above'   => __( 'Sopra il carosello', 'lu3g-carousel' ),
					'overlay' => __( 'Sovrapposti alle card, in basso', 'lu3g-carousel' ),
					'between' => __( 'Tra le due frecce', 'lu3g-carousel' ),
				),
				'description' => __( '"Tra le due frecce" richiede due frecce a sinistra, a destra, sopra o sotto; altrimenti gli indicatori vanno sotto il carosello.', 'lu3g-carousel' ),
				'condition'   => array( 'indicator_type!' => 'none' ),
			)
		);

		$this->add_responsive_control(
			'indicator_align',
			array(
				'label'     => __( 'Allineamento', 'lu3g-carousel' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Sinistra', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Destra', 'lu3g-carousel' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'   => 'center',
				'condition' => array(
					'indicator_type!'     => 'none',
					'indicator_position!' => 'between',
				),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__indicators' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'indicator_gap',
			array(
				'label'       => __( 'Distanza dal carosello', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 24,
				),
				'description' => __( 'Sovrapposti alle card: distanza dal bordo inferiore.', 'lu3g-carousel' ),
				'condition'   => array(
					'indicator_type!'     => 'none',
					'indicator_position!' => 'between',
				),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-indicator-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'hide_indicators_mobile',
			array(
				'label'        => __( 'Nascondi su mobile', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'condition'    => array( 'indicator_type!' => 'none' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Indicatori di posizione.
	 *
	 * @return void
	 */
	private function register_indicator_style_controls() {

		$this->start_controls_section(
			'section_indicators_style',
			array(
				'label'     => __( 'Indicatori di posizione', 'lu3g-carousel' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'indicator_type!' => 'none' ),
			)
		);

		// --- Puntini ---
		$dots = array( 'indicator_type' => 'dots' );

		$this->add_responsive_control(
			'dot_size',
			array(
				'label'      => __( 'Dimensione', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'condition'  => $dots,
				'selectors'  => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-dot-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'dot_active_width',
			array(
				'label'       => __( 'Larghezza del puntino attivo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 4, 'max' => 80 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 24 ),
				'description' => __( 'Uguale alla dimensione per puntini tutti tondi; più larga per l\'effetto "pillola".', 'lu3g-carousel' ),
				'condition'   => $dots,
				'selectors'   => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-dot-active-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'dot_spacing',
			array(
				'label'      => __( 'Distanza tra i puntini', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'condition'  => $dots,
				'selectors'  => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-dot-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => $dots,
				'selectors' => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-dot-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dot_color_active',
			array(
				'label'     => __( 'Colore del puntino attivo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => $dots,
				'selectors' => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-dot-active-color: {{VALUE}};' ),
			)
		);

		// --- Barra ---
		$bar = array( 'indicator_type' => 'progress' );

		$this->add_responsive_control(
			'progress_width',
			array(
				'label'       => __( 'Larghezza', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%', 'px' ),
				'range'       => array(
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'px' => array( 'min' => 40, 'max' => 800 ),
				),
				'default'     => array( 'unit' => '%', 'size' => 100 ),
				'description' => __( 'Tra le frecce la percentuale si riferisce a uno spazio di 160px.', 'lu3g-carousel' ),
				'condition'   => $bar,
				'selectors'   => array( '{{WRAPPER}} .lu3g-carousel__progress' => 'width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'progress_height',
			array(
				'label'      => __( 'Spessore', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 12 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 2 ),
				'condition'  => $bar,
				'selectors'  => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-progress-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'progress_track_color',
			array(
				'label'     => __( 'Colore della traccia', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => $bar,
				'selectors' => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-progress-track: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'progress_bar_color',
			array(
				'label'     => __( 'Colore della barra', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => $bar,
				'selectors' => array( '{{WRAPPER}} .lu3g-carousel' => '--lu3g-progress-color: {{VALUE}};' ),
			)
		);

		// --- Numeri ---
		$fraction = array( 'indicator_type' => 'fraction' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'fraction_typography',
				'selector'  => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__fraction',
				'condition' => $fraction,
			)
		);

		$this->add_control(
			'fraction_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => $fraction,
				'selectors' => array( '{{WRAPPER}} .lu3g-carousel__fraction' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Immagini, solo per il tipo di contenuto Immagini.
	 *
	 * @return void
	 */
	private function register_image_style_controls() {

		$this->start_controls_section(
			'section_image_style',
			array(
				'label'     => __( 'Immagini', 'lu3g-carousel' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type' => 'gallery' ),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'       => __( 'Adattamento', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'cover',
				'options'     => array(
					'cover'   => __( 'Riempi la card, ritagliando', 'lu3g-carousel' ),
					'contain' => __( 'Intera, senza ritagli', 'lu3g-carousel' ),
				),
				'description' => __( '"Intera" lascia vuoti ai lati o sopra e sotto quando la foto ha una proporzione diversa dalla card: si vede lo sfondo della card.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel__image' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_focus',
			array(
				'label'       => __( 'Punto di ritaglio', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'center center',
				'options'     => array(
					'center center' => __( 'Centro', 'lu3g-carousel' ),
					'center top'    => __( 'In alto', 'lu3g-carousel' ),
					'center bottom' => __( 'In basso', 'lu3g-carousel' ),
					'left center'   => __( 'A sinistra', 'lu3g-carousel' ),
					'right center'  => __( 'A destra', 'lu3g-carousel' ),
				),
				'description' => __( 'La parte della foto che resta sempre visibile quando viene ritagliata.', 'lu3g-carousel' ),
				'condition'   => array( 'image_fit' => 'cover' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel__image' => 'object-position: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_hover_zoom',
			array(
				'label'       => __( 'Zoom in hover', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array( '' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'default'     => array( 'size' => 1.05 ),
				'description' => __( 'L\'immagine si ingrandisce dentro la card, che resta ferma. 1 per disattivarlo.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-image-zoom: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'image_overlay_hover',
			array(
				'label'       => __( 'Velatura in hover', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Colore sovrapposto alla foto al passaggio del mouse. Usa un colore semitrasparente.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel__card--image:hover::after' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Card.
	 *
	 * @return void
	 */
	private function register_card_style_controls() {

		$this->start_controls_section(
			'section_card_style',
			array(
				'label' => __( 'Card', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'card_state_tabs' );

		$this->start_controls_tab(
			'card_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'card_bg',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#F4F4F6',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .lu3g-carousel__card',
				'fields_options' => array(
					'border' => array( 'default' => 'solid' ),
					'width'  => array(
						'default' => array(
							'top'      => 1,
							'right'    => 1,
							'bottom'   => 1,
							'left'     => 1,
							'isLinked' => true,
						),
					),
					'color'  => array( 'default' => '#1B1A47' ),
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'card_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'card_bg_hover',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_hover',
			array(
				'label'     => __( 'Bordo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover' => 'border-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => __( 'Raggio dei bordi', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => 12,
					'right'    => 12,
					'bottom'   => 12,
					'left'     => 12,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => 24,
					'right'    => 24,
					'bottom'   => 24,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'mobile_default' => array(
					'top'      => 18,
					'right'    => 18,
					'bottom'   => 18,
					'left'     => 18,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Icona.
	 *
	 * @return void
	 */
	private function register_icon_style_controls() {

		$this->start_controls_section(
			'section_icon_style',
			array(
				'label' => __( 'Icona', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);


		// I valori sono direttamente quelli di flex-direction: così il
		// controllo responsive funziona da solo, senza classi per breakpoint.
		$this->add_responsive_control(
			'icon_position',
			array(
				'label'     => __( 'Posizione', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'column',
				'options'   => array(
					'column'         => __( 'Sopra il testo', 'lu3g-carousel' ),
					'column-reverse' => __( 'Sotto il testo', 'lu3g-carousel' ),
					'row'            => __( 'A sinistra', 'lu3g-carousel' ),
					'row-reverse'    => __( 'A destra', 'lu3g-carousel' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__body' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Dimensione', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 160,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 48,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 36,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-icon-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_gap',
			array(
				'label'      => __( 'Distanza dal testo', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-icon-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->start_controls_tabs( 'icon_state_tabs' );

		$this->start_controls_tab(
			'icon_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'icon_color',
			array(
				'label'       => __( 'Colore', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Vale per le icone vettoriali. Le immagini raster non sono ricolorabili.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel__icon svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel__icon svg [stroke]:not([stroke="none"])' => 'stroke: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'icon_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover .lu3g-carousel__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel__card:hover .lu3g-carousel__icon svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel__card:hover .lu3g-carousel__icon svg [stroke]:not([stroke="none"])' => 'stroke: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Titolo.
	 *
	 * @return void
	 */
	private function register_text_style_controls() {

		$this->start_controls_section(
			'section_text_style',
			array(
				'label' => __( 'Titolo', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .lu3g-carousel__text',
			)
		);

		$this->add_responsive_control(
			'text_align',
			array(
				'label'     => __( 'Allineamento', 'lu3g-carousel' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Sinistra', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Destra', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_min_lines',
			array(
				'label'       => __( 'Righe riservate', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array(
					'' => array(
						'min'  => 0,
						'max'  => 5,
						'step' => 1,
					),
				),
				'default'     => array( 'size' => 0 ),
				'description' => __( 'Riserva lo spazio di N righe anche ai titoli più corti, così le descrizioni partono tutte alla stessa altezza. Zero per disattivare.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-title-lines: {{SIZE}};',
				),
			)
		);
		$this->start_controls_tabs( 'title_state_tabs' );

		$this->start_controls_tab(
			'title_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1B1A47',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__text' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'title_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'text_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover .lu3g-carousel__text' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Stile testo 1. Gli ID restano description_* per non perdere i valori salvati.
	 *
	 * @return void
	 */
	private function register_description_style_controls() {

		$this->start_controls_section(
			'section_description_style',
			array(
				'label' => __( 'Stile testo 1', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .lu3g-carousel__block--style-1',
			)
		);

		$this->add_responsive_control(
			'description_spacing',
			array(
				'label'      => __( 'Distanza dal blocco sopra', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 12,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-description-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->start_controls_tabs( 'description_state_tabs' );

		$this->start_controls_tab(
			'description_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__block--style-1' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'description_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'description_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__card:hover .lu3g-carousel__block--style-1' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Mostra di più. Sezione propria: il troncamento può agire sul titolo o su un blocco di testo.
	 *
	 * @return void
	 */
	private function register_toggle_style_controls() {

		$this->start_controls_section(
			'section_toggle_style',
			array(
				'label' => __( 'Mostra di più', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery', 'truncate' => 'yes' ),
			)
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'toggle_typography',
				'selector'  => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle',
				'condition' => array( 'truncate' => 'yes' ),
			)
		);

		$this->add_control(
			'toggle_gap',
			array(
				'label'      => __( 'Distanza dal testo', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'condition'  => array( 'truncate' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-toggle-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'toggle_underline',
			array(
				'label'        => __( 'Sottolineatura', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'truncate' => 'yes' ),
				'selectors'    => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle' => 'text-decoration: underline;',
				),
			)
		);
		$this->start_controls_tabs( 'toggle_state_tabs' );

		$this->start_controls_tab(
			'toggle_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'toggle_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'truncate' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'toggle_bg',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'condition' => array( 'truncate' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle' => 'background-color: {{VALUE}} !important; background-image: none !important;',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'toggle_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'toggle_color_hover',
			array(
				'label'       => __( 'Colore', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'condition'   => array( 'truncate' => 'yes' ),
				'description' => __( 'Si attiva passando sulla card, non solo sul pulsante.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__body .lu3g-carousel__toggle' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'toggle_bg_hover',
			array(
				'label'       => __( 'Sfondo', 'lu3g-carousel' ),
				'type'        => Controls_Manager::COLOR,
				'condition'   => array( 'truncate' => 'yes' ),
				'description' => __( 'Si attiva passando sulla card, non solo sul pulsante.', 'lu3g-carousel' ),
				'selectors'   => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__body .lu3g-carousel__toggle' => 'background-color: {{VALUE}} !important; background-image: none !important;',
				),
			)
		);

		$this->add_control(
			'toggle_hover_fade',
			array(
				'label'        => __( 'Sbiadisci in hover', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'truncate' => 'yes' ),
				'description'  => __( 'Disattiva se hai impostato uno sfondo pieno: lo sbiadimento lo renderebbe slavato.', 'lu3g-carousel' ),
				'selectors'    => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-toggle-hover-opacity: .7;',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'           => 'toggle_border',
				'selector'       => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle',
				'condition'      => array( 'truncate' => 'yes' ),
				'fields_options' => array(
					'border' => array( 'default' => 'none' ),
				),
			)
		);

		$this->add_responsive_control(
			'toggle_radius',
			array(
				'label'      => __( 'Raggio dei bordi', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'condition'  => array( 'truncate' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_padding',
			array(
				'label'      => __( 'Padding', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'condition'  => array( 'truncate' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__body .lu3g-carousel__toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Pulsante della card.
	 *
	 * @return void
	 */
	private function register_button_style_controls() {

		$this->start_controls_section(
			'section_button_style',
			array(
				'label' => __( 'Pulsante', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'source_type!' => 'gallery' ),
			)
		);


		$this->add_responsive_control(
			'button_align',
			array(
				'label'     => __( 'Allineamento', 'lu3g-carousel' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Sinistra', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => __( 'Centro', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Destra', 'lu3g-carousel' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__actions' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_spacing',
			array(
				'label'      => __( 'Distanza dal testo', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-button-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button',
			)
		);

		$this->start_controls_tabs( 'button_state_tabs' );

		$this->start_controls_tab(
			'button_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);

		$this->add_control(
			'button_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button' => 'color: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);

		// L'hover scatta passando sulla card, non solo sul pulsante: tutta
		// la card è la zona d'interesse, e il pulsante ne segnala l'azione.
		$this->add_control(
			'button_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__button' => 'color: {{VALUE}};',
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__button svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg_hover',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_hover',
			array(
				'label'     => __( 'Bordo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__card:hover .lu3g-carousel__button' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_icon_shift',
			array(
				'label'      => __( 'Spostamento icona', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-button-icon-shift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'button_border',
				'selector'  => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'button_radius',
			array(
				'label'      => __( 'Raggio dei bordi', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_icon_heading',
			array(
				'label'     => __( 'Icona', 'lu3g-carousel' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'button_icon_size',
			array(
				'label'      => __( 'Dimensione', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 6,
						'max' => 48,
					),
				),
				'default'    => array(
					'unit' => 'em',
					'size' => 0.9,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-button-icon-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_icon_gap',
			array(
				'label'      => __( 'Distanza dal testo', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 8,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-button-icon-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Frecce.
	 *
	 * @return void
	 */
	private function register_nav_style_controls() {

		$this->start_controls_section(
			'section_nav_style',
			array(
				'label' => __( 'Frecce', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);


		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => __( 'Dimensione', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 28,
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel__arrow' => '--lu3g-arrow-size: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->start_controls_tabs( 'arrow_state_tabs' );

		$this->start_controls_tab(
			'arrow_tab_normal',
			array( 'label' => __( 'Normale', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1B1A47',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__arrow' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__arrow' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'arrow_tab_hover',
			array( 'label' => __( 'Hover', 'lu3g-carousel' ) )
		);


		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => __( 'Colore', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel__arrow:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg_hover',
			array(
				'label'     => __( 'Sfondo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__arrow:hover' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'           => 'arrow_border',
				'selector'       => '{{WRAPPER}} .lu3g-carousel .lu3g-carousel__arrow',
				'fields_options' => array(
					'border' => array( 'default' => 'none' ),
				),
			)
		);

		$this->add_responsive_control(
			'arrow_radius',
			array(
				'label'      => __( 'Raggio dei bordi', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_padding',
			array(
				'label'      => __( 'Padding', 'lu3g-carousel' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel .lu3g-carousel__arrow' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_gap',
			array(
				'label'      => __( 'Distanza dal carosello', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'condition'  => array( 'arrows_position!' => 'overlay' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-arrow-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_disabled_opacity',
			array(
				'label'      => __( 'Opacità quando inattiva', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					),
				),
				'default'    => array( 'size' => 0.3 ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel__arrow.is-disabled' => 'opacity: {{SIZE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Tab Stile — Animazioni.
	 *
	 * @return void
	 */
	private function register_animation_controls() {

		$this->start_controls_section(
			'section_animation',
			array(
				'label' => __( 'Animazioni', 'lu3g-carousel' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);


		$this->add_control(
			'entry_heading',
			array(
				'label' => __( 'Ingresso', 'lu3g-carousel' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'enable_animation',
			array(
				'label'        => __( 'Attiva', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => __( 'Le card entrano in cascata quando la sezione arriva a schermo.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'animation_type',
			array(
				'label'     => __( 'Tipo', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide-up',
				'options'   => array(
					'fade'        => __( 'Dissolvenza', 'lu3g-carousel' ),
					'slide-up'    => __( 'Dal basso', 'lu3g-carousel' ),
					'slide-down'  => __( 'Dall\'alto', 'lu3g-carousel' ),
					'slide-right' => __( 'Da sinistra', 'lu3g-carousel' ),
					'slide-left'  => __( 'Da destra', 'lu3g-carousel' ),
					'zoom-in'     => __( 'Ingrandimento', 'lu3g-carousel' ),
					'zoom-out'    => __( 'Rimpicciolimento', 'lu3g-carousel' ),
					'blur'        => __( 'Sfocatura', 'lu3g-carousel' ),
					'flip'        => __( 'Ribaltamento', 'lu3g-carousel' ),
					'reveal'      => __( 'Scoprimento dal basso', 'lu3g-carousel' ),
				),
				'condition' => array( 'enable_animation' => 'yes' ),
			)
		);

		$this->add_control(
			'animation_offset',
			array(
				'label'      => __( 'Distanza di partenza', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'condition'  => array(
					'enable_animation' => 'yes',
					'animation_type'   => array( 'slide-up', 'slide-down', 'slide-left', 'slide-right', 'reveal' ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-anim-offset: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'animation_duration',
			array(
				'label'      => __( 'Durata (ms)', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 100,
						'max'  => 1500,
						'step' => 50,
					),
				),
				'default'    => array( 'size' => 500 ),
				'condition'  => array( 'enable_animation' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-anim-duration: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'animation_easing',
			array(
				'label'     => __( 'Andamento', 'lu3g-carousel' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cubic-bezier(.2, .7, .3, 1)',
				'options'   => array(
					'cubic-bezier(.2, .7, .3, 1)'         => __( 'Morbido (predefinito)', 'lu3g-carousel' ),
					'linear'                              => __( 'Lineare', 'lu3g-carousel' ),
					'ease-out'                            => __( 'Decelerato', 'lu3g-carousel' ),
					'cubic-bezier(.34, 1.56, .64, 1)'     => __( 'Con rimbalzo', 'lu3g-carousel' ),
					'cubic-bezier(.83, 0, .17, 1)'        => __( 'Deciso', 'lu3g-carousel' ),
				),
				'condition' => array( 'enable_animation' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-anim-easing: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'animation_stagger',
			array(
				'label'       => __( 'Ritardo tra le card (ms)', 'lu3g-carousel' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '' ),
				'range'       => array(
					'' => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 10,
					),
				),
				'default'     => array( 'size' => 80 ),
				'condition'   => array( 'enable_animation' => 'yes' ),
				'description' => __( 'Zero per farle entrare tutte insieme.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'animation_repeat',
			array(
				'label'        => __( 'Ripeti ogni volta', 'lu3g-carousel' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'condition'    => array( 'enable_animation' => 'yes' ),
				'description'  => __( 'Di norma l\'animazione parte una volta sola.', 'lu3g-carousel' ),
			)
		);

		$this->add_control(
			'hover_heading',
			array(
				'label'     => __( 'Hover', 'lu3g-carousel' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'   => __( 'Effetto sulla card', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lift',
				'options' => array(
					'none'   => __( 'Nessuno', 'lu3g-carousel' ),
					'lift'   => __( 'Sollevamento', 'lu3g-carousel' ),
					'sink'   => __( 'Abbassamento', 'lu3g-carousel' ),
					'zoom'   => __( 'Ingrandimento', 'lu3g-carousel' ),
					'shrink' => __( 'Rimpicciolimento', 'lu3g-carousel' ),
					'tilt'   => __( 'Inclinazione', 'lu3g-carousel' ),
					'glow'   => __( 'Ombra', 'lu3g-carousel' ),
				),
			)
		);

		$this->add_control(
			'hover_distance',
			array(
				'label'      => __( 'Intensità', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 30,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 6,
				),
				'condition'  => array( 'hover_effect' => array( 'lift', 'sink' ) ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-hover-distance: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'hover_scale',
			array(
				'label'      => __( 'Fattore di scala', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 0.8,
						'max'  => 1.2,
						'step' => 0.01,
					),
				),
				'default'    => array( 'size' => 1.03 ),
				'condition'  => array( 'hover_effect' => array( 'zoom', 'shrink' ) ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-hover-scale: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'hover_text_effect',
			array(
				'label'   => __( 'Effetto sul testo', 'lu3g-carousel' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'  => __( 'Nessuno', 'lu3g-carousel' ),
					'shift' => __( 'Scorrimento laterale', 'lu3g-carousel' ),
					'rise'  => __( 'Sollevamento', 'lu3g-carousel' ),
					'line'  => __( 'Sottolineatura', 'lu3g-carousel' ),
				),
			)
		);

		$this->add_control(
			'hover_duration',
			array(
				'label'      => __( 'Durata (ms)', 'lu3g-carousel' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '' ),
				'range'      => array(
					'' => array(
						'min'  => 50,
						'max'  => 800,
						'step' => 25,
					),
				),
				'default'    => array( 'size' => 250 ),
				'selectors'  => array(
					'{{WRAPPER}} .lu3g-carousel' => '--lu3g-hover-duration: {{SIZE}}ms;',
				),
			)
		);
		$this->end_controls_section();
	}

	/* ---------------------------------------------------------------------
	 * CONTENUTO
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * LAYOUT
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * NAVIGAZIONE
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * STILE — CARD
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * STILE — TESTO
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * STILE — FRECCE
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * ANIMAZIONE
	 * ------------------------------------------------------------------ */

	/* ---------------------------------------------------------------------
	 * RENDER
	 * ------------------------------------------------------------------ */

	/**
	 * Tag e attributi SVG ammessi quando un file viene inserito nel markup.
	 *
	 * Inserire un SVG nella pagina significa eseguirne il markup: la lista
	 * tiene fuori script, eventi e riferimenti esterni. Copre le forme che
	 * un'icona esportata da un editor vettoriale usa davvero.
	 *
	 * @return array
	 */
	private function lu3g_allowed_svg_tags() {
		$attrs = array(
			'class'             => true,
			'id'                => true,
			'fill'              => true,
			'fill-opacity'      => true,
			'fill-rule'         => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-dasharray'  => true,
			'opacity'           => true,
			'transform'         => true,
			'clip-path'         => true,
			'clip-rule'         => true,
			'style'             => true,
		);

		return array(
			'svg'            => array_merge(
				$attrs,
				array(
					'xmlns'               => true,
					'viewbox'             => true,
					'width'               => true,
					'height'              => true,
					'preserveaspectratio' => true,
					'role'                => true,
					'aria-hidden'         => true,
					'focusable'           => true,
				)
			),
			'g'              => $attrs,
			'path'           => array_merge( $attrs, array( 'd' => true ) ),
			'circle'         => array_merge( $attrs, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
			'ellipse'        => array_merge( $attrs, array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true ) ),
			'rect'           => array_merge( $attrs, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
			'line'           => array_merge( $attrs, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
			'polyline'       => array_merge( $attrs, array( 'points' => true ) ),
			'polygon'        => array_merge( $attrs, array( 'points' => true ) ),
			'defs'           => array(),
			'clippath'       => array( 'id' => true ),
			'title'          => array(),
			'lineargradient' => array( 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true ),
			'radialgradient' => array( 'id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'gradientunits' => true ),
			'stop'           => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ),
		);
	}

	/**
	 * Markup di un'icona a partire da un file della libreria media.
	 *
	 * Un SVG viene inserito nel markup invece che dentro un tag img, perché
	 * solo così può ereditare il colore impostato nel pannello: un'immagine
	 * non è ricolorabile via CSS.
	 *
	 * @param mixed $value Id allegato, URL, o array del campo media.
	 * @return string
	 */
	private function lu3g_media_icon_markup( $value ) {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['id'] ) ) {
				$value = $value['id'];
			} elseif ( ! empty( $value['url'] ) ) {
				$value = $value['url'];
			} else {
				return '';
			}
		}

		if ( ! $value ) {
			return '';
		}

		$id  = 0;
		$url = '';

		if ( is_numeric( $value ) ) {
			$id  = (int) $value;
			$url = wp_get_attachment_url( $id );
		} else {
			$url = (string) $value;
			$id  = attachment_url_to_postid( $url );
		}

		if ( ! $url ) {
			return '';
		}

		$is_svg = ( 'svg' === strtolower( (string) pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ) );

		if ( $is_svg && $id ) {
			$path = get_attached_file( $id );

			if ( $path && is_readable( $path ) ) {
				$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions

				if ( $svg ) {
					// Via la dichiarazione XML e i commenti, che dentro la
					// pagina non servono e sporcano il markup.
					$svg = preg_replace( '/<\?xml.*?\?>/is', '', $svg );
					$svg = preg_replace( '/<!--.*?-->/s', '', $svg );
					$svg = preg_replace( '/<!DOCTYPE.*?>/is', '', $svg );

					return wp_kses( trim( $svg ), $this->lu3g_allowed_svg_tags() );
				}
			}
		}

		$alt = $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';

		return sprintf(
			'<img src="%s" alt="%s" loading="lazy" />',
			esc_url( $url ),
			esc_attr( $alt )
		);
	}

	/**
	 * Markup di un'icona scelta col selettore di Elementor.
	 *
	 * @param array $icon Valore del controllo ICONS.
	 * @return string
	 */
	private function lu3g_elementor_icon_markup( $icon ) {
		if ( empty( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );

		return (string) ob_get_clean();
	}

	/**
	 * Restituisce le card da stampare, normalizzate.
	 *
	 * Le due sorgenti hanno forme diverse — righe di meta field da una parte,
	 * righe del repeater di Elementor dall'altra — e vengono ridotte qui alla
	 * stessa struttura, così il render non deve sapere da dove arrivano.
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return array Elenco di array con chiavi content, url, new_tab, rel.
	 */
	private function lu3g_get_cards( $settings ) {
		if ( 'manual' === $settings['source_type'] ) {
			return $this->lu3g_get_manual_cards( $settings );
		}

		if ( 'gallery' === $settings['source_type'] ) {
			return $this->lu3g_get_gallery_cards( $settings );
		}

		return $this->lu3g_get_dynamic_cards( $settings );
	}

	/**
	 * Card della sorgente Immagini: una per immagine della galleria.
	 *
	 * Hanno le stesse chiavi delle altre sorgenti, vuote, più l'immagine: il
	 * resto del widget (loop, frecce, indicatori, animazioni) non distingue.
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return array
	 */
	private function lu3g_get_gallery_cards( $settings ) {
		$images = isset( $settings['gallery_images'] ) && is_array( $settings['gallery_images'] )
			? $settings['gallery_images']
			: array();

		$link = in_array( $settings['gallery_link'], array( 'none', 'lightbox', 'file' ), true )
			? $settings['gallery_link']
			: 'none';

		$items = array();

		foreach ( $images as $image ) {
			$id  = isset( $image['id'] ) ? (int) $image['id'] : 0;
			$src = isset( $image['url'] ) ? (string) $image['url'] : '';

			$markup = $this->lu3g_gallery_image_markup( $id, $src, $settings );

			if ( '' === $markup ) {
				continue;
			}

			$full = $id ? wp_get_attachment_image_url( $id, 'full' ) : $src;

			$items[] = array(
				'class'          => '',
				'kicker'         => '',
				'content'        => '',
				'description'    => '',
				'desc_style'     => '1',
				'blocks'         => array(),
				'icon'           => '',
				'url'            => ( 'none' !== $link && $full ) ? esc_url( $full ) : '',
				'new_tab'        => false,
				'rel'            => '',
				'button_text'    => '',
				'button_url'     => '',
				'button_new_tab' => false,
				'button_rel'     => '',
				'image'          => $markup,
				'image_link'     => $link,
				'caption'        => $id ? (string) wp_get_attachment_caption( $id ) : '',
			);
		}

		return $items;
	}

	/**
	 * Tag img di un'immagine della galleria.
	 *
	 * Con una dimensione registrata si usa wp_get_attachment_image, che
	 * aggiunge srcset e sizes: il browser scarica la versione adatta allo
	 * schermo. Con una dimensione personalizzata si usa l'URL ritagliato da
	 * Elementor.
	 *
	 * @param int    $id       ID dell'allegato, 0 se l'immagine ha solo un URL.
	 * @param string $src      URL di ripiego.
	 * @param array  $settings Impostazioni del widget.
	 * @return string
	 */
	private function lu3g_gallery_image_markup( $id, $src, $settings ) {
		$size = isset( $settings['gallery_image_size'] ) ? (string) $settings['gallery_image_size'] : 'large';

		if ( $id && 'custom' !== $size ) {
			return (string) wp_get_attachment_image(
				$id,
				$size,
				false,
				array(
					'class'    => 'lu3g-carousel__image',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}

		if ( $id ) {
			$src = Group_Control_Image_Size::get_attachment_image_src( $id, 'gallery_image', $settings );
		}

		if ( ! $src ) {
			return '';
		}

		$alt = $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';

		return sprintf(
			'<img class="lu3g-carousel__image" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			esc_url( $src ),
			esc_attr( $alt )
		);
	}

	/**
	 * Card scritte direttamente in Elementor.
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return array
	 */
	private function lu3g_get_manual_cards( $settings ) {
		$rows = isset( $settings['manual_items'] ) ? $settings['manual_items'] : array();

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$items = array();

		foreach ( $rows as $row ) {
			$content = $this->lu3g_clean_value(
				isset( $row['card_text'] ) ? $row['card_text'] : '',
				$settings
			);

			if ( '' === $content ) {
				continue;
			}

			$description = $this->lu3g_rich_text(
				isset( $row['card_description'] ) ? $row['card_description'] : ''
			);

			$blocks = $this->lu3g_collect_blocks( $row );

			$kicker = isset( $row['card_kicker'] )
				? trim( wp_strip_all_tags( (string) $row['card_kicker'] ) )
				: '';

			// La classe unica della riga: è quella che {{CURRENT_ITEM}}
			// usa per i colori propri della card.
			$item_class = ! empty( $row['_id'] )
				? 'elementor-repeater-item-' . sanitize_html_class( $row['_id'] )
				: '';

			// Sfondo con immagine: la classe attiva i selettori dello sfondo e
			// la velatura. Senza immagine non si aggiunge, così una velatura
			// scura non copre per sbaglio una card a tinta unita.
			$has_bg_image = isset( $row['item_bg_enable'], $row['item_bg_image']['url'] )
				&& 'yes' === $row['item_bg_enable']
				&& '' !== trim( (string) $row['item_bg_image']['url'] );

			if ( $has_bg_image ) {
				$item_class = trim( $item_class . ' lu3g-carousel__card--has-bg' );
			}

			$button_text = isset( $row['card_button_text'] )
				? trim( wp_strip_all_tags( (string) $row['card_button_text'] ) )
				: '';

			$link    = isset( $row['card_link'] ) ? $row['card_link'] : array();
			$url     = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '';
			$new_tab = ! empty( $link['is_external'] );
			$rel     = ! empty( $link['nofollow'] ) ? 'nofollow' : '';

			// Con un pulsante il link va lì e la card resta un div: un link
			// dentro un altro link non è markup valido.
			$has_button = ( '' !== $button_text );

			$items[] = array(
				'class'          => $item_class,
				'kicker'         => $kicker,
				'content'        => $content,
				'description'    => $description,
				'desc_style'     => $this->lu3g_style_key( $row, 'card_description_style', '1' ),
				'blocks'         => $blocks,
				'icon'           => $this->lu3g_elementor_icon_markup(
					isset( $row['card_icon'] ) ? $row['card_icon'] : array()
				),
				'url'            => $has_button ? '' : $url,
				'new_tab'        => $new_tab,
				'rel'            => $rel,
				'button_text'    => $button_text,
				'button_url'     => $has_button ? $url : '',
				'button_new_tab' => $new_tab,
				'button_rel'     => $rel,
			);
		}

		return $items;
	}

	/**
	 * Card lette da un repeater JetEngine.
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return array
	 */
	private function lu3g_get_dynamic_cards( $settings ) {
		$rows = $this->lu3g_read_repeater_rows( $settings );

		if ( empty( $rows ) ) {
			return array();
		}

		$items   = array();
		$new_tab = ( 'yes' === $settings['link_target'] );

		$from_text = ( 'text' === $settings['dynamic_link_source'] );

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			// Modalità "dal testo": il primo link trovato diventa il link del
			// pulsante, e nel testo resta solo il suo contenuto, senza <a>.
			$text_link = $from_text ? $this->lu3g_extract_text_link( $row, $settings ) : null;

			if ( $text_link ) {
				$row = $text_link['row'];
			}

			$content = $this->lu3g_build_card_content( $row, $settings );

			if ( '' === $content ) {
				continue;
			}

			$icon_key = isset( $settings['icon_field'] ) ? trim( $settings['icon_field'] ) : '';
			$icon     = ( '' !== $icon_key && isset( $row[ $icon_key ] ) )
				? $this->lu3g_media_icon_markup( $row[ $icon_key ] )
				: '';

			$kicker_key = isset( $settings['kicker_field'] ) ? trim( $settings['kicker_field'] ) : '';
			$kicker     = ( '' !== $kicker_key && isset( $row[ $kicker_key ] ) && is_scalar( $row[ $kicker_key ] ) )
				? trim( wp_strip_all_tags( (string) $row[ $kicker_key ] ) )
				: '';

			// Il primo blocco configurato fa da "descrizione", così il
			// troncamento e il render lo trattano come nelle card statiche.
			$blocks      = $this->lu3g_dynamic_blocks( $row, $settings );
			$first       = array_shift( $blocks );
			$description = $first ? $first['html'] : '';
			$desc_style  = $first ? $first['style'] : '1';

			$button_text = $this->lu3g_dynamic_button_text( $row, $settings );
			$url         = $this->lu3g_get_row_url( $row, $settings );
			$row_new_tab = $new_tab;

			if ( $from_text ) {
				// Il pulsante esiste solo se la riga ha un link; senza testo
				// del pulsante impostato si usa un'etichetta predefinita.
				$url = $text_link ? $text_link['url'] : '';

				if ( '' === $url ) {
					$button_text = '';
				} elseif ( '' === $button_text ) {
					$button_text = __( 'Scopri di più', 'lu3g-carousel' );
				}

				$row_new_tab = $new_tab || ( $text_link && $text_link['new_tab'] );
			}

			$has_button = ( '' !== $button_text );

			$items[] = array(
				'class'          => '',
				'kicker'         => $kicker,
				'content'        => $content,
				'description'    => $description,
				'desc_style'     => $desc_style,
				'blocks'         => $blocks,
				'icon'           => $icon,
				// Stessa regola delle card statiche: col pulsante il link
				// va lì, e la card resta un div.
				'url'            => $has_button ? '' : $url,
				'new_tab'        => $new_tab,
				'rel'            => '',
				'button_text'    => $button_text,
				'button_url'     => $has_button ? $url : '',
				'button_new_tab' => $row_new_tab,
				'button_rel'     => '',
			);
		}

		return $items;
	}

	/**
	 * Cerca il primo link in una riga del repeater.
	 *
	 * Guarda, nell'ordine in cui compaiono nella card, il sottocampo del
	 * titolo (o quelli del formato avanzato) e i sottocampi dei blocchi di
	 * testo. Il link trovato viene tolto dal testo, lasciando il suo
	 * contenuto: così non si ripete tra testo e pulsante, e il titolo non
	 * diventa un link dentro la card.
	 *
	 * @param array $row      Riga del repeater.
	 * @param array $settings Impostazioni del widget.
	 * @return array|null Chiavi url, new_tab e row (la riga ripulita), o null.
	 */
	private function lu3g_extract_text_link( $row, $settings ) {
		$keys = array();

		$format = isset( $settings['item_format'] ) ? trim( (string) $settings['item_format'] ) : '';

		if ( '' !== $format ) {
			if ( preg_match_all( '/%([a-zA-Z0-9_\-]+)%/', $format, $m ) ) {
				$keys = $m[1];
			}
		} elseif ( ! empty( $settings['sub_field'] ) ) {
			$keys[] = trim( (string) $settings['sub_field'] );
		}

		if ( ! empty( $settings['dynamic_blocks'] ) && is_array( $settings['dynamic_blocks'] ) ) {
			foreach ( $settings['dynamic_blocks'] as $block ) {
				if ( ! empty( $block['block_field'] ) ) {
					$keys[] = trim( (string) $block['block_field'] );
				}
			}
		}

		$pattern = '/<a\b([^>]*)>(.*?)<\/a>/is';

		foreach ( array_unique( $keys ) as $key ) {
			if ( ! isset( $row[ $key ] ) || ! is_string( $row[ $key ] ) ) {
				continue;
			}

			if ( ! preg_match( $pattern, $row[ $key ], $match ) ) {
				continue;
			}

			if ( ! preg_match( '/\bhref\s*=\s*(["\'])(.*?)\1/is', $match[1], $href ) ) {
				continue;
			}

			$url = esc_url_raw( html_entity_decode( $href[2], ENT_QUOTES ) );

			if ( '' === $url ) {
				continue;
			}

			$row[ $key ] = preg_replace( $pattern, '$2', $row[ $key ], 1 );

			return array(
				'url'     => $url,
				'new_tab' => (bool) preg_match( '/\btarget\s*=\s*(["\'])_blank\1/i', $match[1] ),
				'row'     => $row,
			);
		}

		return null;
	}

	/**
	 * Spiega nell'editor perché la sorgente dinamica non produce card.
	 *
	 * Ripercorre gli stessi passaggi della lettura — nome del campo, post,
	 * righe, sottocampo — e si ferma al primo che non torna. Se il problema
	 * è il sottocampo, elenca quelli presenti nel repeater: l'errore più
	 * comune è lasciare il valore di esempio "testo".
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return string Messaggio non ancora escapato.
	 */
	private function lu3g_dynamic_diagnosis( $settings ) {
		$field = ! empty( $settings['repeater_select'] )
			? $settings['repeater_select']
			: ( isset( $settings['repeater_name'] ) ? trim( $settings['repeater_name'] ) : '' );

		if ( '' === $field ) {
			return __( 'Scegli il campo repeater dall\'elenco o scrivine il nome in "Nome del campo (manuale)".', 'lu3g-carousel' );
		}

		$post_id = ! empty( $settings['source_post_id'] ) ? absint( $settings['source_post_id'] ) : get_the_ID();

		$rows = $this->lu3g_read_repeater_rows( $settings );

		if ( empty( $rows ) ) {
			return sprintf(
				/* translators: 1: nome del campo, 2: ID del post */
				__( 'Il campo "%1$s" è vuoto o non esiste nel post di anteprima (ID %2$s). Controlla il nome, e che il post usato per l\'anteprima del template abbia il repeater compilato.', 'lu3g-carousel' ),
				$field,
				$post_id ? (string) $post_id : '—'
			);
		}

		// Le righe ci sono: il problema è nei sottocampi.
		$available = array();

		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$available = array_merge( $available, array_keys( $row ) );
			}
		}

		$available = array_values( array_unique( array_filter( $available, 'is_string' ) ) );
		$sub       = isset( $settings['sub_field'] ) ? trim( (string) $settings['sub_field'] ) : '';
		$format    = isset( $settings['item_format'] ) ? trim( (string) $settings['item_format'] ) : '';

		if ( '' === $format && ! in_array( $sub, $available, true ) ) {
			return sprintf(
				/* translators: 1: numero di righe, 2: sottocampo impostato, 3: elenco dei sottocampi */
				__( 'Trovate %1$d righe, ma nessuna ha il sottocampo "%2$s". Sottocampi disponibili: %3$s. Scrivi uno di questi in "Sottocampo da mostrare".', 'lu3g-carousel' ),
				count( $rows ),
				'' === $sub ? '—' : $sub,
				$available ? implode( ', ', $available ) : '—'
			);
		}

		return sprintf(
			/* translators: 1: numero di righe, 2: sottocampo */
			__( 'Trovate %1$d righe, ma il sottocampo "%2$s" è vuoto in tutte.', 'lu3g-carousel' ),
			count( $rows ),
			'' === $format ? $sub : $format
		);
	}

	/**
	 * Legge le righe del repeater.
	 *
	 * @param array $settings Impostazioni del widget.
	 * @return array
	 */
	private function lu3g_read_repeater_rows( $settings ) {

		$field = ! empty( $settings['repeater_select'] )
			? $settings['repeater_select']
			: ( isset( $settings['repeater_name'] ) ? trim( $settings['repeater_name'] ) : '' );

		if ( ! $field ) {
			return array();
		}

		$post_id = ! empty( $settings['source_post_id'] )
			? absint( $settings['source_post_id'] )
			: get_the_ID();

		if ( ! $post_id ) {
			return array();
		}

		$rows = get_post_meta( $post_id, $field, true );

		if ( is_string( $rows ) && '' !== $rows ) {
			$decoded = json_decode( $rows, true );
			$rows    = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		// JetEngine restituisce a volte un array associativo indicizzato per riga.
		return array_values( $rows );
	}

	/**
	 * Blocchi di testo di una riga del repeater JetEngine.
	 *
	 * Ogni blocco configurato nel pannello indica un sottocampo e uno stile.
	 * I valori passano per "Solo testo" come gli altri campi del repeater, e
	 * poi per wpautop, così un testo con a capo diventa paragrafi come nelle
	 * card statiche. I blocchi vuoti vengono saltati.
	 *
	 * @param array $row      Riga del repeater.
	 * @param array $settings Impostazioni del widget.
	 * @return array Elenco di array con chiavi html e style.
	 */
	private function lu3g_dynamic_blocks( $row, $settings ) {
		$config = isset( $settings['dynamic_blocks'] ) && is_array( $settings['dynamic_blocks'] )
			? $settings['dynamic_blocks']
			: array();

		$out = array();

		foreach ( $config as $block ) {
			$key = isset( $block['block_field'] ) ? trim( (string) $block['block_field'] ) : '';

			if ( '' === $key || ! isset( $row[ $key ] ) ) {
				continue;
			}

			$value = $this->lu3g_clean_value( $row[ $key ], $settings );

			if ( '' === trim( wp_strip_all_tags( $value ) ) ) {
				continue;
			}

			$out[] = array(
				'html'  => wp_kses_post( wpautop( $value ) ),
				'style' => $this->lu3g_style_key( $block, 'block_style', '1' ),
			);
		}

		return $out;
	}

	/**
	 * Testo del pulsante di una riga del repeater JetEngine.
	 *
	 * Può essere fisso ("Scopri") o contenere %nome_sottocampo%, sostituito
	 * col valore della riga. Se dopo la sostituzione resta vuoto, la card
	 * non ha pulsante.
	 *
	 * @param array $row      Riga del repeater.
	 * @param array $settings Impostazioni del widget.
	 * @return string Testo semplice, non ancora escapato.
	 */
	private function lu3g_dynamic_button_text( $row, $settings ) {
		$template = isset( $settings['dynamic_button_text'] ) ? trim( (string) $settings['dynamic_button_text'] ) : '';

		if ( '' === $template ) {
			return '';
		}

		$text = preg_replace_callback(
			'/%([a-zA-Z0-9_\-]+)%/',
			function ( $matches ) use ( $row ) {
				$key = $matches[1];
				return ( isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ) ? (string) $row[ $key ] : '';
			},
			$template
		);

		return trim( wp_strip_all_tags( $text ) );
	}

	/**
	 * Legge lo stile scelto per un blocco, accettando solo i tre previsti.
	 *
	 * Il valore finisce in un nome di classe: un elenco chiuso evita che un
	 * dato inatteso produca una classe arbitraria.
	 *
	 * @param array  $row     Riga del repeater.
	 * @param string $key     Chiave del controllo di stile.
	 * @param string $default Stile di ripiego.
	 * @return string
	 */
	private function lu3g_style_key( $row, $key, $default ) {
		$value = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';

		return in_array( $value, array( '1', '2', '3' ), true ) ? $value : $default;
	}

	/**
	 * Raccoglie i blocchi di testo aggiuntivi di una card.
	 *
	 * La catena si interrompe al primo interruttore spento: un blocco
	 * compilato ma rimasto dopo un anello disattivato non viene stampato,
	 * coerentemente con quello che il pannello mostra. I blocchi attivi ma
	 * vuoti vengono saltati.
	 *
	 * @param array $row Riga del repeater.
	 * @return array Elenco di array con chiavi html e style.
	 */
	private function lu3g_collect_blocks( $row ) {
		$blocks = array();

		for ( $n = 1; $n <= self::LU3G_MAX_EXTRA_BLOCKS; $n++ ) {
			if ( ! isset( $row[ 'card_add_' . $n ] ) || 'yes' !== $row[ 'card_add_' . $n ] ) {
				break;
			}

			$html = $this->lu3g_rich_text(
				isset( $row[ 'card_extra_' . $n ] ) ? $row[ 'card_extra_' . $n ] : ''
			);

			if ( '' === $html ) {
				continue;
			}

			$blocks[] = array(
				'html'  => $html,
				'style' => $this->lu3g_style_key(
					$row,
					'card_extra_' . $n . '_style',
					( 1 === $n ) ? '2' : ( ( 2 === $n ) ? '3' : '1' )
				),
			);
		}

		return $blocks;
	}

	/**
	 * Prepara il contenuto di un campo a editor visuale.
	 *
	 * Questi campi ignorano "Solo testo": chi li compila con l'editor vuole
	 * la formattazione. wpautop trasforma in paragrafi anche il testo
	 * semplice salvato quando la descrizione era una textarea, così le card
	 * esistenti non perdono gli a capo.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return string HTML filtrato, o stringa vuota.
	 */
	private function lu3g_rich_text( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		// Un editor svuotato può lasciare paragrafi vuoti o spazi: contano
		// come campo vuoto, altrimenti si stamperebbe un blocco senza testo.
		if ( '' === trim( wp_strip_all_tags( $value ) ) ) {
			return '';
		}

		return wp_kses_post( wpautop( $value ) );
	}

	/**
	 * Normalizza un valore per la stampa.
	 *
	 * I campi WYSIWYG di JetEngine restituiscono HTML: viene filtrato con
	 * wp_kses_post() invece che escapato, altrimenti i tag comparirebbero
	 * come testo. Con "Solo testo" attivo i tag vengono rimossi.
	 *
	 * @param mixed $value    Valore grezzo.
	 * @param array $settings Impostazioni del widget.
	 * @return string
	 */
	private function lu3g_clean_value( $value, $settings ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		$value = (string) $value;

		if ( isset( $settings['strip_tags'] ) && 'yes' === $settings['strip_tags'] ) {
			return esc_html( trim( wp_strip_all_tags( $value ) ) );
		}

		return wp_kses_post( $value );
	}

	/**
	 * Costruisce il contenuto di una card dal repeater JetEngine.
	 *
	 * @param array $row      Riga del repeater.
	 * @param array $settings Impostazioni del widget.
	 * @return string HTML già filtrato.
	 */
	private function lu3g_build_card_content( $row, $settings ) {

		$self   = $this;
		$format = isset( $settings['item_format'] ) ? trim( $settings['item_format'] ) : '';

		if ( '' !== $format ) {
			$output = preg_replace_callback(
				'/%([a-zA-Z0-9_\-]+)%/',
				function ( $matches ) use ( $row, $settings, $self ) {
					$key = $matches[1];
					return isset( $row[ $key ] ) ? $self->lu3g_clean_value( $row[ $key ], $settings ) : '';
				},
				$format
			);

			return wp_kses_post( $output );
		}

		$sub = isset( $settings['sub_field'] ) ? trim( $settings['sub_field'] ) : '';

		if ( '' === $sub || ! isset( $row[ $sub ] ) ) {
			return '';
		}

		return $this->lu3g_clean_value( $row[ $sub ], $settings );
	}

	/**
	 * Estrae l'URL di una riga, se configurato.
	 *
	 * @param array $row      Riga del repeater.
	 * @param array $settings Impostazioni del widget.
	 * @return string
	 */
	private function lu3g_get_row_url( $row, $settings ) {

		$key = isset( $settings['link_field'] ) ? trim( $settings['link_field'] ) : '';

		if ( '' === $key || ! isset( $row[ $key ] ) ) {
			return '';
		}

		$value = $row[ $key ];

		// I campi media/link di JetEngine possono arrivare come array.
		if ( is_array( $value ) ) {
			$value = isset( $value['url'] ) ? $value['url'] : '';
		}

		return esc_url( (string) $value );
	}

	/**
	 * Stampa una freccia.
	 *
	 * @param string $direction 'prev' o 'next'.
	 * @param array  $settings  Impostazioni del widget.
	 * @return void
	 */
	private function lu3g_render_arrow( $direction, $settings ) {

		$icon_key = ( 'next' === $direction ) ? 'arrow_next_icon' : 'arrow_prev_icon';
		$icon     = isset( $settings[ $icon_key ] ) ? $settings[ $icon_key ] : array();

		$label = ( 'next' === $direction )
			? __( 'Card successive', 'lu3g-carousel' )
			: __( 'Card precedenti', 'lu3g-carousel' );

		printf(
			'<button type="button" class="lu3g-carousel__arrow lu3g-carousel__arrow--%1$s" data-lu3g-dir="%1$s" aria-label="%2$s">',
			esc_attr( $direction ),
			esc_attr( $label )
		);

		if ( ! empty( $icon['value'] ) ) {
			Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
		}

		echo '</button>';
	}

	/**
	 * Stampa un contenitore di frecce.
	 *
	 * @param string $where    Posizione, usata come modificatore di classe.
	 * @param array  $arrows   Direzioni da stampare, 'prev' e/o 'next'.
	 * @param array  $settings Impostazioni del widget.
	 * @return void
	 */
	private function lu3g_render_nav( $where, $arrows, $settings, $between = '' ) {
		printf( '<div class="lu3g-carousel__nav lu3g-carousel__nav--%s">', esc_attr( $where ) );

		foreach ( $arrows as $i => $direction ) {
			// Gli indicatori "tra le frecce" vanno dopo la prima.
			if ( 1 === $i && '' !== $between ) {
				echo $between; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			$this->lu3g_render_arrow( $direction, $settings );
		}

		echo '</div>';
	}

	/**
	 * Markup degli indicatori di posizione.
	 *
	 * I puntini non si stampano qui: il loro numero dipende da quante
	 * posizioni di scorrimento esistono, che si sa solo nel browser, e li
	 * crea il JS. Il contenitore nasce hidden e il JS lo mostra solo se c'è
	 * qualcosa da scorrere: senza JS non compare un indicatore vuoto.
	 *
	 * @param string $type dots, progress o fraction.
	 * @return string
	 */
	private function lu3g_indicators_markup( $type ) {
		$inner = '';

		if ( 'progress' === $type ) {
			$inner = '<div class="lu3g-carousel__progress"><span class="lu3g-carousel__progress-bar"></span></div>';
		} elseif ( 'fraction' === $type ) {
			$inner = '<span class="lu3g-carousel__fraction" aria-live="polite"></span>';
		}

		return sprintf(
			'<div class="lu3g-carousel__indicators lu3g-carousel__indicators--%1$s" data-lu3g-indicators="%1$s" data-lu3g-dot-label="%3$s" hidden>%2$s</div>',
			esc_attr( $type ),
			$inner,
			/* translators: 1: posizione, 2: totale. Letto dal JS per i puntini. */
			esc_attr__( 'Vai alla posizione %1$s di %2$s', 'lu3g-carousel' )
		);
	}

	/**
	 * Render del widget sul frontend.
	 *
	 * @return void
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		// Elementor restituisce sempre i default, ma un widget salvato con una
		// versione precedente del plugin può non avere tutte le chiavi.
		$settings = wp_parse_args(
			$settings,
			array(
				'source_type'        => 'dynamic',
				'arrows_mode'        => 'single',
				'show_arrows'        => 'yes',
				'arrows_position'    => 'start',
				'enable_animation'   => 'yes',
				'animation_repeat'   => '',
				'height_mode'        => 'yes',
				'scroll_snap'        => 'yes',
				'hide_arrows_mobile' => '',
				'hide_idle_arrows'   => '',
				'link_target'        => '',
				'item_format'        => '',
				'link_field'         => '',
				'animation_type'     => 'slide-up',
				'hover_effect'       => 'lift',
				'hover_text_effect'  => 'none',
				'card_width_mode'    => 'auto',
				'content_layout'     => 'grouped',
				'center_mode'        => '',
				'kicker_field'       => '',
				'gallery_images'     => array(),
				'gallery_link'       => 'none',
				'gallery_image_size' => 'large',
				'indicator_type'     => 'none',
				'indicator_position' => 'below',
				'hide_indicators_mobile' => '',
				'dynamic_blocks'     => array(),
				'dynamic_button_text' => '',
				'dynamic_link_source' => 'field',
				'title_tag'          => 'span',
				'truncate_target'    => 'title',
				'button_icon'        => array(),
				'icon_field'         => '',
				'truncate'           => '',
				'expand_label'       => __( 'Mostra di più', 'lu3g-carousel' ),
				'collapse_label'     => __( 'Mostra meno', 'lu3g-carousel' ),
				'loop'               => '',
				'autoplay'           => '',
				'pause_on_hover'     => 'yes',
			)
		);

		// Il ritardo si inserisce in secondi ma viaggia in millisecondi.
		$delay = isset( $settings['autoplay_delay']['size'] )
			? (int) round( (float) $settings['autoplay_delay']['size'] * 1000 )
			: 4000;

		$speed = isset( $settings['scroll_speed']['size'] )
			? (int) $settings['scroll_speed']['size']
			: 600;

		$items = $this->lu3g_get_cards( $settings );

		if ( empty( $items ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				$messages = array(
					'manual'  => __( 'Nessuna card. Aggiungine una dalla sezione Card del pannello.', 'lu3g-carousel' ),
					'gallery' => __( 'Nessuna immagine. Aggiungile dalla sezione Card del pannello.', 'lu3g-carousel' ),
					'dynamic' => __( 'Nessuna riga trovata. Controlla il nome del campo repeater e che il post abbia dei valori.', 'lu3g-carousel' ),
				);
				$message  = isset( $messages[ $settings['source_type'] ] ) ? $messages[ $settings['source_type'] ] : $messages['dynamic'];

				// Per la sorgente dinamica un messaggio generico non basta:
				// si dice quale dei passaggi non torna.
				if ( 'dynamic' === $settings['source_type'] ) {
					$message = $this->lu3g_dynamic_diagnosis( $settings );
				}

				printf(
					'<div class="lu3g-carousel__empty">%s</div>',
					esc_html( $message )
				);
			}
			return;
		}

		$single   = ( 'single' === $settings['arrows_mode'] );
		$show_nav = ( 'yes' === $settings['show_arrows'] );
		$position = $settings['arrows_position'];
		$animate  = ( 'yes' === $settings['enable_animation'] );
		$stagger  = isset( $settings['animation_stagger']['size'] ) ? (int) $settings['animation_stagger']['size'] : 80;

		// Con una sola freccia la posizione "ai due lati" non ha senso.
		if ( $single && 'split' === $position ) {
			$position = 'start';
		}

		$valid_positions = array( 'start', 'end', 'split', 'above', 'below', 'overlay' );

		if ( ! in_array( $position, $valid_positions, true ) ) {
			$position = 'start';
		}

		// Una freccia sola fa da avanti e indietro, e il JS la inverte a fine
		// corsa; con due, ognuna ha la sua direzione.
		$arrow_set = $single ? array( 'prev' ) : array( 'prev', 'next' );

		$indicator_type = in_array( $settings['indicator_type'], array( 'dots', 'progress', 'fraction' ), true )
			? $settings['indicator_type']
			: '';

		$indicator_position = in_array( $settings['indicator_position'], array( 'below', 'above', 'overlay', 'between' ), true )
			? $settings['indicator_position']
			: 'below';

		// "Tra le frecce" serve un contenitore con entrambe le frecce: con
		// una freccia sola, frecce ai lati, sovrapposte o spente, gli
		// indicatori vanno sotto il carosello.
		if ( 'between' === $indicator_position
			&& ! ( $show_nav && ! $single && in_array( $position, array( 'start', 'end', 'above', 'below' ), true ) ) ) {
			$indicator_position = 'below';
		}

		$indicators = $indicator_type ? $this->lu3g_indicators_markup( $indicator_type ) : '';

		$classes = array( 'lu3g-carousel' );

		// Con le frecce spente la posizione può arrivare vuota: niente classe
		// monca tipo "lu3g-carousel--arrows-".
		if ( $show_nav && '' !== $position ) {
			$classes[] = 'lu3g-carousel--arrows-' . sanitize_html_class( $position );
		}

		if ( $animate ) {
			$classes[] = 'lu3g-carousel--in-' . sanitize_html_class( $settings['animation_type'] );
		}

		if ( 'none' !== $settings['hover_effect'] ) {
			$classes[] = 'lu3g-carousel--hover-' . sanitize_html_class( $settings['hover_effect'] );
		}

		if ( 'none' !== $settings['hover_text_effect'] ) {
			$classes[] = 'lu3g-carousel--text-' . sanitize_html_class( $settings['hover_text_effect'] );
		}

		if ( 'fixed' === $settings['card_width_mode'] ) {
			$classes[] = 'lu3g-carousel--fixed-width';
		}

		if ( 'yes' === $settings['center_mode'] ) {
			$classes[] = 'lu3g-carousel--center';
		}

		if ( 'spread' === $settings['content_layout'] ) {
			$classes[] = 'lu3g-carousel--spread';
		}

		if ( $indicator_type ) {
			$classes[] = 'lu3g-carousel--has-indicators';
			$classes[] = 'lu3g-carousel--indicators-' . $indicator_position;

			if ( 'yes' === $settings['hide_indicators_mobile'] ) {
				$classes[] = 'lu3g-carousel--hide-indicators-mobile';
			}
		}

		// Un button dentro un <a> è markup non valido, quindi il troncamento
		// interattivo si disattiva quando le card sono cliccabili. Il
		// controllo è sulla lista reale, così vale per entrambe le sorgenti.
		$has_link = false;

		foreach ( $items as $item ) {
			if ( '' !== $item['url'] ) {
				$has_link = true;
				break;
			}
		}

		$truncate = ( 'yes' === $settings['truncate'] && ! $has_link && 'gallery' !== $settings['source_type'] );

		// Il tag viene dal pannello: si accetta solo un elenco chiuso, perché
		// finisce stampato come nome di elemento HTML.
		$allowed_tags = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );
		$title_tag    = in_array( $settings['title_tag'], $allowed_tags, true ) ? $settings['title_tag'] : 'span';

		$toggle_markup = '';

		if ( $truncate ) {
			$toggle_markup = sprintf(
				'<div class="lu3g-carousel__toggle-wrap"><button type="button" class="lu3g-carousel__toggle" data-lu3g-toggle data-label-expand="%1$s" data-label-collapse="%2$s" aria-expanded="false" hidden>%3$s</button></div>',
				esc_attr( $settings['expand_label'] ),
				esc_attr( $settings['collapse_label'] ),
				esc_html( $settings['expand_label'] )
			);
		}

		$button_icon = ! empty( $settings['button_icon'] )
			? $this->lu3g_elementor_icon_markup( $settings['button_icon'] )
			: '';

		if ( $truncate ) {
			$classes[] = 'lu3g-carousel--clamp';
		}

		if ( 'yes' === $settings['height_mode'] ) {
			$classes[] = 'lu3g-carousel--fixed-height';
		}

		if ( 'yes' === $settings['scroll_snap'] ) {
			$classes[] = 'lu3g-carousel--snap';
		}

		if ( 'yes' === $settings['hide_idle_arrows'] ) {
			$classes[] = 'lu3g-carousel--hide-idle-arrows';
		}

		if ( 'yes' === $settings['hide_arrows_mobile'] ) {
			$classes[] = 'lu3g-carousel--hide-arrows-mobile';
		}

		$this->add_render_attribute(
			'wrapper',
			array(
				'class'               => $classes,
				'data-lu3g-carousel'  => '',
				'data-lu3g-animate'   => $animate ? '1' : '0',
				'data-lu3g-stagger'   => (string) $stagger,
				'data-lu3g-repeat'    => ( 'yes' === $settings['animation_repeat'] ) ? '1' : '0',
				'data-lu3g-mode'      => $single ? 'single' : 'double',
				'data-lu3g-loop'      => ( 'yes' === $settings['loop'] ) ? '1' : '0',
				'data-lu3g-autoplay'  => ( 'yes' === $settings['autoplay'] ) ? '1' : '0',
				'data-lu3g-delay'     => (string) $delay,
				'data-lu3g-speed'     => (string) $speed,
				'data-lu3g-pause'     => ( 'yes' === $settings['pause_on_hover'] ) ? '1' : '0',
			)
		);

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>

			<?php
			if ( $indicators && 'above' === $indicator_position ) {
				echo $indicators; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			// Frecce prima del viewport: a sinistra, sopra, o la sola freccia
			// "indietro" quando stanno ai due lati.
			if ( $show_nav && in_array( $position, array( 'start', 'above', 'split' ), true ) ) {
				$this->lu3g_render_nav(
					$position,
					'split' === $position ? array( 'prev' ) : $arrow_set,
					$settings,
					'between' === $indicator_position ? $indicators : ''
				);
			}
			?>

			<?php
			// Lo stage racchiude il viewport ed è il riferimento per tutto ciò
			// che si sovrappone alle card: frecce e indicatori sovrapposti si
			// posizionano sulle sole card, non su card più indicatori.
			?>
			<div class="lu3g-carousel__stage">
			<div class="lu3g-carousel__viewport" data-lu3g-viewport tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Carosello di card', 'lu3g-carousel' ); ?>">
				<div class="lu3g-carousel__track" data-lu3g-track>
					<?php
					foreach ( $items as $index => $item ) :

						$url = $item['url'];
						$tag = $url ? 'a' : 'div';

						// L'indice alimenta il ritardo a cascata dell'animazione.
						$card_class = trim( 'lu3g-carousel__card ' . $item['class'] );
						$attrs      = 'class="' . esc_attr( $card_class ) . '" style="--lu3g-i: ' . (int) $index . ';"';

						if ( $url ) {
							$attrs .= ' href="' . esc_url( $url ) . '"';

							$rel = trim( 'noopener noreferrer ' . $item['rel'] );

							if ( $item['new_tab'] ) {
								$attrs .= ' target="_blank" rel="' . esc_attr( $rel ) . '"';
							} elseif ( $item['rel'] ) {
								$attrs .= ' rel="' . esc_attr( $item['rel'] ) . '"';
							}
						}

						// Card immagine: niente corpo testuale, solo la foto.
						if ( ! empty( $item['image'] ) ) {
							$image_attrs = str_replace(
								'class="lu3g-carousel__card',
								'class="lu3g-carousel__card lu3g-carousel__card--image',
								$attrs
							);

							if ( $url && 'lightbox' === $item['image_link'] ) {
								// Il lightbox di Elementor raggruppa le immagini con
								// lo stesso valore di slideshow: le frecce del lightbox
								// scorrono tutta la galleria del carosello.
								$image_attrs .= ' data-elementor-open-lightbox="yes"'
									. ' data-elementor-lightbox-slideshow="lu3g-' . esc_attr( $this->get_id() ) . '"';

								if ( '' !== $item['caption'] ) {
									$image_attrs .= ' data-elementor-lightbox-title="' . esc_attr( $item['caption'] ) . '"';
								}
							} elseif ( $url ) {
								// "Apri il file": senza questo, il lightbox globale di
								// Elementor intercetterebbe comunque il link a un'immagine.
								$image_attrs .= ' data-elementor-open-lightbox="no"';
							}

							printf(
								'<%1$s %2$s>%3$s</%1$s>',
								esc_html( $tag ),
								$image_attrs, // phpcs:ignore WordPress.Security.EscapeOutput
								$item['image'] // phpcs:ignore WordPress.Security.EscapeOutput
							);

							continue;
						}
						?>
						<<?php echo esc_html( $tag ) . ' ' . $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<div class="lu3g-carousel__body">
								<?php if ( '' !== $item['icon'] ) : ?>
									<span class="lu3g-carousel__icon" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<?php endif; ?>
								<?php
								// Si tronca la descrizione se richiesto e se c'è;
								// altrimenti il titolo, che c'è sempre.
								$clamp_description = $truncate
									&& 'description' === $settings['truncate_target']
									&& '' !== $item['description'];
								$clamp_title       = $truncate && ! $clamp_description;

								$title_class = 'lu3g-carousel__text' . ( $clamp_title ? ' lu3g-carousel__clamp' : '' );
								// La descrizione resta un blocco come gli altri, con la
								// classe dello stile scelto; la classe __description
								// resta per compatibilità con CSS scritti a mano.
								$desc_class = 'lu3g-carousel__block lu3g-carousel__block--style-' . $item['desc_style']
									. ' lu3g-carousel__description'
									. ( $clamp_description ? ' lu3g-carousel__clamp' : '' );
								?>
								<div class="lu3g-carousel__content">
									<?php if ( '' !== $item['kicker'] ) : ?>
										<span class="lu3g-carousel__kicker"><?php echo esc_html( $item['kicker'] ); ?></span>
									<?php endif; ?>
									<<?php echo esc_html( $title_tag ); ?> class="<?php echo esc_attr( $title_class ); ?>"><?php echo $item['content']; // phpcs:ignore WordPress.Security.EscapeOutput ?></<?php echo esc_html( $title_tag ); ?>>
									<?php
									if ( $clamp_title ) {
										echo $toggle_markup; // phpcs:ignore WordPress.Security.EscapeOutput
									}
									?>

									<?php if ( '' !== $item['description'] ) : ?>
										<div class="<?php echo esc_attr( $desc_class ); ?>"><?php echo $item['description']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
										<?php
										if ( $clamp_description ) {
											echo $toggle_markup; // phpcs:ignore WordPress.Security.EscapeOutput
										}
										?>
									<?php endif; ?>

									<?php foreach ( $item['blocks'] as $block ) : ?>
										<div class="lu3g-carousel__block lu3g-carousel__block--style-<?php echo esc_attr( $block['style'] ); ?>"><?php echo $block['html']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									<?php endforeach; ?>

									<?php if ( '' !== $item['button_text'] ) : ?>
										<div class="lu3g-carousel__actions">
											<?php
											$button_tag   = $item['button_url'] ? 'a' : 'span';
											$button_attrs = 'class="lu3g-carousel__button"';

											if ( $item['button_url'] ) {
												$button_attrs .= ' href="' . esc_url( $item['button_url'] ) . '"';

												if ( $item['button_new_tab'] ) {
													$button_attrs .= ' target="_blank" rel="' . esc_attr( trim( 'noopener noreferrer ' . $item['button_rel'] ) ) . '"';
												} elseif ( $item['button_rel'] ) {
													$button_attrs .= ' rel="' . esc_attr( $item['button_rel'] ) . '"';
												}
											}
											?>
											<<?php echo esc_html( $button_tag ) . ' ' . $button_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
												<span class="lu3g-carousel__button-text"><?php echo esc_html( $item['button_text'] ); ?></span>
												<?php if ( '' !== $button_icon ) : ?>
													<span class="lu3g-carousel__button-icon" aria-hidden="true"><?php echo $button_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
												<?php endif; ?>
											</<?php echo esc_html( $button_tag ); ?>>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</<?php echo esc_html( $tag ); ?>>
					<?php endforeach; ?>
				</div>
			</div>

			<?php
			// Tutto ciò che è sovrapposto alle card sta dentro lo stage.
			if ( $show_nav && 'overlay' === $position ) {
				$this->lu3g_render_nav( 'overlay', $arrow_set, $settings );
			}

			if ( $indicators && 'overlay' === $indicator_position ) {
				echo $indicators; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
			</div>

			<?php
			// Frecce dopo lo stage: a destra, sotto, o la sola freccia
			// "avanti" quando stanno ai due lati.
			if ( $show_nav && in_array( $position, array( 'end', 'below', 'split' ), true ) ) {
				$this->lu3g_render_nav(
					'split' === $position ? 'end' : $position,
					'split' === $position ? array( 'next' ) : $arrow_set,
					$settings,
					'between' === $indicator_position ? $indicators : ''
				);
			}

			if ( $indicators && 'below' === $indicator_position ) {
				echo $indicators; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>

		</div>
		<?php
	}
}
