# WordPress Plugins különböző megoldásokra

Ez a tárhely különböző WordPress bővítményeket tartalmaz, amelyek különféle funkciókat és szolgáltatásokat kínálnak a WordPress weboldalak számára. 

## Tartalom

## Bővítmények listája

### 1. CloudMentor Events

**Verzió:** 1.0.8  
**Leírás:** Kompakt eseménylista Cloud és AI technológiai határidők megjelenítéséhez. Themify kompatibilis.

#### Funkciók

- **Custom Post Type:** Egyszerű eseménykezelés a WordPress adminban dedikált bejegyzés típussal
- **Platform kategóriák:** Azure, AWS, GCP és egyéb felhő platformok szűrése
- **Esemény típusok:** Új, Beállítás, Biztonság, Kivezetés, Megszűnik, és egyéb típusok megkülönböztetése
- **Soft/Hard határidők:** Megkülönböztethető ajánlott és kötelező határidők kezelése
- **Kompakt nézet:** Kattintásra lenyíló részletek a jobb áttekinthetőség érdekében
- **Widget támogatás:** Sidebar-ban is elhelyezhető widget formában
- **Shortcode támogatás:** Rugalmas beillesztés bárhová shortcode használatával
- **Themify kompatibilis:** Teljes integráció Themify témákkal
- **Reszponzív design:** Mobil-barát megjelenés minden eszközön
- **Akadálymentesség:** ARIA támogatás és billentyűzet navigáció

#### Shortcode használat

Alapértelmezett megjelenítés:
```
[cloud-events]
```

Egyedi limittel:
```
[cloud-events limit="10"]
```

Szűrés platform szerint:
```
[cloud-events platform="azure"]
```

Widget formában a sidebar-ban is használható a "CloudMentor Events" widget hozzáadásával.

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig

---

### 2. Content Guard (CloudMentor)

**Verzió:** 0.0.5  
**Leírás:** Tartalom védelmi plugin WordPress oldalakhoz és bejegyzésekhez

#### Funkciók

- **Teljes tartalom védelem:** A plugin megvédi az összes oldalt és bejegyzést, így csak bejelentkezett felhasználók férhetnek hozzájuk
- **Kivételek kezelése:** Rugalmasan megadhatók azok az oldalak, amelyek kivételként bejelentkezés nélkül is elérhetők
- **Kategória alapú kivételek:** Bejegyzés-kategóriák szintjén is beállíthatók kivételek - a kiválasztott kategóriákba tartozó bejegyzések szabadon elérhetők
- **Átirányítás testreszabása:** Beállítható átirányítási céloldal, ahova a nem bejelentkezett felhasználók kerülnek, ha védett tartalmat próbálnak elérni
- **Hurok védelem:** A plugin automatikusan kezeli az átirányítási hurkokat, biztosítva a zavartalan működést

#### Használat

A beállítások a WordPress admin felületen az „Beállítások" > „Content Guard" menüpontban érhetők el. Itt:
- Kiválaszthatók a kivételként engedélyezett oldalak
- Megadhatók a kivételként engedélyezett bejegyzés-kategóriák
- Beállítható az átirányítás céloldala nem bejelentkezett felhasználók számára

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig

---

### 3. Lab Launcher (CloudMentor)

**Verzió:** 1.2.0  
**Leírás:** CloudMentor Lab indító plugin Azure és AWS felhő platformokhoz

#### Funkciók

- **Felhő labor indítás:** WordPress felületről közvetlenül indíthatók Azure és AWS labor környezetek
- **Felhasználó alapú kezelés:** Minden felhasználó saját labor példányokat indíthat és kezelhet
- **REST API integráció:** Teljes REST API támogatás a backend szolgáltatással való kommunikációhoz
- **Bővített labor státusz követés:** Valós idejű státusz követés a laborok állapotáról, beleértve az „Előkészítés alatt”, „Folyamatban”, „Sikeresen elvégezve” és „Lejárt az idő” állapotokat
- **Webhook támogatás:** Külső rendszerek értesíthetik a plugint a labor státusz változásokról
- **Labor ellenőrzés:** Beépített labor verifikációs funkció a teljesítések nyomon követéséhez
- **Shortcode támogatás:** Egyszerű shortcode-ok használata WordPress oldalakon és bejegyzésekben
- **Admin felület:** Átfogó adminisztrációs felület kurzusok, laborok és felhasználói státuszok kezeléséhez
- **Lab kezelő oldal:** A labor admin oldal `Lab kezelő` néven érhető el, külön `Lab létrehozása` gombbal az új űrlap megnyitásához
- **Részletes státusz szűrés:** A felhasználói státuszok oldalon külön szűrhető az email, a lab azonosító, a cloud provider és a státusz
- **Csoportosított státusz nézet:** A felhasználói státuszok lista email cím szerint csoportosítva jelenik meg az admin felületen
- **Lab indulási idő követés:** Az admin oldalon megjeleníthető a lab indulási időpontja az újonnan indított környezetekhez
- **Képzéskezelés fejlesztések:** A `Képzések` oldalon egyedi `Új képzés` felirat, rendezhető hozzárendelt lab-ok, kurzusleírás megjelenítés és labonkénti státusz a shortcode nézetben
- **Képzés előrehaladás kijelzés:** A képzés shortcode megjeleníti a kapcsolódó gyakorlatok teljesítési arányát, például `Kapcsolódó gyakorlatok (Elvégezve: 1/9 - 11%)`
- **TTL kezelés:** Beállítható labor élettartam (Time To Live) a költségek optimalizálásához
- **Markdown leírás (1.2.0):** Labonként választható HTML vagy natív Markdown leírás; a meglévő lab-ok alapértelmezetten HTML módban maradnak
- **Markdown szerkesztő:** Kód és Előnézet fül, WP média picker képbeszúráshoz, oldaltörés gomb
- **Kódrészlet másolás:** Fenced kódrészleteknél (pl. `bash`, `powershell`) Másolás gomb a parancs vágólapra helyezéséhez
- **Lapozás görgetés:** Oldalváltáskor automatikus görgetés az oldal első címsorához (`##`, `###`, `####`)

