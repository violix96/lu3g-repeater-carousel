# LU3G Repeater Carousel

Widget Elementor che trasforma un campo repeater JetEngine in un carosello a
scorrimento orizzontale, con aggancio allo scroll, freccia di navigazione e
animazione d'ingresso.

Nasce dal CSS scritto a mano per il template del CPT Prodotti: stesse scelte
tecniche, ma i valori diventano controlli invece che variabili da modificare
nel foglio di stile.

## Requisiti

- WordPress 6.0+
- PHP 7.4+
- Elementor 3.5+
- JetEngine (per i campi repeater)

## Installazione

Copia la cartella `lu3g-repeater-carousel` in `wp-content/plugins/` e attiva
il plugin. Il widget compare nel pannello di Elementor sotto la categoria
**LU3G**, col nome **Carosello repeater**.

## Configurazione minima

Il widget ha due sorgenti, scelte in **Sorgente → Da dove arrivano le card**.

**Statico — inserito in Elementor** — non richiede JetEngine. Aggiungi le card dal
repeater di Elementor. Ogni card ha due tab: **Contenuto** — etichetta,
titolo, un blocco di testo, e sotto l'interruttore *Aggiungi un altro blocco di
testo* per aggiungerne altri solo quando servono, poi icona, testo del pulsante
e link — e **Stile**, dove attivando *Colori
propri* la card può avere sfondo, bordo, testi e pulsante diversi dalle
altre. Con il testo del pulsante
compilato il link va sul pulsante; senza, rende cliccabile l'intera card. Il contenuto resta
nella pagina, quindi va bene per sezioni una tantum e non per un template che
deve cambiare da un post all'altro.

**Dinamico — repeater JetEngine** — per i template di CPT:

1. Trascina il widget nel template.
2. Scegli il campo repeater dall'elenco. Se non compare, lascia "inserimento
   manuale" e scrivi il nome del meta field.
3. In **Sottocampo da mostrare**, indica il sottocampo del titolo.
4. Facoltativi, con gli stessi elementi delle card statiche: sottocampo
   etichetta, **Blocchi di testo** (un repeater dove ogni riga collega un
   sottocampo a uno dei tre stili di testo), sottocampo icona, sottocampo link
   e testo del pulsante — fisso, o con `%nome_sottocampo%` per leggerlo dalla
   riga.

**Immagini** — una card per ogni foto scelta con il controllo galleria di
Elementor, che accetta anche gallerie dinamiche. Proporzione delle card, punto
di ritaglio, zoom e velatura in hover, lightbox di Elementor al click.

## Il pannello

**Tab Contenuto**

- **Card** — sorgente (card scritte in Elementor o repeater JetEngine) e
  contenuto. Per le card manuali ogni riga ha le tab Contenuto e Stile.
- **Opzioni del contenuto** — tag del titolo, icona del pulsante, *Solo
  testo*, troncamento con le etichette di *Mostra di più*.
- **Layout** — larghezza delle card, card visibili, anteprima della
  successiva, spaziatura, altezza, distribuzione del contenuto.
- **Scorrimento** — velocità, aggancio, loop infinito, autoplay.
- **Frecce** — attivazione, numero di frecce (una o due), posizione (a sinistra, a destra,
  ai due lati, sopra, sotto, sovrapposte alle card), allineamento, distanza
  dal bordo, distanza tra le frecce, icone, visibilità su mobile.
- **Indicatori di posizione** — puntini, barra di avanzamento o numeri,
  sotto, sopra, sovrapposti alle card o tra le due frecce.

**Tab Stile**, nell'ordine degli elementi della card dall'alto in basso:
Card, Icona, Etichetta, Titolo, Stile testo 1, 2 e 3, Mostra di più,
Pulsante, Frecce, Indicatori di posizione, Animazioni. Dove c'è uno stato hover, i colori sono divisi
nelle tab Normale / Hover.

## Note tecniche

**Card immagine.** La foto è in posizione assoluta sull'intera card, padding
compreso, con `object-fit`: il padding delle card di testo non crea cornici.
La proporzione usa `selectors_dictionary`: ogni opzione diventa una
dichiarazione completa (`aspect-ratio` più l'annullamento dell'altezza), che
col selettore del wrapper batte le regole dell'altezza fissa. Nel loop, i cloni
ricevono un gruppo lightbox proprio per set, così il lightbox di Elementor non
mostra ogni foto tre volte.

