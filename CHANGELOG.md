# Changelog

## 1.17.0
- Nuova opzione *Layout → Distribuzione del contenuto → Allineato riga per riga*: icona, etichetta, titolo e ogni blocco di testo prendono l'altezza del più alto tra le card, così titoli e testi partono tutti alla stessa quota anche se un titolo va su più righe. Il pulsante resta in fondo come in *Distribuito*. Le altezze si ricalcolano al ridimensionamento e dopo il caricamento di font e immagini.

## 1.16.0
- Nuova opzione *Scorrimento → Blocca con poche card*. Se le card sono al massimo il numero indicato (*Blocca fino a*, impostabile per dispositivo: di default 3 su desktop, 2 su tablet, 1 su mobile), il carosello diventa una riga ferma: niente scorrimento, frecce, indicatori, scorrimento infinito, autoplay e modalità centrata.
- *Card bloccate*: le card si dividono tutta la larghezza, oppure mantengono la loro larghezza allineate a sinistra, al centro o a destra.
- Se cambiando dispositivo, o ridimensionando la finestra, il blocco si attiva o si disattiva, il carosello si ricostruisce da solo.

## 1.15.0
- Nuovo controllo *Stile → Icona → Allineamento* (sinistra, centro, destra, per dispositivo), per l'icona sopra o sotto il testo.
- Corretto: le icone SVG lette dal repeater JetEngine (*Sottocampo icona*) che usano maschere, come quelle esportate da Figma, mostravano macchie bianche. Il filtro di sicurezza toglieva il tag `<mask>` lasciando visibili le forme che conteneva.
- Gli id interni degli SVG inseriti nella pagina ora hanno un prefisso unico, così due icone diverse con gli stessi id non si scambiano maschere o gradienti.

## 1.14.1
- Corretto: con *Link della card → Da un sottocampo link* il pulsante compariva su tutte le card, anche su quelle con il sottocampo link vuoto, come pulsante non cliccabile. Ora compare solo se la riga ha il link. Se il *Sottocampo link* non è indicato, il pulsante resta come prima.

## 1.14.0
- Nuova opzione *Link della card → Da sottocampi del repeater* per la sorgente dinamica: link e testo del pulsante si leggono da due sottocampi dedicati (*Sottocampo URL*, di default `link_bottone`, e *Sottocampo testo pulsante*, di default `testo_bottone`), con il suo interruttore *Apri in una nuova scheda*. Il testo della card non viene modificato.
- Le card senza URL non hanno il pulsante. Con l'URL e senza testo si usa il *Testo del pulsante* del widget, altrimenti "Scopri di più". `%testo_link%` nel *Testo del pulsante* riprende il valore del sottocampo testo.
- Le modalità esistenti (*Da un sottocampo link*, *Dal primo link nel testo*) funzionano come prima.

## 1.13.0
- Nuova opzione *Opzioni del contenuto → Contenuto del pulsante*: *Testo e icona*, *Solo testo* o *Solo icona*. Con *Solo icona* il pulsante compare su ogni card che ha un link, senza bisogno di scrivere un testo; il testo, se c'è, diventa l'etichetta per i lettori di schermo.
- Sorgente dinamica con *Dal primo link nel testo*: nel testo del pulsante si può usare `%testo_link%` per riprendere le parole del link trovato.

## 1.12.1
- Modalità centrata: all'avvio è centrata la card di mezzo del gruppo visibile. Con 3 card visibili si vede subito piccola, grande, piccola, senza spazi vuoti.
- La card centrale è in rilievo: ombra regolabile (attiva di default) e dimensione che può superare 1.
- Corretto: l'ultima card non arrivava esattamente al centro in fondo al carosello.

## 1.12.0
- Nuova *Modalità centrata* in *Layout*: la card attiva sta al centro, quelle ai lati sono più piccole e sbiadite, con dimensione e opacità regolabili. Funziona con scorrimento infinito, frecce, indicatori ed effetti hover.

