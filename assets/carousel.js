/**
 * LU3G Repeater Carousel
 *
 * L'inizializzazione è idempotente: ogni carosello viene marcato con un flag
 * così può essere richiamata più volte senza duplicare i listener. Serve
 * nell'editor Elementor, dove i widget vengono ricostruiti a ogni modifica.
 */
( function () {
	'use strict';

	var INIT_FLAG = 'lu3gCarouselReady';

	/**
	 * Curva di accelerazione dello scorrimento programmato.
	 *
	 * @param {number} t Avanzamento da 0 a 1.
	 * @return {number}
	 */
	function easeInOutCubic( t ) {
		return t < 0.5
			? 4 * t * t * t
			: 1 - Math.pow( -2 * t + 2, 3 ) / 2;
	}

	/**
	 * Inizializza un singolo carosello.
	 *
	 * @param {HTMLElement} root Elemento con data-lu3g-carousel.
	 */
	function initCarousel( root ) {
		if ( ! root || root.dataset[ INIT_FLAG ] === '1' ) {
			return;
		}

		var viewport = root.querySelector( '[data-lu3g-viewport]' );
		var track = root.querySelector( '[data-lu3g-track]' );

		if ( ! viewport || ! track ) {
			return;
		}

		// Blocco con poche card. La copia va presa prima di qualsiasi
		// modifica: se cambiando dispositivo lo stato si inverte, il
		// carosello viene ricostruito da qui, con loop e modalità centrata
		// accesi o spenti da capo invece che smontati a metà.
		var lock = readLock( root );
		var pristine = lock ? root.cloneNode( true ) : null;
		var locked = lock ? isLocked( lock ) : false;

		if ( locked ) {
			root.classList.add( 'is-locked' );
			root.classList.remove( 'lu3g-carousel--center' );
		}

		root.dataset[ INIT_FLAG ] = '1';

		var arrows = Array.prototype.slice.call( root.querySelectorAll( '[data-lu3g-dir]' ) );
		var singleMode = root.getAttribute( 'data-lu3g-mode' ) === 'single';
		var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		var speed = parseInt( root.getAttribute( 'data-lu3g-speed' ), 10 );
		if ( isNaN( speed ) || speed < 0 ) {
			speed = 600;
		}

		var loop = root.getAttribute( 'data-lu3g-loop' ) === '1' && ! locked;
		var autoplay = root.getAttribute( 'data-lu3g-autoplay' ) === '1' && ! reduced && ! locked;
		var pauseOnHover = root.getAttribute( 'data-lu3g-pause' ) === '1';

		var delay = parseInt( root.getAttribute( 'data-lu3g-delay' ), 10 );
		if ( isNaN( delay ) || delay < 500 ) {
			delay = 4000;
		}

		// Direzione corrente: con una sola freccia si inverte a fine corsa,
		// e in autoplay senza loop fa lo stesso.
		var dir = 1;
		var animation = null;
		var setWidth = 0;
		var timer = null;
		var paused = false;

		// Vero mentre lo scorrimento è in movimento, slancio inerziale
		// incluso: serve a non riallineare il loop nel mezzo del gesto.
		var scrolling = false;
		var settleTimer = null;

		/* -----------------------------------------------------------------
		 * Scorrimento programmato
		 * -------------------------------------------------------------- */

		/**
		 * Interrompe un'animazione in corso.
		 */
		function cancelAnimation() {
			if ( animation ) {
				window.cancelAnimationFrame( animation );
				animation = null;
			}
		}

		/**
		 * Scorre fino a una posizione con durata controllata.
		 *
		 * Non si usa scrollBy con behavior smooth perché la sua durata la
		 * decide il browser e non sarebbe regolabile. Durante l'animazione
		 * lo snap viene sospeso: con scroll-snap mandatory il browser
		 * riaggancerebbe la posizione a ogni frame.
		 *
		 * @param {number}   target   Posizione di destinazione in pixel.
		 * @param {Function} [onDone] Callback a fine corsa.
		 */
		function scrollTo( target, onDone ) {
			cancelAnimation();

			var start = viewport.scrollLeft;
			var distance = target - start;

			if ( ! distance ) {
				if ( onDone ) {
					onDone();
				}
				return;
			}

			if ( reduced || speed < 50 ) {
				viewport.scrollLeft = target;
				if ( onDone ) {
					onDone();
				}
				return;
			}

			var snap = viewport.style.scrollSnapType;
			viewport.style.scrollSnapType = 'none';

			var startTime = null;

			function step( now ) {
				if ( null === startTime ) {
					startTime = now;
				}

				var progress = Math.min( ( now - startTime ) / speed, 1 );

				viewport.scrollLeft = start + distance * easeInOutCubic( progress );

				if ( progress < 1 ) {
					animation = window.requestAnimationFrame( step );
					return;
				}

				animation = null;
				viewport.style.scrollSnapType = snap;

				if ( onDone ) {
					onDone();
				}
			}

			animation = window.requestAnimationFrame( step );
		}

		/**
		 * Passo di scorrimento: larghezza di una card più il gap.
		 *
		 * Ricalcolato a ogni chiamata perché la card può essere dimensionata
		 * in percentuale e cambia con il viewport.
		 *
		 * @return {number} Pixel.
		 */
		function getStep() {
			var card = track.querySelector( '.lu3g-carousel__card' );

			if ( ! card ) {
				return viewport.clientWidth;
			}

			var gap = parseFloat( window.getComputedStyle( track ).columnGap );

			if ( isNaN( gap ) ) {
				gap = 0;
			}

			return card.offsetWidth + gap;
		}

		/* -----------------------------------------------------------------
		 * Scorrimento infinito
		 *
		 * Il set di card viene duplicato prima e dopo l'originale, e la
		 * posizione parte dal set centrale. Quando lo scorrimento esce dalla
		 * fascia centrale, scrollLeft viene spostato di un set intero senza
		 * animazione: il salto è invisibile perché il contenuto è identico.
		 * -------------------------------------------------------------- */

		/**
		 * Prepara un clone perché non sia raggiungibile dai lettori di
		 * schermo né dalla tastiera.
		 *
		 * Il pulsante "Mostra di più" resta al suo posto: rimuoverlo faceva
		 * sparire il comando appena si scorreva su un set clonato. È invece
		 * escluso dalla navigazione con tabindex negativo, perché un
		 * elemento focalizzabile dentro un aria-hidden è una trappola.
		 *
		 * @param {HTMLElement} node Clone della card.
		 */
		function prepareClone( node, setName ) {
			node.classList.add( 'is-clone' );
			node.setAttribute( 'aria-hidden', 'true' );

			// Il lightbox di Elementor raggruppa le immagini per valore di
			// slideshow: con i cloni nello stesso gruppo ogni foto comparirebbe
			// tre volte. Ogni set di cloni diventa un gruppo a sé, che contiene
			// la galleria completa nell'ordine giusto.
			var slideshow = node.getAttribute( 'data-elementor-lightbox-slideshow' );

			if ( slideshow ) {
				node.setAttribute( 'data-elementor-lightbox-slideshow', slideshow + '-' + setName );
			}

			if ( 'A' === node.tagName ) {
				node.setAttribute( 'tabindex', '-1' );
			}

			// Tutto ciò che è focalizzabile nel clone — pulsante "Mostra di
			// più", link del pulsante — esce dalla navigazione da tastiera.
			var focusables = node.querySelectorAll( 'a, button' );

			Array.prototype.forEach.call( focusables, function ( el ) {
				el.setAttribute( 'tabindex', '-1' );
			} );
		}

		/**
		 * Misura la larghezza di un set di card.
		 *
		 * @param {number} count Numero di card originali.
		 * @return {number}
		 */
		function measureSet( count ) {
			var first = track.children[ 0 ];
			var firstOriginal = track.children[ count ];

			if ( ! first || ! firstOriginal ) {
				return 0;
			}

			return firstOriginal.offsetLeft - first.offsetLeft;
		}

		// Ogni card riceve un indice prima di essere clonata: serve a tenere
		// allineato lo stato di espansione tra l'originale e i suoi cloni.
		Array.prototype.forEach.call( track.children, function ( card, index ) {
			card.setAttribute( 'data-lu3g-index', String( index ) );
		} );

		var originalCount = track.children.length;

		/**
		 * Duplica il set di card prima e dopo l'originale.
		 *
		 * @return {boolean} True se il loop è stato predisposto.
		 */
		function buildLoop() {
			var originals = Array.prototype.slice.call( track.children );

			if ( originals.length < 2 ) {
				return false;
			}

			var before = document.createDocumentFragment();
			var after = document.createDocumentFragment();

			originals.forEach( function ( node ) {
				var head = node.cloneNode( true );
				var tail = node.cloneNode( true );

				prepareClone( head, 'head' );
				prepareClone( tail, 'tail' );

				before.appendChild( head );
				after.appendChild( tail );
			} );

			track.appendChild( after );
			track.insertBefore( before, track.firstChild );

			root.classList.add( 'lu3g-carousel--looping' );

			setWidth = measureSet( originals.length );
			viewport.scrollLeft = setWidth;

			return true;
		}

		/**
		 * Riporta la posizione dentro la fascia centrale quando ne esce.
		 *
		 * Su mobile lo slancio inerziale prosegue dopo il rilascio del dito:
		 * scrivere scrollLeft mentre è in corso produce i salti e lo
		 * sfarfallio del testo, perché il browser continua l'inerzia dalla
		 * posizione vecchia. Il riallineamento viene quindi rimandato a
		 * scorrimento fermo, e forzato subito solo se si sta per arrivare
		 * alla fine reale del contenuto, dove non c'è più nulla da mostrare.
		 *
		 * @param {boolean} [immediate] Salta l'attesa.
		 */
		function normalize( immediate ) {
			if ( ! loop || ! setWidth ) {
				return;
			}

			var pos = viewport.scrollLeft;
			var outside = pos < setWidth * 0.5 || pos > setWidth * 1.5;

			if ( ! outside ) {
				return;
			}

			// Margine di sicurezza: oltre questa soglia il contenuto sta per
			// finire e il salto va fatto comunque, anche in pieno slancio.
			var critical = pos < setWidth * 0.15 || pos > setWidth * 1.85;

			if ( ! immediate && ! critical && scrolling ) {
				return;
			}

			viewport.scrollLeft = pos < setWidth ? pos + setWidth : pos - setWidth;
		}

		if ( loop ) {
			loop = buildLoop();
		}

		/* -----------------------------------------------------------------
		 * Frecce
		 * -------------------------------------------------------------- */

		/**
		 * Aggiorna lo stato delle frecce in base alla posizione.
		 */
		function sync() {
			// is-static segnala che tutte le card sono già visibili: il CSS la
			// usa per nascondere le frecce, se richiesto nel pannello.
			root.classList.toggle( 'is-static', track.scrollWidth - viewport.clientWidth <= 2 );

			// In loop non si arriva mai a un capo: le frecce restano attive
			// e la direzione non si inverte.
			if ( loop ) {
				arrows.forEach( function ( arrow ) {
					arrow.classList.remove( 'is-disabled' );

					if ( singleMode ) {
						arrow.classList.add( 'is-flipped' );
					}
				} );
				return;
			}

			var maxScroll = track.scrollWidth - viewport.clientWidth;
			var scrollable = maxScroll > 2;
			var atStart = viewport.scrollLeft <= 2;
			var atEnd = viewport.scrollLeft >= maxScroll - 2;

			if ( singleMode ) {
				if ( atEnd ) {
					dir = -1;
				}
				if ( atStart ) {
					dir = 1;
				}
			}

			arrows.forEach( function ( arrow ) {
				var arrowDir = arrow.getAttribute( 'data-lu3g-dir' );

				if ( singleMode ) {
					arrow.classList.toggle( 'is-flipped', dir === 1 );
					arrow.classList.toggle( 'is-disabled', ! scrollable );
					return;
				}

				var blocked = ( 'prev' === arrowDir ) ? atStart : atEnd;
				arrow.classList.toggle( 'is-disabled', ! scrollable || blocked );
			} );
		}

		/**
		 * Sposta il carosello di un passo nella direzione indicata.
		 *
		 * @param {number} direction 1 avanti, -1 indietro.
		 */
		function move( direction ) {
			var target = viewport.scrollLeft + getStep() * direction;

			if ( ! loop ) {
				var maxScroll = track.scrollWidth - viewport.clientWidth;
				target = Math.max( 0, Math.min( target, maxScroll ) );
			}

			scrollTo( target, function () {
				normalize( true );
			} );
		}

		/* -----------------------------------------------------------------
		 * Autoplay
		 * -------------------------------------------------------------- */

		/**
		 * Avvia il ciclo automatico.
		 */
		function startAutoplay() {
			// root staccato: l'istanza è stata sostituita da una ricostruzione.
			if ( ! autoplay || timer || ! root.isConnected ) {
				return;
			}

			timer = window.setInterval( function () {
				if ( paused || document.hidden ) {
					return;
				}

				// Con una card aperta lo scorrimento automatico porterebbe
				// via il testo che l'utente sta leggendo.
				if ( root.querySelector( '.lu3g-carousel__card.is-expanded' ) ) {
					return;
				}

				if ( loop ) {
					move( 1 );
					return;
				}

				// Senza loop il carosello rimbalza tra i due capi.
				var maxScroll = track.scrollWidth - viewport.clientWidth;

				if ( maxScroll <= 2 ) {
					return;
				}

				if ( viewport.scrollLeft >= maxScroll - 2 ) {
					dir = -1;
				} else if ( viewport.scrollLeft <= 2 ) {
					dir = 1;
				}

				move( dir );
			}, delay );
		}

		/**
		 * Ferma il ciclo automatico.
		 */
		function stopAutoplay() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		/**
		 * Riavvia il conteggio dopo un'interazione, così lo scorrimento
		 * automatico non arriva subito dopo un click dell'utente.
		 */
		function restartAutoplay() {
			if ( ! autoplay ) {
				return;
			}

			stopAutoplay();
			startAutoplay();
		}

		arrows.forEach( function ( arrow ) {
			arrow.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var direction = singleMode
					? dir
					: ( 'next' === arrow.getAttribute( 'data-lu3g-dir' ) ? 1 : -1 );

				move( direction );
				restartAutoplay();
			} );
		} );

		if ( autoplay ) {
			if ( pauseOnHover ) {
				root.addEventListener( 'mouseenter', function () {
					paused = true;
				} );

				root.addEventListener( 'mouseleave', function () {
					paused = false;
				} );
			}

			// La pausa sul focus vale sempre: chi naviga da tastiera non deve
			// vedersi spostare il contenuto sotto le mani.
			root.addEventListener( 'focusin', function () {
				paused = true;
			} );

			root.addEventListener( 'focusout', function () {
				paused = false;
			} );

			// Lo swipe manuale interrompe il movimento in corso.
			viewport.addEventListener( 'pointerdown', function () {
				cancelAnimation();
				restartAutoplay();
			} );

			document.addEventListener( 'visibilitychange', function () {
				if ( ! document.hidden ) {
					restartAutoplay();
				}
			} );

			startAutoplay();
		}

		/* -----------------------------------------------------------------
		 * Indicatori di posizione
		 *
		 * Un indicatore per ogni posizione di scorrimento raggiungibile, che
		 * non è per forza una per card: senza loop, le ultime card non
		 * diventano mai la prima visibile, perché il carosello si ferma
		 * prima. Il numero dipende quindi dalla larghezza, e si ricalcola al
		 * ridimensionamento.
		 * -------------------------------------------------------------- */

		var indicatorBox = root.querySelector( '[data-lu3g-indicators]' );
		var indicatorType = indicatorBox ? indicatorBox.getAttribute( 'data-lu3g-indicators' ) : '';
		var stops = [];
		var dots = [];
		var activeIndex = -1;
		var indicatorFrame = null;

		/**
		 * Card originali, esclusi i cloni del loop.
		 *
		 * @return {HTMLElement[]}
		 */
		function originalCards() {
			return Array.prototype.filter.call( track.children, function ( el ) {
				return ! el.classList.contains( 'is-clone' );
			} );
		}

		/**
		 * Posizioni di scorrimento raggiungibili.
		 *
		 * @return {number[]}
		 */
		function computeStops() {
			var cards = originalCards();

			if ( ! cards.length ) {
				return [];
			}

			var base = cards[ 0 ].offsetLeft;

			// Con il loop ogni card è raggiungibile; le posizioni sono quelle
			// del set centrale, dove lo scorrimento viene sempre riportato.
			if ( loop && setWidth ) {
				return cards.map( function ( card ) {
					return setWidth + ( card.offsetLeft - base );
				} );
			}

			var max = track.scrollWidth - viewport.clientWidth;

			if ( max <= 2 ) {
				return [];
			}

			var out = [];

			cards.forEach( function ( card ) {
				var pos = Math.min( card.offsetLeft - base, max );

				if ( ! out.length || pos - out[ out.length - 1 ] > 2 ) {
					out.push( pos );
				}
			} );

			return out;
		}

		/**
		 * Posizione corrente, riportata nel set centrale se c'è il loop.
		 *
		 * @return {number}
		 */
		function currentPosition() {
			var pos = viewport.scrollLeft;

			if ( loop && setWidth ) {
				pos = ( ( ( pos - setWidth ) % setWidth ) + setWidth ) % setWidth + setWidth;
			}

			return pos;
		}

		/**
		 * Indice della posizione più vicina a quella corrente.
		 *
		 * @param {number} pos Posizione corrente.
		 * @return {number}
		 */
		function nearestStop( pos ) {
			var best = 0;
			var bestDistance = Infinity;

			stops.forEach( function ( stop, i ) {
				var distance = Math.abs( stop - pos );

				// Nel loop, a fine set la prima card è più vicina "girando".
				if ( loop && setWidth ) {
					distance = Math.min( distance, Math.abs( stop + setWidth - pos ) );
				}

				if ( distance < bestDistance ) {
					bestDistance = distance;
					best = i;
				}
			} );

			return best;
		}

		/**
		 * Scorre fino alla posizione di un indicatore.
		 *
		 * @param {number} i Indice della posizione.
		 */
		function goTo( i ) {
			if ( ! stops.length ) {
				return;
			}

			var target = stops[ i ];

			// Nel loop si resta nel set in cui ci si trova, così lo scorrimento
			// non attraversa mezzo carosello per arrivare alla card.
			if ( loop && setWidth ) {
				target += viewport.scrollLeft - currentPosition();
			}

			scrollTo( target, function () {
				normalize( true );
			} );

			restartAutoplay();
		}

		/**
		 * Aggiorna l'indicatore attivo.
		 */
		function updateIndicators() {
			if ( ! indicatorBox || indicatorBox.hidden || ! stops.length ) {
				return;
			}

			var i = nearestStop( currentPosition() );

			if ( i === activeIndex ) {
				return;
			}

			activeIndex = i;

			if ( 'dots' === indicatorType ) {
				dots.forEach( function ( dot, k ) {
					dot.classList.toggle( 'is-active', k === i );

					if ( k === i ) {
						dot.setAttribute( 'aria-current', 'true' );
					} else {
						dot.removeAttribute( 'aria-current' );
					}
				} );
			} else if ( 'progress' === indicatorType ) {
				var bar = indicatorBox.querySelector( '.lu3g-carousel__progress-bar' );

				if ( bar ) {
					bar.style.width = ( ( i + 1 ) / stops.length * 100 ) + '%';
				}
			} else if ( 'fraction' === indicatorType ) {
				var fraction = indicatorBox.querySelector( '.lu3g-carousel__fraction' );

				if ( fraction ) {
					fraction.textContent = ( i + 1 ) + ' / ' + stops.length;
				}
			}
		}

		/**
		 * Ricalcola le posizioni e, per i puntini, ricrea i pulsanti se il
		 * loro numero è cambiato.
		 */
		function buildIndicators() {
			if ( ! indicatorBox ) {
				return;
			}

			stops = computeStops();

			// Con una sola posizione non c'è nulla da indicare.
			if ( stops.length < 2 ) {
				indicatorBox.hidden = true;
				return;
			}

			indicatorBox.hidden = false;

			if ( 'dots' === indicatorType && dots.length !== stops.length ) {
				var label = indicatorBox.getAttribute( 'data-lu3g-dot-label' ) || '%1$s / %2$s';

				indicatorBox.innerHTML = '';

				dots = stops.map( function ( stop, i ) {
					var dot = document.createElement( 'button' );

					dot.type = 'button';
					dot.className = 'lu3g-carousel__dot';
					dot.setAttribute(
						'aria-label',
						label.replace( '%1$s', String( i + 1 ) ).replace( '%2$s', String( stops.length ) )
					);
					dot.addEventListener( 'click', function () {
						goTo( i );
					} );

					indicatorBox.appendChild( dot );

					return dot;
				} );
			}

			activeIndex = -1;
			updateIndicators();
		}

		/**
		 * Aggiorna al massimo una volta per frame durante lo scorrimento.
		 */
		function scheduleIndicators() {
			if ( ! indicatorBox || indicatorFrame ) {
				return;
			}

			indicatorFrame = window.requestAnimationFrame( function () {
				indicatorFrame = null;
				updateIndicators();
			} );
		}

		/* -----------------------------------------------------------------
		 * Modalità centrata
		 *
		 * Segna con is-center la card il cui centro è più vicino a quello del
		 * viewport. Si aggiorna durante lo scorrimento, al massimo una volta
		 * per frame, così scala e opacità seguono il dito o l'animazione
		 * delle frecce senza aspettare che lo scorrimento si fermi. I cloni
		 * del loop sono inclusi: quando la card centrale è un clone, è quella
		 * a dover apparire attiva.
		 * -------------------------------------------------------------- */

		var centerMode = root.classList.contains( 'lu3g-carousel--center' );
		var centerFrame = null;
		var centerCard = null;

		function updateCenter() {
			if ( ! centerMode ) {
				return;
			}

			var box = viewport.getBoundingClientRect();
			var middle = box.left + box.width / 2;
			var best = null;
			var bestDistance = Infinity;

			Array.prototype.forEach.call( track.children, function ( card ) {
				var r = card.getBoundingClientRect();
				var distance = Math.abs( r.left + r.width / 2 - middle );

				if ( distance < bestDistance ) {
					bestDistance = distance;
					best = card;
				}
			} );

			if ( best === centerCard ) {
				return;
			}

			if ( centerCard ) {
				centerCard.classList.remove( 'is-center' );
			}

			if ( best ) {
				best.classList.add( 'is-center' );
			}

			centerCard = best;
		}

		function scheduleCenter() {
			if ( ! centerMode || centerFrame ) {
				return;
			}

			centerFrame = window.requestAnimationFrame( function () {
				centerFrame = null;
				updateCenter();
			} );
		}

		/* -----------------------------------------------------------------
		 * Eventi
		 * -------------------------------------------------------------- */

		/**
		 * Segna la fine dello scorrimento e riallinea il loop.
		 *
		 * Si usa scrollend dove esiste; altrove un timer che riparte a ogni
		 * evento di scroll e scade quando questi smettono di arrivare.
		 */
		function onSettled() {
			scrolling = false;
			normalize( true );
			sync();
		}

		var hasScrollEnd = 'onscrollend' in window;

		if ( hasScrollEnd ) {
			viewport.addEventListener( 'scrollend', onSettled );
		}

		viewport.addEventListener( 'scroll', function () {
			// Durante l'animazione programmata il riallineamento avviene a
			// fine corsa, non a ogni frame.
			if ( ! animation ) {
				scrolling = true;
				normalize();

				if ( ! hasScrollEnd ) {
					window.clearTimeout( settleTimer );
					settleTimer = window.setTimeout( onSettled, 120 );
				}
			}

			sync();
			scheduleIndicators();
			scheduleCenter();
		}, { passive: true } );

		/**
		 * Sostituisce il carosello con una copia intatta e la inizializza.
		 */
		function rebuild() {
			stopAutoplay();
			cancelAnimation();

			if ( ! root.parentNode ) {
				return;
			}

			var fresh = pristine.cloneNode( true );
			root.parentNode.replaceChild( fresh, root );
			initCarousel( fresh );
		}

		window.addEventListener( 'resize', function () {
			// Dopo una ricostruzione questa istanza è staccata dalla pagina.
			if ( ! root.isConnected ) {
				return;
			}

			if ( lock && isLocked( lock ) !== locked ) {
				rebuild();
				return;
			}

			if ( loop ) {
				setWidth = measureSet( originalCount );
			}

			sync();
			buildIndicators();
			updateCenter();
		} );

		// Le immagini o i font che caricano dopo possono cambiare le misure.
		window.addEventListener( 'load', function () {
			if ( ! root.isConnected ) {
				return;
			}

			if ( loop ) {
				setWidth = measureSet( originalCount );
			}

			sync();
			buildIndicators();
			updateCenter();
		} );

		sync();
		buildIndicators();
		updateCenter();

		setupTruncation( root );
		setupAnimation( root, reduced );
	}

	/**
	 * Gestisce il troncamento del testo.
	 *
	 * Il pulsante è nascosto nel markup e viene mostrato solo dove il testo
	 * eccede davvero il numero di righe: su una card corta un "Mostra di più"
	 * che non fa nulla è peggio che assente.
	 *
	 * @param {HTMLElement} root Elemento del carosello.
	 */
	function setupTruncation( root ) {
		if ( ! root.classList.contains( 'lu3g-carousel--clamp' ) ) {
			return;
		}

		var toggles = Array.prototype.slice.call( root.querySelectorAll( '[data-lu3g-toggle]' ) );

		if ( ! toggles.length ) {
			return;
		}

		/**
		 * Mostra o nasconde ogni pulsante confrontando due altezze.
		 *
		 * Contare le righe dividendo per il line-height è fragile: con font
		 * personalizzati e valori frazionari l'arrotondamento sbaglia di una
		 * riga e il pulsante compare su testi che entrano tutti. Qui si
		 * misura la stessa cosa due volte, con e senza clamp: se i due
		 * valori coincidono il testo non è troncato.
		 */
		function refresh() {
			toggles.forEach( function ( toggle ) {
				var card = toggle.closest( '.lu3g-carousel__card' );
				// L'elemento troncato è quello marcato dal PHP: può essere il
				// titolo o la descrizione, a seconda dell'impostazione.
				var text = card ? card.querySelector( '.lu3g-carousel__clamp' ) : null;

				if ( ! text ) {
					return;
				}

				// Su una card già aperta il pulsante resta comunque visibile.
				if ( card.classList.contains( 'is-expanded' ) ) {
					toggle.hidden = false;
					return;
				}

				var clampedHeight = text.clientHeight;

				text.classList.add( 'is-measuring' );
				var fullHeight = text.scrollHeight;
				text.classList.remove( 'is-measuring' );

				var styles = window.getComputedStyle( text );
				var lineHeight = parseFloat( styles.lineHeight );

				if ( isNaN( lineHeight ) || lineHeight <= 0 ) {
					lineHeight = parseFloat( styles.fontSize ) * 1.2;
				}

				// Mezza riga di tolleranza assorbe gli arrotondamenti del
				// rendering senza lasciar passare una riga intera.
				var tolerance = Math.max( 2, lineHeight * 0.5 );

				toggle.hidden = fullHeight <= clampedHeight + tolerance;
			} );
		}

		/**
		 * Applica lo stato di espansione a una card e al suo pulsante.
		 *
		 * @param {HTMLElement} card     Card da aggiornare.
		 * @param {boolean}     expanded Stato da applicare.
		 */
		function applyState( card, expanded ) {
			card.classList.toggle( 'is-expanded', expanded );

			var toggle = card.querySelector( '[data-lu3g-toggle]' );

			if ( ! toggle ) {
				return;
			}

			toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
			toggle.textContent = expanded
				? toggle.getAttribute( 'data-label-collapse' )
				: toggle.getAttribute( 'data-label-expand' );
		}

		toggles.forEach( function ( toggle ) {
			toggle.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				event.stopPropagation();

				var card = toggle.closest( '.lu3g-carousel__card' );

				if ( ! card ) {
					return;
				}

				var expanded = ! card.classList.contains( 'is-expanded' );
				var index = card.getAttribute( 'data-lu3g-index' );

				// Con lo scorrimento infinito la stessa card esiste in tre
				// copie: se non si allineano, scorrendo si rivede la versione
				// troncata di una card che l'utente ha appena aperto.
				if ( null !== index ) {
					var twins = root.querySelectorAll(
						'.lu3g-carousel__card[data-lu3g-index="' + index + '"]'
					);

					Array.prototype.forEach.call( twins, function ( twin ) {
						applyState( twin, expanded );
					} );

					return;
				}

				applyState( card, expanded );
			} );
		} );

		// La prima misura aspetta il frame successivo: al momento dell'init
		// il layout può non essere ancora risolto e le altezze sarebbero
		// entrambe zero, con il pulsante nascosto per sbaglio.
		if ( window.requestAnimationFrame ) {
			window.requestAnimationFrame( refresh );
		} else {
			refresh();
		}

		// Il ricalcolo dopo il load serve perché i font personalizzati
		// cambiano l'altezza del testo dopo il primo render.
		window.addEventListener( 'load', function () {
			refresh();
		} );

		window.addEventListener( 'resize', function () {
			refresh();
		} );

		if ( 'fonts' in document && document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				refresh();
			} );
		}

		// Se il carosello parte nascosto (tab, accordion, sezione fuori
		// schermo) le altezze valgono zero: si rimisura quando compare.
		if ( 'IntersectionObserver' in window ) {
			var visibility = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						refresh();
					}
				} );
			} );

			visibility.observe( root );
		}
	}

	/**
	 * Arma l'animazione d'ingresso e la fa partire quando il carosello
	 * entra nel viewport.
	 *
	 * La classe .is-armed viene aggiunta solo qui: se il JS non gira, le card
	 * restano visibili invece di sparire.
	 *
	 * @param {HTMLElement} root    Elemento del carosello.
	 * @param {boolean}     reduced True se l'utente ha chiesto meno animazioni.
	 */
	function setupAnimation( root, reduced ) {
		var enabled = root.getAttribute( 'data-lu3g-animate' ) === '1';

		if ( ! enabled || reduced || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var stagger = parseInt( root.getAttribute( 'data-lu3g-stagger' ), 10 );

		if ( isNaN( stagger ) ) {
			stagger = 80;
		}

		root.style.setProperty( '--lu3g-stagger', stagger + 'ms' );
		root.classList.add( 'is-armed' );

		var repeat = root.getAttribute( 'data-lu3g-repeat' ) === '1';

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					root.classList.add( 'is-in-view' );

					if ( ! repeat ) {
						observer.unobserve( entry.target );
					}
				} else if ( repeat ) {
					root.classList.remove( 'is-in-view' );
				}
			} );
		}, { threshold: 0.25 } );

		observer.observe( root );
	}

	/**
	 * Legge le regole di blocco dello scorrimento, se presenti.
	 *
	 * @param {HTMLElement} root Elemento del carosello.
	 * @return {?Object} { base, rules: [ { q, lock } ] } o null.
	 */
	function readLock( root ) {
		var raw = root.getAttribute( 'data-lu3g-lock' );

		if ( ! raw ) {
			return null;
		}

		try {
			var data = JSON.parse( raw );
			return data && Array.isArray( data.rules ) ? data : null;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Dice se, alla larghezza attuale, lo scorrimento è bloccato.
	 *
	 * Parte dal valore desktop e applica le regole in ordine: le max-width
	 * arrivano dalla più larga alla più stretta, quindi vince quella del
	 * dispositivo più piccolo che corrisponde.
	 *
	 * @param {Object} lock Regole lette da readLock().
	 * @return {boolean}
	 */
	function isLocked( lock ) {
		var value = !! lock.base;

		lock.rules.forEach( function ( rule ) {
			if ( rule && rule.q && window.matchMedia( rule.q ).matches ) {
				value = !! rule.lock;
			}
		} );

		return value;
	}

	/**
	 * Inizializza tutti i caroselli presenti in un contenitore.
	 *
	 * @param {ParentNode} scope Contenitore da ispezionare.
	 */
	function initAll( scope ) {
		var container = scope || document;
		var nodes = container.querySelectorAll( '[data-lu3g-carousel]' );

		Array.prototype.forEach.call( nodes, initCarousel );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	// Editor e frontend di Elementor: i widget vengono montati dopo il load,
	// e nell'editor rimontati a ogni modifica dei controlli.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/lu3g-repeater-carousel.default',
			function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ] : $scope;
				initAll( el );
			}
		);
	} );
}() );
