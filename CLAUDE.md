# LU3G Repeater Carousel — contesto per Claude

Questo file serve a riprendere il lavoro in una nuova sessione. Leggilo prima di toccare il codice.

## Chi e cosa

- Autore: Vincenzo, LU3G Agenzia Web (GitHub `violix96`). Si lavora in **italiano**, risposte brevi e pratiche.
- Plugin WordPress: widget Elementor **"Carosello repeater"** (nome `lu3g-repeater-carousel`, categoria LU3G). Carosello a scroll orizzontale nativo con snap, alimentato da un repeater JetEngine, da card scritte a mano o da una galleria di immagini.
- Repository: `violix96/lu3g-repeater-carousel` (pubblico, branch `main`). È la fonte di verità.
- Versione attuale: **1.13.0**.

## File

| File | Contenuto |
|---|---|
| `lu3g-repeater-carousel.php` | Bootstrap, `Version:`, costante `LU3G_CAROUSEL_VERSION`, updater GitHub (prima dei controlli sulle dipendenze), registrazione widget/asset |
| `includes/class-widget-repeater-carousel.php` | Il widget (~5000 righe): controlli, lettura dati, render |
| `assets/carousel.css` | Stili (variabili `--lu3g-*`) |
| `assets/carousel.js` | Scorrimento, loop, autoplay, indicatori, mostra di più, animazioni, modalità centrata |
| `vendor/plugin-update-checker/` | Plugin Update Checker 5.7, non modificare |
| `.github/workflows/release.yml` | Crea `lu3g-repeater-carousel.zip` e lo allega alla Release |
| `CHANGELOG.md`, `readme.txt` | Novità per versione, `Stable tag:` |
| `DOCUMENTAZIONE.md` | Guida d'uso del widget |
| `AGGIORNAMENTI.md` | Guida aggiornamenti automatici (riusabile per gli altri plugin) |

## Struttura del widget

**Contenuto**: Card (sorgente `source_type`: `dynamic` repeater JetEngine / `manual` / `gallery`), Opzioni del contenuto, Layout, Scorrimento, Frecce, Indicatori di posizione.
**Stile**: Card, Immagini, Icona, Etichetta (kicker), Titolo, Stile testo 1 (ID `description_*`), Stile testo 2/3 (prefissi `extra_1`/`extra_2` via `lu3g_register_block_style`), Mostra di più, Pulsante, Frecce, Indicatori, Animazioni.

- Blocchi di testo aggiuntivi: catena di switcher `card_add_N` / `card_extra_N` / `card_extra_N_style`, massimo `LU3G_MAX_EXTRA_BLOCKS = 4`. Vincenzo preferisce **switcher**, non pulsanti "aggiungi" (provato e scartato in 1.6/1.7).
- Le tre sorgenti producono item con le stesse chiavi: `class, kicker, content, description, desc_style, blocks, icon, url, new_tab, rel, button_text, button_url, button_new_tab, button_rel` (+ `image, image_link, caption` per la galleria). Il render è unico.
- Ogni funzionalità nuova deve funzionare **per tutte le sorgenti** (parità dinamica/manuale), salvo quando non ha senso.

## Regole da rispettare

1. **Tutti i metodi helper del widget hanno prefisso `lu3g_`** (`lu3g_get_cards`, `lu3g_get_dynamic_cards`, `lu3g_read_repeater_rows`, `lu3g_dynamic_diagnosis`, `lu3g_extract_text_link`, `lu3g_render_nav`, `lu3g_indicators_markup`, …). Un metodo `get_items()` ha già causato un fatal error collidendo con `Elementor\Base_Object`.
2. Non rinominare mai gli ID dei controlli esistenti: i widget già salvati sui siti perderebbero le impostazioni. Per le nuove opzioni, default che **non cambiano** l'aspetto dei widget esistenti.
3. Testo WYSIWYG: `wp_kses_post`, con opzione "Solo testo" (`strip_tags`). Mai `esc_html` su HTML.
4. Il tema può stilizzare `button`: reset con specificità alta nel CSS.
5. Aumentando la versione: `Version:` + `LU3G_CAROUSEL_VERSION` + `Stable tag:` in `readme.txt` + voce in cima a `CHANGELOG.md`.
6. Il token GitHub non va mai nel codice: solo `define( 'LU3G_GITHUB_TOKEN', ... )` in `wp-config.php` (il repo ora è pubblico, non serve).