## 1.11.0
- Nuova opzione *Frecce → Nascondi se non c'è niente da scorrere*: quando tutte le card sono già visibili le frecce spariscono invece di restare spente, e ricompaiono se la finestra si restringe.

## 1.10.1
- Versione di prova del sistema di aggiornamento automatico da GitHub. Nessuna modifica al funzionamento del carosello.

## 1.10.0
- Nuova opzione *Link della card → Dal primo link nel testo* per la sorgente dinamica: il pulsante compare solo sulle card che hanno un link nei testi, e usa quel link.

## 1.9.2
- Nell'editor, quando la sorgente dinamica non produce card, il messaggio dice quale passaggio non torna e, se il sottocampo è sbagliato, elenca quelli disponibili nel repeater.

## 1.9.1
- Corretto: su iPhone le card non scorrevano con lo swipe. Una regola introdotta nella 1.8.1 per le frecce (`pointer-events: none` sul contenitore che scorre) blocca lo scorrimento al tocco in Safari per iOS. Le frecce restano cliccabili grazie allo `z-index`.

## 1.9.0
- Immagine di sfondo per le singole card statiche: dimensioni, posizione (anche personalizzata), ripetizione, velatura con colore, intensità e intensità in hover.

## 1.8.1
- Corretto: con l'effetto hover *Ombra*, le frecce sopra o sotto il carosello non rispondevano al click, perché la fascia di margine attorno alle card le copriva.

## 1.8.0
- Nuovo tipo di contenuto **Immagini**: carosello di sole foto dalla libreria media o da una galleria dinamica.
- Proporzione delle card, punto di ritaglio, zoom e velatura in hover, lightbox di Elementor.
- In modalità Immagini il pannello nasconde le sezioni dei testi.

## 1.7.0
- La sorgente dinamica ha gli stessi elementi di quella statica: etichetta, blocchi di testo con stile, pulsante con testo fisso o da sottocampo.
- Nuovi indicatori di posizione: puntini, barra di avanzamento, numeri; sotto, sopra, sovrapposti o tra le frecce.
- Nuovo contenitore `__stage` attorno al viewport, riferimento per gli elementi sovrapposti.
- Ripristinato l'interruttore per aggiungere blocchi di testo.

## 1.6.0
- "Numero di frecce" al posto di "Modalità"; "Tipo di contenuto" con le voci Statico e Dinamico.
- Pulsanti per aggiungere i blocchi di testo, sostituiti di nuovo dall'interruttore nella 1.7.0.

## 1.5.0
- Pannello riorganizzato: Card, Opzioni del contenuto, Layout, Scorrimento, Frecce; tab Normale / Hover nelle sezioni di stile.
- Sei posizioni per le frecce, con allineamenti e distanze.
- Corretto: con le frecce sotto, il carosello collassava ad altezza zero.

## 1.4.0
- Blocchi di testo aggiungibili a catena nelle card statiche.
- Tre stili di testo condivisi al posto di uno stile per campo.

## 1.3.0
- Colori propri per singola card.
- Etichetta sopra il titolo, testi con editor visuale.

## 1.2.1
- Corretto il bordo inferiore delle card tagliato dal viewport.
- Corretto il sollevamento in hover bloccato dall'animazione d'ingresso.

## 1.2.0
- Distribuzione del contenuto "Distribuito" e righe riservate per il titolo.

## 1.1.0
- Card statiche con titolo, descrizione e pulsante; tag del titolo; troncamento su titolo o descrizione.

## 1.0.x
- Carosello da repeater JetEngine e da contenuto statico; icone SVG; card visibili e anteprima; larghezza fissa; troncamento; scorrimento infinito; autoplay; animazioni d'ingresso e hover.
- 1.0.1: corretto l'errore fatale dovuto a un metodo in conflitto con Elementor.