#### Lab leírás: HTML és Markdown

Minden lab két leírásmezőt tartalmaz; a frontenden a **Markdown leírás használata** checkbox dönt:

| Mód | Mikor használd | Megjelenítés |
|-----|----------------|--------------|
| HTML (alapértelmezett) | Meglévő lab-ok, TinyMCE tartalom | A régi `description` mező (wp_editor) |
| Markdown | Új vagy átállított lab-ok | A `description_md` mező Parsedown-nal renderelve |

A két mező egymás mellett marad mentve, így bármikor vissza lehet kapcsolni HTML módra adatvesztés nélkül.

**Oldaltörés** (mindkét módban): `<!-- pagebreak -->`

**Markdown szintaxis példák:**

````markdown
## Első lépés

Szöveg **félkövérrel**, `inline kód`.

![Azure portál](https://example.com/kep.png){width=600px}

<!-- pagebreak -->

#### Második lépés

```powershell
cd C:\Users\<felhasználónév>\Downloads
```
````

- **Kép mérettel:** `![leírás](url){width=400px}` vagy `{width=50%}` — a média picker automatikusan generálja a szintaxist
- **Kódrészlet:** `` ```powershell `` … `` ``` `` — a frontenden Másolás gomb jelenik meg; a téma stílusaitól független, dedikált CSS-sel
- **Placeholderek:** A `<felhasználónév>` jellegű szövegek helyesen jelennek meg Windows-utakban is

#### Shortcode használat

Lab megjelenítése oldalon:

```
[lab_launcher id="azure-basic"]
```

Képzés shortcode (kapcsolódó lab-ok listája):

```
[lab_training id="123"]
```

#### Fő komponensek

- **API hívások:** Biztonságos kommunikáció a CloudMentor backend szolgáltatással
- **Kurzus kezelő:** Admin felület a kurzusok és laborok konfigurálásához
- **Felhasználói státusz:** Részletes státusz követés felhasználónként és labor típusonként
- **Shortcode generátor:** Admin felület shortcode-ok egyszerű generálásához
- **Markdown feldolgozó:** Parsedown alapú renderelés, kódrészletek védelme a WordPress HTML-szűrő elől

#### REST API végpontok

- `/lab-launcher/v1/start-lab` - Labor indítása
- `/lab-launcher/v1/verify-lab` - Labor ellenőrzése
- `/lab-launcher/v1/lab-status-update` - Státusz frissítés
- `/lab-launcher/v1/lab-status-webhook` - Webhook státusz fogadás
- `/lab-launcher/v1/preview-markdown` - Markdown előnézet (admin, szerkesztői jogosultság)

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig
- CloudMentor backend szolgáltatás hozzáférés

---

### 4. Registration Guard – Anti-Spam Signup Protection

**Verzió:** 1.0.0  
**Leírás:** Bot-regisztrációk blokkolása a WordPress regisztrációs űrlapon honeypot, időzár, token ellenőrzés és IP-alapú sebességkorlátozás kombinációjával — külső API-kulcs nélkül.

#### Funkciók

- **Honeypot mező:** Rejtett, konfigurálható nevű input mező, amelyet a botok kitöltenek
- **Időzár (time-trap):** Túl gyors vagy lejárt űrlap-beküldések elutasítása
- **Token védelem:** IP-hez kötött, egyszer használatos token a közvetlen POST kérések ellen
- **Sebességkorlát:** IP-alapú próbálkozás-limit (alapértelmezett: 3/óra), Cloudflare támogatással
- **Naplózás:** Elutasított regisztrációs kísérletek naplózása az admin felületen (max. 500 bejegyzés)
- **Nincs külső függőség:** Nem igényel reCAPTCHA, Turnstile vagy más harmadik fél szolgáltatást

#### Használat

A beállítások a WordPress admin felületen a **Beállítások → Registration Guard** menüpontban érhetők el.

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig

---

## Licenc

A bővítmények MIT/GPL licenc alatt érhetők el.