**Indicatori di posizione.** Ce n'è uno per ogni posizione di scorrimento
raggiungibile, non per ogni card: senza loop, con 6 card e 3 visibili le
posizioni sono 4, perché le ultime card non diventano mai la prima visibile.
Il numero dipende dalla larghezza, quindi i puntini li crea il JS e si
ricalcolano al ridimensionamento. Con il loop ogni card è una posizione. Se
non c'è nulla da scorrere l'indicatore non compare; senza JS non compare
affatto, invece di restare vuoto.

**Lo stage.** Il viewport è racchiuso in `.lu3g-carousel__stage`, che è il
riferimento per tutto ciò che si sovrappone alle card: frecce e indicatori
sovrapposti si centrano sull'area delle card, non su card più indicatori.

**ID dei controlli.** Sono le chiavi con cui Elementor salva i valori nelle
pagine: non vanno mai rinominati. La riorganizzazione della 1.5.0 ha spostato
i controlli tra sezioni e cambiato le etichette, ma nessun ID — i caroselli
già pubblicati mantengono tutte le impostazioni.

**Frecce sopra e sotto.** In colonna il viewport usa `flex: 0 0 auto`. Con
`flex: 1 1 0` e l'overflow orizzontale attivo, il suo `min-height` automatico
vale zero e il carosello collassava ad altezza nulla: era un bug della
posizione "Sotto" nelle versioni precedenti.

**Frecce sovrapposte.** Il contenitore copre il carosello con
`pointer-events: none`, così click e swipe arrivano alle card; solo le frecce
ricevono gli eventi. Con una sola freccia, questa va sul lato destro.

**Colori della singola card.** Usano `{{CURRENT_ITEM}}` di Elementor: ogni riga
del repeater riceve una classe unica, stampata sulla card, a cui si agganciano
i suoi colori. I selettori hanno un livello in più di quelli generali, hover
compreso, così la card personalizzata vince sempre; i campi lasciati vuoti
restano quelli generali. I cloni del loop copiano la classe e quindi i colori.

**Blocchi di testo.** Nelle card statiche Elementor non permette un repeater
dentro un repeater, quindi i blocchi aggiuntivi sono una catena di
interruttori: ogni blocco compare solo se è attivo l'interruttore del
precedente, e spegnerne uno toglie quel blocco e tutti i successivi, dal
pannello e dalla pagina. Il massimo è nella costante `LU3G_MAX_EXTRA_BLOCKS`
(4 oltre al primo). Nella sorgente dinamica invece i blocchi sono un vero
repeater, perché lì sta a livello di widget.

Lo stile non appartiene al blocco ma è scelto da un menu: nello Stile del
widget ci sono tre **stili di testo**, e ogni blocco ne usa uno. Così le sezioni
del pannello restano tre qualunque sia il numero di blocchi, e due blocchi con
lo stesso stile hanno lo stesso aspetto ovunque siano. I blocchi usano l'editor
visuale e ignorano *Solo testo*; i testi scritti quando il primo blocco era una
textarea passano per `wpautop` e non perdono gli a capo.

**Margine di sicurezza del viewport.** `overflow-x: auto` rende automaticamente
`auto` anche `overflow-y`, quindi il viewport taglia tutto ciò che sporge in
verticale. Senza margine, il bordo inferiore delle card coincideva con la linea
di taglio e spariva con gli arrotondamenti subpixel; sollevamento e ombra in
hover venivano troncati. Il viewport ha ora un padding verticale compensato da
un margine negativo della stessa misura (`--lu3g-bleed`), che si adatta
all'effetto hover scelto: il layout della pagina non cambia. La fascia è
invisibile ma occupa spazio: frecce e indicatori hanno uno z-index più alto per
non esserne coperti. Il viewport NON deve avere `pointer-events: none`: su iOS
Safari blocca lo swipe (bug della 1.8.1–1.9.0, corretto nella 1.9.1).

**Animazione d'ingresso e hover.** L'animazione usa `fill-mode: backwards` e
non `both`: con `both` l'ultimo fotogramma restava applicato a fine animazione
e il suo `transform: none` batteva il transform dell'hover, che quindi non
funzionava.

**Distribuzione del contenuto.** In modalità *Raggruppato* il blocco di
contenuto viene ancorato in alto, al centro o in basso nella card: con testi di
lunghezza diversa icone e titoli finiscono ad altezze diverse da una card
all'altra. In modalità *Distribuito* ogni livello della catena flex occupa
tutta l'altezza disponibile — il body si estende con `align-self: stretch`, il
contenuto diventa una colonna flessibile, e il pulsante viene spinto in fondo
con `margin-top: auto` — così icone, titoli e pulsanti si allineano tra tutte
le card. Con **Righe riservate** nel pannello Titolo anche le descrizioni
partono alla stessa altezza quando i titoli vanno su un numero di righe
diverso: il titolo prende comunque lo spazio di N righe (`min-height` in unità
`lh`, con ripiego in `em`).

