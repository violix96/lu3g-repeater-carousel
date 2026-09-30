# LU3G Repeater Carousel

Documentazione del plugin — versione 1.11.0

Widget per Elementor che crea caroselli a scorrimento orizzontale: di card con testi, scritte direttamente in Elementor o lette da un campo repeater di JetEngine, oppure di sole immagini. Lo stesso widget funziona sia in una pagina qualsiasi sia in un template di CPT dove ogni post ha i suoi contenuti.

Sviluppato da LU3G Agenzia Web.

---

## Indice

1. [Requisiti](#requisiti)
2. [Installazione e aggiornamento](#installazione-e-aggiornamento)
3. [Concetti di base](#concetti-di-base)
4. [Guida rapida: card statiche](#guida-rapida-card-statiche)
5. [Guida rapida: card da repeater JetEngine](#guida-rapida-card-da-repeater-jetengine)
6. [Guida rapida: carosello di immagini](#guida-rapida-carosello-di-immagini)
7. [Il pannello, controllo per controllo](#il-pannello-controllo-per-controllo)
8. [Ricette](#ricette)
9. [Comportamenti da conoscere](#comportamenti-da-conoscere)
10. [Personalizzare con il CSS](#personalizzare-con-il-css)
11. [Risoluzione dei problemi](#risoluzione-dei-problemi)
12. [Note per sviluppatori](#note-per-sviluppatori)
13. [Cronologia delle versioni](#cronologia-delle-versioni)

---

## Requisiti

| Componente | Versione minima |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 |
| Elementor | 3.5 |
| JetEngine | qualsiasi versione recente — necessario solo per la sorgente dinamica |

Se Elementor o JetEngine non sono attivi, il plugin non si carica e mostra un avviso nella bacheca invece di generare errori.

---

## Installazione e aggiornamento

**Installazione.** Da *Plugin → Aggiungi nuovo → Carica plugin*, carica lo zip e attiva. Il widget compare nel pannello di Elementor nella categoria **LU3G**, con il nome **Carosello repeater**. Si trova anche cercando "carosello", "carousel", "repeater", "card" o "slider".

**Aggiornamento.** Disattiva ed elimina la versione installata, poi carica il nuovo zip. Le impostazioni dei caroselli non si perdono: sono salvate nelle pagine, non nel plugin. Ogni versione incrementa il numero di versione degli asset, quindi il browser non serve CSS e JS vecchi dalla cache.

> Prima di aggiornare un sito in produzione, prova l'aggiornamento su una copia di test.

---

## Concetti di base

### Tre tipi di contenuto

La prima scelta, in *Contenuto → Card → Tipo di contenuto*, decide da dove arrivano le card.

**Statico — inserito in Elementor.** Le card si scrivono una per una nel pannello. Non serve JetEngine. Il contenuto resta salvato nella pagina: va bene per sezioni fatte una volta sola, come una home o una pagina chi siamo.

**Dinamico — repeater JetEngine.** Le card si leggono da un campo repeater del post. Si configura una volta nel template del CPT e ogni post mostra le sue card. Va bene per schede prodotto, servizi, landing che condividono lo stesso layout con contenuti diversi.

**Immagini.** Una card per ogni foto scelta dalla libreria media, senza testi. Va bene per gallerie, portfolio, prodotti o piatti mostrati solo per immagini.

Le sorgenti Statico e Dinamico hanno gli stessi elementi e usano le stesse sezioni di stile: passare dall'una all'altra non cambia l'aspetto. Con Immagini le sezioni dei testi spariscono dal pannello e compare la sezione di stile Immagini.

### Com'è fatta una card

Dall'alto verso il basso, tutti facoltativi tranne il titolo:

```
┌─────────────────────────────┐
│ [icona]                     │
│ ETICHETTA                   │
│ Titolo                      │
│ Blocco di testo 1           │  ← ognuno usa uno dei
│ Blocco di testo 2           │    tre "stili di testo"
│ …                           │
│ Mostra di più               │  ← solo se il testo è troncato
│                             │
│ Pulsante →                  │
└─────────────────────────────┘
```

### I tre stili di testo

I blocchi di testo non hanno uno stile proprio: ognuno sceglie uno dei **tre stili di testo** definiti nella tab Stile. Per esempio stile 1 per i paragrafi normali, stile 2 in grassetto per le frasi evidenziate, stile 3 piccolo per le note. Due blocchi con lo stesso stile hanno lo stesso aspetto ovunque si trovino, e il pannello resta con tre sezioni anche se usi molti blocchi.

### Larghezza delle card

Di default la larghezza non si imposta: si calcola. Scegli quante card vedere per volta e quanto far sporgere la successiva, e il plugin divide lo spazio disponibile. La formula è:

```
larghezza card = (spazio disponibile − spazi tra le card − anteprima) ÷ card visibili
```

Così il carosello resta proporzionato a qualunque larghezza di container. In alternativa si può impostare una larghezza fissa.

---

## Guida rapida: card statiche

1. Trascina **Carosello repeater** nella pagina.
2. In *Contenuto → Card*, imposta **Tipo di contenuto** su *Statico — inserito in Elementor*.
3. Nel repeater **Card**, compila ogni card. Ogni riga ha due tab:
   - **Contenuto**: etichetta, titolo, testo, eventuali blocchi aggiuntivi, icona, testo del pulsante e link.
   - **Stile**: attiva *Colori propri* solo se questa card deve avere colori diversi dalle altre, e *Immagine di sfondo* per metterle una foto dietro ai testi.
4. In *Contenuto → Layout* scegli quante **Card visibili** e quanta **Anteprima card successiva**.
5. Passa alla tab **Stile** e regola card, titolo, stili di testo e pulsante.

**Aggiungere blocchi di testo a una card.** Sotto il primo testo c'è l'interruttore **Aggiungi un altro blocco di testo**. Attivandolo compare un nuovo blocco con il suo menu di stile e il suo interruttore per aggiungerne un altro, fino a cinque blocchi in tutto. Spegnendo un interruttore, quel blocco e tutti quelli successivi spariscono dal pannello e dalla pagina.

---

## Guida rapida: card da repeater JetEngine

Prerequisito: un meta box JetEngine sul CPT con un campo **repeater**, per esempio `campi_applicazione`, con dentro i sottocampi che ti servono (`titolo`, `descrizione`, `icona`, `link`…).

1. Apri il template del CPT in Elementor e trascina **Carosello repeater**.
2. In *Contenuto → Card*, lascia **Tipo di contenuto** su *Dinamico — repeater JetEngine*.
3. **Campo repeater**: scegli il repeater dall'elenco. Se non compare, lascia l'opzione di inserimento manuale e scrivi il nome del campo in **Nome del campo (manuale)**.
4. **Sottocampo da mostrare**: il nome del sottocampo del titolo.
5. Facoltativi, con gli stessi elementi delle card statiche:
   - **Sottocampo etichetta** per il testo sopra il titolo;
   - **Blocchi di testo**: un repeater dove ogni riga collega un sottocampo a uno dei tre stili di testo, nell'ordine in cui vuoi mostrarli;
   - **Sottocampo icona**: un sottocampo media;
   - **Sottocampo link**;
   - **Testo del pulsante**: fisso, come `Scopri`, oppure `%nome_sottocampo%` per leggerlo dalla riga.
6. **ID del post**: lascia vuoto per leggere il post corrente, che è il caso normale nei template.

Nell'editor il widget mostra le card del post di anteprima. Se appare "Nessuna riga trovata", controlla il nome del campo e che il post di anteprima abbia il repeater compilato.

**Formato avanzato.** Per comporre il titolo da più sottocampi, scrivi in **Formato avanzato** qualcosa come `<strong>%titolo%</strong> — %sottotitolo%`. Se compilato, sostituisce il sottocampo del titolo.

---

## Guida rapida: carosello di immagini

1. Trascina **Carosello repeater** nella pagina.
2. In *Contenuto → Card*, imposta **Tipo di contenuto** su *Immagini*.
3. Aggiungi le foto dal controllo galleria: si scelgono più immagini insieme e si riordinano trascinandole. Nei template di CPT si può collegare una galleria dinamica, per esempio un campo galleria di JetEngine, dall'icona dei tag dinamici.
4. **Dimensione dell'immagine**: *large* va bene quasi sempre. Il plugin genera anche le versioni ridotte per gli schermi piccoli.
5. **Al click**: nessuna azione, apertura nel lightbox di Elementor, o apertura del file.
6. In *Layout → Proporzione* scegli la forma delle card, per esempio 3:4 per foto verticali. Tutte le card avranno la stessa forma, qualunque sia la proporzione delle foto: le immagini vengono ritagliate.
7. In *Stile → Immagini* scegli il punto di ritaglio, lo zoom in hover e un'eventuale velatura colorata.

Frecce, indicatori, scorrimento infinito, autoplay e animazioni funzionano come per le card di testo.

---

## Il pannello, controllo per controllo

I controlli contrassegnati con **R** sono responsive: possono avere valori diversi per desktop, tablet e mobile.

### Tab Contenuto

#### Card

| Controllo | Cosa fa |
|---|---|
| Tipo di contenuto | *Statico*, *Dinamico — repeater JetEngine* o *Immagini*. |
| Card | *(statico)* Il repeater delle card, con le tab Contenuto e Stile per ogni riga. |
| Campo repeater / Nome del campo | *(dinamico)* Il repeater JetEngine da leggere. |
| Sottocampo da mostrare | *(dinamico)* Il sottocampo del titolo. |
| Sottocampo etichetta | *(dinamico)* Testo breve sopra il titolo. |
| Blocchi di testo | *(dinamico)* Sottocampi da mostrare sotto il titolo, ognuno con uno stile. |
| Testo del pulsante | *(dinamico)* Testo fisso o `%sottocampo%`. |
| Sottocampo icona | *(dinamico)* Sottocampo media. Gli SVG diventano ricolorabili. |
| Link della card | *(dinamico)* *Da un sottocampo link*, oppure *Dal primo link nel testo*: cerca un `<a href>` nel titolo e nei blocchi di testo, lo sposta sul pulsante e lo toglie dal testo. Il pulsante compare solo sulle card che hanno un link. |
| Sottocampo link | *(dinamico)* Va sul pulsante; senza pulsante rende cliccabile la card. |
| Apri in una nuova scheda | *(dinamico)* Per il link della card o del pulsante. |
| Formato avanzato | *(dinamico)* Titolo composto con `%sottocampo%`. |
| ID del post | *(dinamico)* Vuoto = post corrente. |
| Immagini | *(immagini)* Le foto del carosello; accetta una galleria dinamica. |
| Dimensione dell'immagine | *(immagini)* Versione della foto da caricare. |
| Al click | *(immagini)* Nessuna azione, lightbox, apertura del file. |

**Dentro ogni card statica — tab Contenuto:** Etichetta, Titolo, Testo con il suo stile, blocchi aggiuntivi, Icona, Testo del pulsante, Link.

**Dentro ogni card statica — tab Stile:**
- interruttore *Colori propri*; se attivo: Sfondo, Sfondo in hover, Bordo, Testi e icona, Testo del pulsante, Sfondo del pulsante. I campi lasciati vuoti restano quelli generali;
- interruttore *Immagine di sfondo*; se attivo: Immagine (anche da tag dinamico), Dimensioni (Cover, Contenitore, Automatica, Personalizzato con larghezza) **R**, Posizione (nove posizioni o Personalizzato con X e Y) **R**, Ripetizione, e la velatura: Colore, Intensità, Intensità in hover.

#### Opzioni del contenuto

| Controllo | Cosa fa |
|---|---|
| Tag del titolo | H2–H6, p, span, div. Cambia solo la semantica, non l'aspetto: per card con descrizione usa H3. |
| Icona del pulsante | Icona o SVG accanto al testo del pulsante. |
| Solo testo | Toglie i tag HTML dal titolo e dai campi JetEngine. I blocchi di testo delle card statiche mantengono sempre la formattazione dell'editor. |
| Tronca il testo | Limita il testo a un numero di righe e aggiunge *Mostra di più*. |
| Cosa troncare | Il titolo o il primo blocco di testo. |
| Righe visibili **R** | Quante righe restano visibili prima del taglio. |
| Etichette per espandere / richiudere | I testi del pulsante, di default *Mostra di più* / *Mostra meno*. |

#### Layout

| Controllo | Cosa fa |
|---|---|
| Larghezza delle card | *Calcolata dalle card visibili* o *Fissa*. |
| Proporzione **R** | *(immagini)* Forma delle card: 1:1, 4:5, 3:4, 2:3, 9:16, 4:3, 3:2, 16:9. Quando è impostata l'altezza delle card viene ignorata. *Usa l'altezza delle card* torna ai controlli di altezza. |
| Larghezza **R** | Solo in modalità fissa, in px o vw. |
| Card visibili **R** | Quante card intere si vedono. Default 4 / 2 / 1. |
| Anteprima card successiva **R** | Quanto sporge la card successiva, come indizio che si può scorrere. Zero per card esatte. |
| Spazio tra le card **R** | Distanza tra una card e l'altra. |
| Altezza delle card **R** | Altezza, o altezza minima se non è fissa. |
| Altezza fissa | Attivo: tutte le card hanno esattamente l'altezza indicata e il testo in eccesso viene tagliato. Spento: l'altezza è un minimo e le card crescono tutte insieme, restando uguali. |
| Distribuzione del contenuto | *Raggruppato*: tutto il contenuto insieme, posizionato in alto, al centro o in basso. *Distribuito*: contenuto in alto e pulsante in fondo, con icone, titoli e pulsanti allineati tra le card. |
| Posizione del testo **R** | Solo in modalità raggruppata. |

#### Scorrimento

| Controllo | Cosa fa |
|---|---|
| Velocità di scorrimento | Durata dell'animazione a ogni scatto, per frecce, indicatori e autoplay. |
| Aggancio allo scroll | Le card si allineano al bordo quando lo scorrimento si ferma. |
| Scorrimento infinito | Dopo l'ultima card si riparte dalla prima. Servono almeno due card. |
| Autoplay | Scorrimento automatico. |
| Tempo tra uno scorrimento e l'altro | In secondi. |
| Metti in pausa al passaggio del mouse | Ferma l'autoplay mentre il mouse è sul carosello. |

#### Frecce

| Controllo | Cosa fa |
|---|---|
| Mostra le frecce | Attiva la navigazione a frecce. |
| Numero di frecce | *Una*: cambia verso quando il carosello arriva in fondo. *Due*: avanti e indietro. |
| Posizione | A sinistra, a destra, ai due lati, sopra, sotto, sovrapposte alle card. |
| Allineamento **R** | Sopra e sotto: sinistra, centro, destra, ai due estremi. |
| Allineamento verticale **R** | Frecce laterali e sovrapposte: alto, centro, basso. |
| Distanza dal bordo **R** | Frecce sovrapposte. I valori negativi le portano fuori dalle card. |
| Distanza tra le frecce **R** | Con due frecce vicine. |
| Icona indietro / avanti | Icone o SVG personalizzati. |
| Nascondi su mobile | Su touch lo swipe è già sufficiente. |
| Nascondi se non c'è niente da scorrere | Quando tutte le card sono già visibili le frecce spariscono invece di restare spente. Si ricalcola al ridimensionamento. |

#### Indicatori di posizione

| Controllo | Cosa fa |
|---|---|
| Tipo | Nessuno, Puntini, Barra di avanzamento, Numeri (es. 2 / 6). |
| Posizione | Sotto, sopra, sovrapposti alle card in basso, tra le due frecce. |
| Allineamento **R** | Sinistra, centro, destra. |
| Distanza dal carosello **R** | Per i sovrapposti è la distanza dal bordo inferiore. |
| Nascondi su mobile | Nasconde gli indicatori sotto i 767px. |

*Tra le due frecce* richiede due frecce a sinistra, a destra, sopra o sotto. Negli altri casi gli indicatori vanno automaticamente sotto il carosello.

### Tab Stile

Le sezioni seguono l'ordine degli elementi nella card. Dove c'è un effetto hover, i colori sono divisi nelle tab **Normale** e **Hover**. L'hover si attiva passando sull'intera card, non solo sul singolo elemento.

| Sezione | Controlli |
|---|---|
| Card | Sfondo e bordo (normale / hover), raggio dei bordi, padding. Con le immagini il padding non crea cornici: la foto copre l'intera card. |
| Immagini | *(immagini)* Adattamento (riempi ritagliando, o intera), punto di ritaglio **R**, zoom in hover, velatura in hover. |
| Icona | Posizione rispetto al testo (sopra, sotto, sinistra, destra) **R**, dimensione, distanza dal testo, colore (normale / hover). |
| Etichetta | Tipografia, distanza dal titolo, colore (normale / hover). |
| Titolo | Tipografia, allineamento, righe riservate, colore (normale / hover). |
| Stile testo 1, 2, 3 | Tipografia, distanza dal blocco sopra, colore (normale / hover). |
| Mostra di più | Tipografia, distanza, sottolineatura, colore e sfondo (normale / hover), bordo, raggio, padding. |
| Pulsante | Allineamento, distanza dal testo, tipografia, colore e sfondo (normale / hover), bordo, raggio, padding, icona e spostamento della freccia in hover. |
| Frecce | Dimensione, colore e sfondo (normale / hover), bordo, raggio, padding, distanza dal carosello, opacità quando inattive. |
| Indicatori di posizione | Puntini: dimensione, larghezza dell'attivo, distanza, colori. Barra: larghezza, spessore, colori. Numeri: tipografia e colore. |
| Animazioni | Ingresso e hover: vedi sotto. |

**Righe riservate** (nel Titolo) fa occupare a ogni titolo lo spazio di N righe anche quando ne usa meno. Con 2, se un titolo va su due righe e gli altri su una, le descrizioni partono comunque tutte alla stessa altezza.

**Animazioni d'ingresso.** Dieci tipi: dissolvenza, dal basso, dall'alto, da sinistra, da destra, ingrandimento, rimpicciolimento, sfocatura, ribaltamento, scoprimento dal basso. Si regolano durata, andamento, distanza di partenza e ritardo tra una card e l'altra. L'animazione parte quando il carosello entra nello schermo, non al caricamento della pagina.

**Effetti hover.** Sulla card: sollevamento, abbassamento, ingrandimento, rimpicciolimento, inclinazione, ombra. Sul titolo: scorrimento laterale, sollevamento, sottolineatura animata.

---

## Ricette

### Card con icone, titoli e pulsanti allineati

Il layout "servizi": icona, titolo, descrizione e pulsante in fondo, allineati tra tutte le card anche con testi di lunghezza diversa.

1. *Layout → Distribuzione del contenuto*: **Distribuito**.
2. *Layout → Altezza fissa*: spento.
3. *Stile → Titolo → Righe riservate*: **2**, o il numero di righe del titolo più lungo.
4. *Stile → Pulsante → Allineamento*: a piacere, per esempio al centro.

### Una card di colore diverso dalle altre

1. Nella card da evidenziare, tab **Stile** della riga, attiva **Colori propri**.
2. Imposta solo i colori che cambiano, per esempio sfondo scuro, testi chiari, pulsante invertito.

Le altre card restano sui colori generali.

### Frecce in basso a sinistra con la barra in mezzo

1. *Frecce*: **Numero di frecce** *Due*, **Posizione** *Sotto il carosello*, **Allineamento** *Sinistra*.
2. *Indicatori di posizione*: **Tipo** *Barra di avanzamento*, **Posizione** *Tra le due frecce*.

Risultato: `← ━━━━──── →`. Con **Tipo** *Puntini* si ottiene `← ● ○ ○ →`.

### Card con foto di sfondo e testo leggibile

1. Nella card, tab **Stile** della riga, attiva **Immagine di sfondo** e scegli la foto.
2. **Dimensioni** *Cover*, **Posizione** sul soggetto da tenere in vista, per esempio *Sopra al centro* per un volto.
3. **Colore della velatura** nero, o il colore del brand scuro; **Intensità** fra 0,35 e 0,55.
4. In *Colori propri*, **Testi e icona** chiari.
5. Facoltativo: **Intensità in hover** più bassa, per schiarire la foto al passaggio del mouse.

### Pulsante solo sulle card che hanno un link

Quando i link sono scritti dentro i testi del repeater, per esempio un titolo che contiene `<a href="…">`:

1. *Card → Link della card*: **Dal primo link nel testo**.
2. *Testo del pulsante*: per esempio `Scopri di più`. Se lo lasci vuoto viene usato "Scopri di più".

Su ogni card il plugin cerca il primo link nel titolo e poi nei blocchi di testo. Se lo trova, il pulsante compare con quel link, e si apre in una nuova scheda se il link aveva `target="_blank"`. Nel testo il link viene tolto e resta solo il suo contenuto, così il titolo non diventa cliccabile due volte. Sulle card senza link il pulsante non compare.

### Carosello che gira da solo

1. *Scorrimento*: attiva **Scorrimento infinito** e **Autoplay**.
2. **Tempo tra uno scorrimento e l'altro**: 4–5 secondi è un buon punto di partenza.
3. Lascia attiva la pausa al passaggio del mouse.

### Card con testo lungo e "Mostra di più"

1. *Opzioni del contenuto*: attiva **Tronca il testo**.
2. **Cosa troncare**: *Il primo blocco di testo*.
3. **Righe visibili**: 3 su desktop, 2 su mobile.

Il pulsante *Mostra di più* compare solo sulle card dove il testo viene davvero tagliato.

---

## Comportamenti da conoscere

**Il link segue il pulsante.** Se una card ha il testo del pulsante, il link va sul pulsante e la card non è cliccabile. Se non ce l'ha, il link rende cliccabile l'intera card. Le due cose si escludono perché un link dentro un altro link non è HTML valido. Vale per entrambe le sorgenti.

**Troncamento e card cliccabili.** Se l'intera card è un link, il troncamento si disattiva, perché il pulsante *Mostra di più* dentro un link non funzionerebbe. Con il link sul pulsante invece il troncamento funziona.

**Quanti puntini.** Ce n'è uno per ogni posizione raggiungibile, non uno per card. Con 6 card e 3 visibili i puntini sono 4, perché le ultime card non possono diventare la prima visibile. Con lo scorrimento infinito ogni card ha il suo puntino. Se non c'è niente da scorrere, l'indicatore non compare.

**Scorrimento infinito.** Il carosello duplica le card prima e dopo l'originale e, quando si esce dalla parte centrale, si riposiziona senza che si veda. Le copie sono escluse da lettori di schermo e tastiera. Aprire *Mostra di più* su una card apre anche le sue copie.

**Autoplay e accessibilità.** L'autoplay non parte per chi ha attivato la riduzione delle animazioni nelle impostazioni del sistema. Si mette in pausa quando si naviga il carosello da tastiera, quando la scheda del browser non è in primo piano e mentre una card è espansa con *Mostra di più*. Un click sulle frecce o uno swipe azzerano il conteggio.

**Nell'editor.** Il carosello funziona anche nell'anteprima di Elementor, e si reinizializza a ogni modifica dei controlli.

**Lightbox e scorrimento infinito.** Con il lightbox, le frecce del lightbox scorrono tutte le foto del carosello. Con lo scorrimento infinito ogni copia della galleria forma un gruppo a sé, così da qualunque copia si apra, il lightbox mostra ogni foto una volta sola.

**Senza JavaScript.** Le card restano visibili e scorrevoli a mano, e gli indicatori non compaiono invece di restare vuoti.

---

## Personalizzare con il CSS

Quasi tutto si regola dal pannello. Per casi particolari, queste sono le classi principali:

| Classe | Elemento |
|---|---|
| `.lu3g-carousel` | Contenitore del widget |
| `.lu3g-carousel__stage` | Area delle card, riferimento per ciò che è sovrapposto |
| `.lu3g-carousel__viewport` | Area che scorre |
| `.lu3g-carousel__track` | Fila delle card |
| `.lu3g-carousel__card` | Singola card |
| `.lu3g-carousel__card--image` / `.lu3g-carousel__image` | Card immagine / foto |
| `.lu3g-carousel__icon` | Icona |
| `.lu3g-carousel__kicker` | Etichetta |
| `.lu3g-carousel__text` | Titolo |
| `.lu3g-carousel__block--style-1` / `-2` / `-3` | Blocchi di testo per stile |
| `.lu3g-carousel__button` | Pulsante della card |
| `.lu3g-carousel__toggle` | Mostra di più |
| `.lu3g-carousel__nav` / `.lu3g-carousel__arrow` | Contenitore delle frecce / freccia |
| `.lu3g-carousel__indicators` / `.lu3g-carousel__dot` | Indicatori / singolo puntino |

Stati aggiunti dal JavaScript: `.is-active` sul puntino attivo, `.is-expanded` sulla card aperta, `.is-disabled` sulla freccia inattiva, `.is-clone` sulle copie del loop.

Le misure passano per variabili CSS definite su `.lu3g-carousel`, che il pannello sovrascrive. Si possono impostare anche a mano: con Elementor Pro, in *Avanzate → CSS personalizzato* del widget, dove `selector` indica il widget stesso; senza Pro, nel CSS del tema usando una classe aggiunta al widget in *Avanzate → Classi CSS*.

```css
selector .lu3g-carousel {
	--lu3g-gap: 32px;
	--lu3g-dot-active-color: #c8a24a;
}
```

Le principali: `--lu3g-visible`, `--lu3g-peek`, `--lu3g-gap`, `--lu3g-card-height`, `--lu3g-arrow-gap`, `--lu3g-arrow-offset`, `--lu3g-indicator-gap`, `--lu3g-dot-size`, `--lu3g-dot-color`, `--lu3g-dot-active-color`, `--lu3g-progress-color`, `--lu3g-lines`, `--lu3g-icon-size`, `--lu3g-hover-duration`.

---

## Risoluzione dei problemi

**Nelle card compaiono tag come `<p>` scritti per esteso.**
Il sottocampo JetEngine è un campo WYSIWYG. Attiva *Opzioni del contenuto → Solo testo*, oppure lascialo spento per mantenere la formattazione.

**L'elenco dei campi repeater è vuoto.**
JetEngine non ha un'interfaccia ufficiale per elencare i campi e la sua struttura interna può cambiare tra versioni. Usa **Nome del campo (manuale)**: il carosello funziona allo stesso modo.

**Il carosello dinamico non mostra card nell'editor.**
Il messaggio nell'editor dice quale passaggio non torna. I casi sono tre:
- *manca il nome del campo*: sceglilo dall'elenco o scrivilo in "Nome del campo (manuale)";
- *il campo è vuoto o non esiste*: il nome è sbagliato, oppure il post usato per l'anteprima del template non ha il repeater compilato. Nelle impostazioni del template scegli come anteprima un post che ha dati;
- *le righe ci sono ma manca il sottocampo*: "Sottocampo da mostrare" non corrisponde al *name* del sottocampo in JetEngine. Il messaggio elenca i sottocampi disponibili. Attenzione: `testo` è solo il valore di esempio del campo.

**Le frecce hanno un bordo o uno sfondo che non ho impostato.**
È lo stile del tema applicato a tutti i pulsanti. Il plugin lo azzera, ma se il tema usa selettori molto specifici imposta bordo e sfondo esplicitamente in *Stile → Frecce*.

**Il testo delle card esce dal bordo.**
Con *Altezza fissa* attiva il testo in eccesso viene tagliato. Disattivala, oppure attiva il troncamento con *Mostra di più*.

**Su iPhone le card non scorrono con lo swipe, su Android sì.**
Aggiorna almeno alla 1.9.1: era un bug della 1.8.1 e della 1.9.0. Se il problema resta, verifica che nessun CSS personalizzato del tema o della pagina imposti `pointer-events: none` o `touch-action: none` su un contenitore del carosello.

**Il carosello allarga il container.**
Controlla che non ci siano larghezze minime impostate sul container padre. Il widget è progettato per restare dentro lo spazio disponibile.

**Nella console compare un avviso `_load_textdomain_just_in_time`.**
Se il dominio indicato non è `lu3g-carousel`, l'avviso viene da un altro plugin e non è un errore. Su un sito di test conviene tenere `WP_DEBUG` attivo, con `WP_DEBUG_DISPLAY` spento e `WP_DEBUG_LOG` acceso: gli avvisi finiscono in `wp-content/debug.log` invece che sulla pagina.

**Dopo un aggiornamento qualcosa sembra vecchio.**
Rigenera il CSS di Elementor da *Elementor → Strumenti → Rigenera file e dati*, e svuota la cache di eventuali plugin di caching.

---

## Note per sviluppatori

### Struttura

```
lu3g-repeater-carousel/
├── lu3g-repeater-carousel.php               bootstrap, controllo dipendenze, registrazione asset
├── includes/
│   └── class-widget-repeater-carousel.php   controlli del pannello e render
├── assets/
│   ├── carousel.css
│   └── carousel.js
└── README.md
```

CSS e JS sono registrati, non accodati: il widget li dichiara in `get_style_depends()` e `get_script_depends()`, quindi Elementor li carica solo nelle pagine che contengono il widget.

### Regole da rispettare

**Mai rinominare gli ID dei controlli.** Sono le chiavi con cui Elementor salva i valori nelle pagine: cambiarne uno svuota quell'impostazione in tutti i caroselli già pubblicati. Spostare un controllo tra sezioni o cambiarne l'etichetta è invece sicuro. Per questo alcuni ID hanno nomi storici, come `card_text` per il titolo o `description_*` per lo Stile testo 1.

**Prefissare i metodi con `lu3g_`.** `Widget_Base` eredita da `Base_Object`, che dichiara alcuni metodi `final`: un helper chiamato `get_items()` ha già causato un errore fatale. Il prefisso evita collisioni anche con metodi che Elementor potrebbe aggiungere in futuro.

**Non cambiare i valori di default dei controlli esistenti.** Elementor non salva i valori lasciati sul default: cambiarlo modificherebbe l'aspetto di tutti i caroselli che lo usano.

### Punti di estensione

- **Numero massimo di blocchi di testo** nelle card statiche: costante `LU3G_MAX_EXTRA_BLOCKS` in cima alla classe del widget, oggi 4 oltre al primo. Pannello e render si adattano da soli.
- **Nuovo stile di sezione per un blocco di testo**: il metodo `lu3g_register_block_style()` genera una sezione completa con tipografia, distanza e colori.
- **Nuova animazione d'ingresso**: tutte usano un solo `@keyframes` che legge variabili di partenza (`--lu3g-from-x`, `--lu3g-from-y`, `--lu3g-from-scale`…). Un nuovo tipo è una regola CSS più una voce nel menu.

### Scelte tecniche

**Scorrimento nativo.** Il carosello usa `overflow-x` e `scroll-snap` del browser, senza librerie: swipe, inerzia, trackpad e tastiera funzionano senza codice. Lo scorrimento di frecce, indicatori e autoplay è un'animazione su `requestAnimationFrame`, perché la durata di `scrollBy` con `behavior: smooth` la decide il browser e non si può regolare.

**Margine di sicurezza verticale.** `overflow-x: auto` rende automaticamente `auto` anche `overflow-y`, quindi il viewport taglierebbe bordi, ombre e sollevamenti in hover. Il viewport ha un padding verticale compensato da un margine negativo uguale (`--lu3g-bleed`), che si adatta all'effetto hover scelto: fino a 48px con l'ombra. Quella fascia è invisibile ma occupa spazio: frecce e indicatori fuori dallo stage hanno uno `z-index` più alto, così la fascia e l'ombra non li coprono. Il viewport non può invece avere `pointer-events: none`, che renderebbe la fascia trasparente ai click: su iOS Safari lo scorrimento al tocco dipende dal contenitore che scorre, e con quella regola il carosello non risponde allo swipe. Il limite residuo: con l'ombra in hover, un elemento della pagina a meno di 48px dal carosello può risultare coperto; basta lasciare un po' più di spazio.

**Loop su mobile.** Il riposizionamento del loop aspetta la fine dello slancio inerziale (evento `scrollend`, o un timer dove non esiste): farlo durante lo slancio produce salti e sfarfallio del testo.

**SVG nel markup.** Le icone SVG vengono inserite nel markup invece che in un `<img>`, perché solo così prendono il colore dal pannello. Il file passa per `wp_kses` con un elenco chiuso di tag e attributi vettoriali, che esclude script, eventi e riferimenti esterni.

**Immagine di sfondo delle card.** Tre livelli: l'immagine è il `background` della card, la velatura è uno pseudo-elemento `::before` con colore e intensità da variabili CSS, i testi stanno sopra con `z-index`. Dimensione, posizione e ripetizione arrivano dai controlli della riga, non dal CSS di base, perché lo sfondo generale delle card usa la forma abbreviata `background:`, che le azzera. Tutti i selettori richiedono la classe `--has-bg`, stampata solo con l'interruttore acceso e un'immagine scelta: spegnendolo l'immagine sparisce anche se resta salvata, e una velatura scura non copre mai per sbaglio una card a tinta unita.

**Colori per singola card.** Usano `{{CURRENT_ITEM}}` di Elementor: ogni riga del repeater riceve una classe unica, stampata sulla card, con selettori più specifici di quelli generali.

---

## Cronologia delle versioni

**1.11.0**
- Opzione per nascondere le frecce quando non c'è niente da scorrere.

**1.10.1**
- Versione di prova del sistema di aggiornamento automatico.

**1.10.0**
- Nuova opzione *Link della card → Dal primo link nel testo* per la sorgente dinamica: il pulsante compare solo sulle card che hanno un link nei testi, e usa quel link.

**1.9.2**
- Nell'editor, quando la sorgente dinamica non produce card, il messaggio dice quale passaggio non torna e, se il sottocampo è sbagliato, elenca quelli disponibili nel repeater.

**1.9.1**
- Corretto: su iPhone le card non scorrevano con lo swipe. Una regola introdotta nella 1.8.1 per le frecce (`pointer-events: none` sul contenitore che scorre) blocca lo scorrimento al tocco in Safari per iOS. Le frecce restano cliccabili grazie allo `z-index`.

**1.9.0**
- Immagine di sfondo per le singole card statiche: dimensioni, posizione (anche personalizzata), ripetizione, velatura con colore, intensità e intensità in hover.

**1.8.1**
- Corretto: con l'effetto hover *Ombra*, le frecce sopra o sotto il carosello non rispondevano al click, perché la fascia di margine attorno alle card le copriva.

**1.8.0**
- Nuovo tipo di contenuto **Immagini**: carosello di sole foto dalla libreria media o da una galleria dinamica.
- Proporzione delle card, punto di ritaglio, zoom e velatura in hover, lightbox di Elementor.
- In modalità Immagini il pannello nasconde le sezioni dei testi.

**1.7.0**
- La sorgente dinamica ha gli stessi elementi di quella statica: etichetta, blocchi di testo con stile, pulsante con testo fisso o da sottocampo.
- Nuovi indicatori di posizione: puntini, barra di avanzamento, numeri; sotto, sopra, sovrapposti o tra le frecce.
- Nuovo contenitore `__stage` attorno al viewport, riferimento per gli elementi sovrapposti.
- Ripristinato l'interruttore per aggiungere blocchi di testo.

**1.6.0**
- "Numero di frecce" al posto di "Modalità"; "Tipo di contenuto" con le voci Statico e Dinamico.
- Pulsanti per aggiungere i blocchi di testo, sostituiti di nuovo dall'interruttore nella 1.7.0.

**1.5.0**
- Pannello riorganizzato: Card, Opzioni del contenuto, Layout, Scorrimento, Frecce; tab Normale / Hover nelle sezioni di stile.
- Sei posizioni per le frecce, con allineamenti e distanze.
- Corretto: con le frecce sotto, il carosello collassava ad altezza zero.

**1.4.0**
- Blocchi di testo aggiungibili a catena nelle card statiche.
- Tre stili di testo condivisi al posto di uno stile per campo.

**1.3.0**
- Colori propri per singola card.
- Etichetta sopra il titolo, testi con editor visuale.

**1.2.1**
- Corretto il bordo inferiore delle card tagliato dal viewport.
- Corretto il sollevamento in hover bloccato dall'animazione d'ingresso.

**1.2.0**
- Distribuzione del contenuto "Distribuito" e righe riservate per il titolo.

**1.1.0**
- Card statiche con titolo, descrizione e pulsante; tag del titolo; troncamento su titolo o descrizione.

**1.0.x**
- Carosello da repeater JetEngine e da contenuto statico; icone SVG; card visibili e anteprima; larghezza fissa; troncamento; scorrimento infinito; autoplay; animazioni d'ingresso e hover.
- 1.0.1: corretto l'errore fatale dovuto a un metodo in conflitto con Elementor.
