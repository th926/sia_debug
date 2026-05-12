Utgangspunkt for en handover samtale.
## Hvorfor er programmet til
Mange kunder bruker veldig mye serverplass. Tingen som tar mest plass er bilder, der mange av dem er ubrukte. Serverplass koster mye penger vi ønsker å ha flere servere på planen før vi må oppgradere.

Wordpress har ingen måte å finne ut av om bilder er brukt. Man kan manuelt slette bilder, men dette er tidkrevende når vi snakker om flere tusen bilder. Tanken er et program som kan finne ut hva som er ubrukt og slette dem.
## Begrunnelser tatt i programmet
På grunn av PHP og wordpress sine begrensninger er de ikke egnet for denne type program. Om man likevel skulle valgt å benytte seg av disse teknologiene hadde det krevet å ha muligheten til å nå serveren fordi programmet måtte ha blitt kjørt ved bruk av wordpress cli. Derfor er det rettferdig at programmet må lastes opp til serveren. Serveren kan ikke bruke php, at node er installert er ikke garantert så vi må ha noe som kan kjøre på serveren uansett. Vi trenger noe som kan kompileres, dermed C++.
## Hvordan er det tenkt å brukes
Filen kompileres ved bruk av cmake til å lage bygge systemet og make for selve kompileringen. Husk å fjerne debug flagget når du skal sette den i produksjon. For å kjøre er det som å bruke hvilken som helst annen binær fil.
## Hvordan virker det
| Symbol | Mening                                                      |
| ------ | ----------------------------------------------------------- |
| %      | wildcard matcher hva som helst                              |
| wp_    | prefixen for databasen. Kan endre seg og må finnes dynamisk |
Programmet henter alle bilder fra wp_posts databasen med følgene spørring:
``` sql
select ID from wp_posts where post_mime_type LIKE 'image/%' AND post_type = 'attachment'
```

Så iterer vi over alle disse idene og de følgene spørringene er for å finne den nåværende iden i databasen. Vi bruker databasens søke funksjoner for å forenkle programmet.
### SQL Spørringer
Kjernen av programmet er de følgende sql spørringene. `attachment_id` eller `{$attachment_id}` betyr at det må byttes ut med attachment iden du søker etter når du kjører spørringen. I programmet er disse spørringene i klassen `Searcher` som befinner seg i `Searcher.h` og `Searcher.cpp`. Alle må søkes for å ikke slette noen brukte bilder!
#### Postmeta
##### Featured images
```sql
SELECT post_id FROM wp_postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = {$attachment_id};
```
##### Annet ACF bruk
```sql
SELECT post_id FROM wp_postmeta WHERE meta_value = '%\"{$attachment_id}\"%' OR meta_value = '%i:{$attachment_id}%' OR meta_value = '%attachment_id\";i:{$attachment_id}' AND meta_key NOT LIKE '_%%';
```
#### Posts
##### ACF blokker
```sql
SELECT ID FROM wp_posts WHERE post_type != 'revision' AND post_type != 'attachment' AND post_content LIKE '%\":{$attachment_id},%';
```
##### Innebygde wordpress blokker
``` sql
SELECT ID FROM wp_posts WHERE post_status = 'publish' AND (post_content LIKE '%wp-image-{$attachment_id}%' OR post_content LIKE '{$attachment_id}=\"{$attachment_id}\"%' OR post_content LIKE '%data-id=\"{$attachment_id}\"%');
```

#### Options
##### Bilder lagret i instillinger (f.eks. socials ikoner eller lignende globale ting)
``` sql
SELECT option_id FROM wp_options WHERE option_value LIKE '%${attachment_id}%';
```
##### Widgets
```sql
SELECT option_id FROM wp_options
        WHERE option_name LIKE 'widget_%%' AND option_value LIKE '%{$attachment_id}%'
```
##### customizer
```sql
SELECT option_id FROM wp_options
        WHERE option_name LIKE 'theme_mods_%%' AND option_value LIKE '%{$attachment_id}%'
```