**Titolo, descrizione e pulsante.** Il tag del titolo è solo semantica: un
reset toglie margini, dimensione e peso che il tema assegna agli heading, e
l'aspetto lo decide il controllo Tipografia. Il troncamento può lavorare sul
titolo o sulla descrizione; sulle card senza descrizione ripiega sul titolo.
La chiave interna del titolo resta `card_text` per non svuotare le card già
inserite nelle pagine esistenti.

**Icone SVG.** Un SVG scelto col selettore di Elementor o caricato come
sottocampo media viene inserito nel markup invece che dentro un tag `img`:
solo così eredita il colore impostato nel pannello, perché un'immagine non è
ricolorabile via CSS. Il file passa per `wp_kses` con un'allowlist di tag e
attributi SVG, che tiene fuori script, gestori di eventi e riferimenti
esterni. Gli altri formati restano immagini e non sono ricolorabili.

**Larghezza delle card.** Non è un valore fisso: è
`(100% - gap totali) / card visibili`. Cambiando il numero di card visibili
cambia la larghezza, e il layout resta proporzionale a qualunque larghezza di
container.

**Perché non c'è una libreria.** Lo scorrimento è nativo
(`overflow-x` + `scroll-snap`), quindi swipe, inerzia, tastiera e trackpad
funzionano senza codice. Lo scorrimento programmato delle frecce e
dell'autoplay è un'animazione su `requestAnimationFrame`: `scrollBy` con
`behavior: smooth` non va bene perché la durata la decide il browser e non
sarebbe regolabile.

**Come funziona il loop.** Il set di card viene duplicato prima e dopo
l'originale e la posizione parte dal set centrale. Quando lo scorrimento esce
dalla fascia centrale, la posizione viene spostata di un set intero senza
animazione: il salto è invisibile perché il contenuto è identico. I cloni sono
esclusi da lettori di schermo e tastiera, ma conservano il pulsante "Mostra di
più": rimuoverlo faceva sparire il comando appena si scorreva su un set
clonato. Lo stato di espansione viene tenuto allineato tra le tre copie della
stessa card. Con meno di due card il loop si disattiva da solo.

**Loop su mobile.** Lo slancio inerziale prosegue dopo il rilascio del dito, e
scrivere `scrollLeft` mentre è in corso produce salti e sfarfallio del testo,
perché il browser continua l'inerzia dalla posizione precedente. Il
riallineamento è quindi rimandato a scorrimento fermo — con l'evento
`scrollend` dove esiste, altrimenti con un timer di 120 ms — e forzato subito
solo vicino alla fine reale del contenuto.

**Autoplay e accessibilità.** L'autoplay non parte per chi ha attivato la
riduzione delle animazioni nelle impostazioni di sistema, si mette in pausa
quando il focus entra nel carosello, quando la scheda del browser non è in
primo piano, e — se richiesto — al passaggio del mouse. Un'interazione manuale
azzera il conteggio.

**Animazione senza JS.** La classe `is-armed` che nasconde le card viene
aggiunta dallo script. Se il JS non parte, le card restano visibili invece di
sparire.

**Editor Elementor.** Lo script si aggancia a
`frontend/element_ready/lu3g-repeater-carousel.default`, quindi il carosello
funziona anche nell'anteprima e si reinizializza a ogni modifica dei controlli
senza duplicare i listener.

**Lettura dei repeater JetEngine.** JetEngine non espone un'API stabile per
elencare i meta box, quindi `get_repeater_fields()` tenta due strade note e in
caso di fallimento ricade sul campo manuale. Se dopo un aggiornamento di
JetEngine l'elenco si svuota, è lì che va guardato.

## Struttura

```
lu3g-repeater-carousel/
├── lu3g-repeater-carousel.php          bootstrap, dipendenze, registrazione asset
├── includes/
│   └── class-widget-repeater-carousel.php   controlli e render
├── assets/
│   ├── carousel.css
│   └── carousel.js
└── README.md
```

Gli asset sono registrati, non accodati: il widget li dichiara in
`get_style_depends()` e `get_script_depends()`, così Elementor li carica solo
nelle pagine dove il widget è presente.

## Possibili sviluppi

- Sorgente alternativa: termini di tassonomia o post correlati, oltre al repeater.
- Icona o immagine dentro la card, dal sottocampo media di JetEngine.
- Puntini di paginazione come alternativa alle frecce.
- Barra di avanzamento dell'autoplay.
