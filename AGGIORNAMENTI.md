# Aggiornamenti automatici dei plugin da GitHub

Guida per collegare un plugin WordPress a un repository GitHub, così ogni sito dove è installato riceve gli aggiornamenti dalla bacheca, come un plugin di WordPress.org. Usa la libreria [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (versione 5.7 al momento della stesura).

Il primo plugin configurato così è **LU3G Repeater Carousel**: usalo come riferimento.

---

## Come funziona

1. Il codice del plugin sta in un repository GitHub.
2. Quando pubblichi una **Release** su GitHub, una GitHub Action crea lo zip del plugin e lo allega alla Release.
3. Ogni sito controlla periodicamente GitHub (circa ogni 12 ore). Se trova una versione più nuova di quella installata, mostra l'avviso di aggiornamento in *Bacheca → Aggiornamenti* e nella pagina *Plugin*.

---

## Configurare un nuovo plugin

Per ognuno dei tuoi plugin, una volta sola.

### 1. Crea il repository

Su GitHub, repository nuovo con lo **stesso nome della cartella del plugin** (lo *slug*), per esempio `nome-plugin`. Vuoto, senza README.

### 2. Copia la libreria

Copia la cartella `vendor/plugin-update-checker` da LU3G Repeater Carousel dentro il nuovo plugin, sempre in `vendor/plugin-update-checker`.

### 3. Aggiungi il codice

Nel file principale del plugin, dopo il controllo `ABSPATH` e **prima** di qualsiasi controllo su Elementor o altre dipendenze: il plugin deve potersi aggiornare anche se una dipendenza è disattivata. Sostituisci i tre valori segnati.

```php
if ( file_exists( __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';

	$nome_plugin_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/violix96/NOME-PLUGIN/',   // ← repository
		__FILE__,
		'NOME-PLUGIN'                                 // ← slug, uguale alla cartella
	);

	$nome_plugin_updater->getVcsApi()->enableReleaseAssets( '/NOME-PLUGIN\.zip($|[?&#])/i' ); // ← slug

	if ( defined( 'LU3G_GITHUB_TOKEN' ) && LU3G_GITHUB_TOKEN ) {
		$nome_plugin_updater->setAuthentication( LU3G_GITHUB_TOKEN );
	}
}
```

Il nome della variabile (`$nome_plugin_updater`) deve essere diverso per ogni plugin, altrimenti due plugin sullo stesso sito si sovrascrivono a vicenda.

### 4. Copia la GitHub Action

Copia `.github/workflows/release.yml` da LU3G Repeater Carousel e cambia una sola riga:

```yaml
env:
  PLUGIN_SLUG: NOME-PLUGIN
```

L'Action presume che il file principale si chiami `NOME-PLUGIN.php`. Se il tuo si chiama diversamente, rinominalo oppure correggi il nome del file nel passaggio "Controlla che la versione corrisponda al tag".

### 5. Primo caricamento

Carica il plugin nel repository e pubblica la prima Release (vedi sotto). Poi installa **a mano** questa versione sui siti: le versioni vecchie non contengono il codice di aggiornamento, quindi non possono scoprire da sole quelle nuove. Da qui in poi gli aggiornamenti arrivano da soli.

---

## Pubblicare un aggiornamento

Ogni volta che vuoi distribuire una nuova versione:

1. Aumenta il numero di versione nel plugin, in **due punti** se il plugin li ha: l'intestazione `Version:` e l'eventuale costante della versione (es. `LU3G_CAROUSEL_VERSION`). Aggiorna anche `Stable tag:` in `readme.txt`.
2. Aggiungi le novità in cima a `CHANGELOG.md`: compaiono nella finestra "Visualizza dettagli" in bacheca.
3. Fai commit e push su GitHub.
4. Su GitHub: **Releases → Draft a new release**. Tag: la versione con una `v` davanti, per esempio `v1.11.0`. Titolo e descrizione a piacere. Pubblica.
5. Nella scheda **Actions** del repository controlla che l'Action sia verde, e nella Release che sia comparso lo zip.

Se il tag non corrisponde alla `Version:` del plugin, l'Action si ferma con un errore e non allega lo zip, così i siti non ricevono una versione con il numero sbagliato. Correggi, fai push, elimina la Release e ricreala.

Le Release segnate come *pre-release* vengono ignorate dai siti: utili per una versione da provare prima su un solo sito, installandola a mano.

---

## Repository pubblico o privato

**Pubblico**: nessuna configurazione sui siti. Il codice è visibile a chiunque.

**Privato**: ogni sito ha bisogno di un token GitHub per scaricare gli aggiornamenti.

1. GitHub → *Settings → Developer settings → Personal access tokens → Fine-grained tokens → Generate new token*.
2. **Repository access**: *Only select repositories*, e scegli solo i repository dei plugin.
3. **Permissions → Repository permissions → Contents**: *Read-only*. Nient'altro.
4. Imposta una scadenza e segnatela: alla scadenza gli aggiornamenti smettono di arrivare senza errori evidenti.
5. Sui siti, in `wp-config.php`, prima di `/* That's all, stop editing! */`:

```php
define( 'LU3G_GITHUB_TOKEN', 'github_pat_...' );
```

Un solo token può servire tutti i plugin, perché tutti leggono la stessa costante. Non scriverlo mai nel codice dei plugin: finirebbe nel repository e su ogni sito.

---

## Verificare che funzioni

Su un sito di test:

1. Installa la versione attuale del plugin, con il codice di aggiornamento.
2. Pubblica su GitHub una Release con una versione più alta.
3. Nel sito, in *Plugin*, il plugin deve mostrare "È disponibile una nuova versione". Per non aspettare 12 ore, nella riga del plugin c'è il link **Verifica aggiornamenti**, aggiunto dalla libreria.
4. Aggiorna e controlla che la cartella del plugin abbia ancora lo stesso nome e che il plugin resti attivo.

---

## Problemi frequenti

**L'aggiornamento non compare.** Verifica che la Release non sia una *pre-release*, che lo zip sia allegato, che la versione sia più alta di quella installata e, con un repository privato, che il token sia definito e non scaduto. Poi usa *Verifica aggiornamenti*.

**Dopo l'aggiornamento il plugin risulta disattivato o duplicato.** WordPress ha installato uno zip con la cartella sbagliata: di solito lo zip generato da GitHub invece di quello dell'Action. Controlla che la Release abbia lo zip allegato con il nome esatto `NOME-PLUGIN.zip`.

**L'Action fallisce su "Controlla che la versione corrisponda al tag".** Il tag e `Version:` non coincidono: il messaggio d'errore mostra entrambi.
