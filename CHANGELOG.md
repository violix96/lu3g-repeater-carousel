# Changelog

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