## Bug già risolti (non reintrodurli)

- Animazione d'ingresso con `fill-mode: both` bloccava il transform dell'hover → `backwards`.
- Bordo/ombra tagliati dall'overflow del viewport → `padding-block`/`margin-block` con `--lu3g-bleed` (56px in modalità centrata).
- Frecce "sotto" facevano collassare il carosello a altezza 0 → stage/viewport `flex: 0 0 auto` in colonna.
- Frecce non cliccabili sotto il glow dell'hover: **non** usare `pointer-events: none` sul viewport (rompe lo swipe su iPhone, 1.9.1). Frecce e indicatori hanno `z-index: 3` sopra lo stage.
- Loop: i cloni mantengono pulsanti/toggle (`tabindex="-1"`), stato sincronizzato con `data-lu3g-index`; la normalizzazione della posizione avviene dopo `scrollend` (glitch su mobile).
- Modalità centrata: spaziatori `::before/::after` larghi `calc(var(--lu3g-peek)/2)` con margine negativo pari al gap; `::after` ha +1px di base e -gap-1px di margine, altrimenti l'ultima card (scalata) non arriva al centro. Le card laterali usano la proprietà `scale`, non `transform` (non collide con hover/animazioni).
- Sorgente dinamica "nessuna riga": quasi sempre un sotto-campo sbagliato (es. lasciato `testo` invece di `titolo_card_ruolo`). In editor compare un messaggio di diagnosi (`lu3g_dynamic_diagnosis`).

## Funzioni per versione (sintesi)

- 1.9: immagine di sfondo per card (cover/contain, posizione, ripetizione) + overlay colore/intensità; fix swipe iOS; diagnosi sorgente dinamica.
- 1.10.0: `dynamic_link_source` "Dal primo link nel testo" (il pulsante appare solo se nel testo c'è un `<a>`).
- 1.11.0: `hide_idle_arrows`, nasconde le frecce se non c'è nulla da scorrere (JS aggiunge `is-static`).
- 1.13.0: `button_display` (testo e icona / solo testo / solo icona; con solo icona ogni card con link riceve il pulsante, testo → aria-label, helper `lu3g_icon_only_buttons`); `%testo_link%` nel testo del pulsante dinamico.
- 1.12.0/1.12.1: modalità centrata (`center_mode`, `center_side_scale`, `center_side_opacity`, `center_main_scale` default 1.04, `center_shadow`); con 3 card visibili la seconda è subito al centro senza scorrere. JS: `updateCenter`/`scheduleCenter` assegnano `.is-center`.

## Come si testa

Nel container PHP si installa con `apt-get install php-cli`: usare `php -l` e script con stub di Elementor per provare i metodi privati via Reflection. Metodo usato finora:
- `node --check assets/carousel.js`
- controllo parentesi del PHP con uno script Python
- pagina HTML statica che replica il markup del widget + Playwright/Chromium (preinstallato, `executablePath: '/opt/pw-browsers/chromium'` se serve) per misurare posizioni, centratura, loop, clic sulle frecce, swipe con emulazione iPhone.

## Pubblicare una versione

1. Modifica, aumenta la versione (regola 5), commit e push su `main`. Commit firmati come `violix96 <vincy1621@gmail.com>`.
2. La **Release la crea Vincenzo** su GitHub (dalla sessione Claude la creazione di Release è rifiutata).
3. **Tag = versione esatta**, es. `1.12.1` oppure `v1.12.1` (v minuscola). Tag come `V_1.12.1` o `V_11.0` fanno fallire l'Action (niente zip allegato) e la libreria di aggiornamento non li legge correttamente.
4. Verificare in *Actions* che sia verde e che la Release abbia `lu3g-repeater-carousel.zip`.
5. Sui siti: *Plugin → Verifica aggiornamenti* per non aspettare le 12 ore.

## Idee non ancora fatte

Trascinamento col mouse su desktop, navigazione da tastiera, compatibilità WPML/Polylang, griglia su desktop e carosello su mobile, card da post/CPT (non solo repeater), immagine di sfondo per card dalla sorgente dinamica, pulsante pausa per l'autoplay.
