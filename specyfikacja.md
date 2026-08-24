# Centrum — osobiste centrum dowodzenia zdrowiem i treningiem

**Data specyfikacji:** 2026-08-24
**Status:** zatwierdzona przez klienta, gotowa do planowania implementacji
**Charakter projektu:** aplikacja prywatna, dwoje użytkowników (para), self-hosted na VPS

---

## 1. Cel projektu

Aplikacja webowa (PWA na iPhone + przeglądarka desktop) dla **dwojga użytkowników (para,
osobne konta)**, która prowadzi każde z nich przez proces poprawy zdrowia — z **pełną
separacją danych osobistych** (każde widzi tylko swoje) i **wspólnym modułem posiłków**
(gotują i jedzą razem, ale z różnym zapotrzebowaniem kalorycznym). Cele użytkownika
głównego:

- redukcja wagi z ~101 kg do ~80 kg (zdrowe tempo 0,4–0,7 kg/tydz., bez efektu jojo),
- walka z wysokim cholesterolem/trójglicerydami i stłuszczeniem wątroby (śledzenie badań krwi),
- powrót do regularnego treningu: bieganie (cele typu „dystans X do daty Y"), siłownia
  (powrót po przerwie, ochrona odcinka lędźwiowego), sporty rakietowe (tenis stołowy, squash),
- trener AI (Claude API), który planuje tygodnie treningowe i koryguje je po każdym treningu.

Aplikacja NIE trafia do App Store — działa jako PWA instalowana z przeglądarki.

## 2. Użytkownicy i profile

Aplikacja obsługuje **dokładnie dwoje użytkowników** (konta zakładane seedem/komendą,
bez publicznej rejestracji). Profil użytkownika (wiek, wzrost, cele, kontuzje, preferencje
żywieniowe) to **dane per użytkownik wypełniane w aplikacji**, nie stałe w kodzie —
poniższy profil użytkownika głównego służy jako kontekst domenowy i dane startowe.

**Zasady współdzielenia:**
- **Prywatne (każde widzi tylko swoje):** waga, obwody, badania krwi, ciśnienie, leki,
  treningi, plany treningowe, cele, streaki, komentarze AI.
- **Wspólne:** baza posiłków, rozpiski dnia (wspólne dania, osobne gramatury porcji),
  lista zakupów, profil żywieniowy gospodarstwa częściowo (wykluczenia obojga
  respektowane przy generacji).
- **Budżet AI: wspólny limit** dla całej aplikacji (domyślnie 5 USD/mies., edytowalny),
  panel kosztów z podziałem per użytkownik.
- Treningi „razem" (wspólne bieganie, siłownia w tym samym czasie): każde loguje swój
  trening niezależnie — plany są osobne (różne cele → różne zestawy ćwiczeń), wspólny
  jest co najwyżej termin, który każde podaje w swojej dostępności.

### 2.1 Profil użytkownika głównego (kontekst domenowy — kluczowy dla trenera AI)

- Mężczyzna, 35 lat, 186 cm, ~101 kg (start), cel ~80 kg.
- **Bieganie:** sporadyczne; przygotowywał się do biegu na 7,5 km (wrzesień 2026) z pomocą
  claude.ai w trybie „raport po treningu → kolejne kroki" — ten flow jest wzorcem dla aplikacji.
- **Siłownia:** łącznie ~4 lata stażu, ale ostatni trening 3 lata temu → traktować jako
  początkującego. **Przebyta kontuzja dolnego odcinka kręgosłupa (lędźwie) przy martwym ciągu.**
  Obawa przed nawrotem (częściowo psychiczna). Zasada nadrzędna: odbudowa na lekkich ciężarach,
  powolna progresja, monitoring bólu pleców po każdym treningu.
- **Sporty:** tenis stołowy regularnie 1×/tydz. po 1,5 h; możliwy squash w przyszłości.
- **Zdrowie:** wysoki cholesterol/trójglicerydy, stłuszczenie wątroby. Ciśnienie zwykle
  120–130/80 (bez zdiagnozowanego nadciśnienia, ale prowadzimy ewidencję).
- **Dostępność treningowa:** zmienna tydzień do tygodnia → plan tygodnia zawsze zaczyna się
  od pytania o dostępne dni.
- **Sprzęt:** Apple Watch (biega bez telefonu), konto Strava — wszystkie aktywności
  (biegi + sporty) trafiają do Stravy po synchronizacji z telefonem.
- **Kompetencje techniczne:** programista, stack dzienny Vue + Laravel + Inertia + MySQL —
  chce móc samodzielnie utrzymywać i rozwijać kod.

### 2.2 Profil drugiego użytkownika (partnerka)

- Pełne, lustrzane konto: własny dashboard, waga, badania, ciśnienie, cele, plany
  treningowe od AI — wszystkie moduły.
- Profil (wiek, wzrost, waga, cele, ewentualne kontuzje, preferencje żywieniowe)
  wypełni w aplikacji po starcie.
- **Źródło jej treningów: jeszcze nieustalone** → połączenie Stravy jest per użytkownik
  i opcjonalne; ręczny wpis treningu (bieg/sport) zawsze dostępny dla obojga.

## 3. Decyzje projektowe (zatwierdzone)

| # | Decyzja | Wybór |
|---|---------|-------|
| 1 | Architektura | Monolit: Laravel 12 + Inertia.js + Vue 3 (Composition API) + MySQL 8 + Tailwind CSS |
| 2 | Telefon | PWA (manifest + service worker, vite-plugin-pwa); instalacja przez Safari „Dodaj do ekranu początkowego" |
| 3 | Źródło biegów i sportów | Strava API (Apple Watch → Strava → aplikacja); **przycisk ręcznej synchronizacji**, nie webhook (webhook = opcjonalne usprawnienie na później). Połączenie Strava **per użytkownik, opcjonalne**; ręczny wpis treningu zawsze dostępny |
| 4 | Siłownia | Live-logger w aplikacji na telefonie (telefon zawsze przy sobie na siłowni) |
| 5 | AI | Claude API; tryb „auto-plan + komentarz użytkownika" (nie wolny czat); twardy budżet miesięczny **wspólny dla obojga użytkowników** (domyślnie 5 USD), realne liczenie kosztów per token, panel z podziałem kosztów per użytkownik |
| 6 | Odżywianie | BEZ dziennika zjedzonych posiłków; waga dzienna + trend + cel kaloryczny/białkowy wyznaczany i korygowany przez AI wg tempa spadku wagi; do tego **planowanie posiłków w przód** (moduł 4.8: generator rozpiski dnia z pętlą akceptacji i rosnącą bazą posiłków) |
| 7 | Badania krwi | Formularz ręczny (bez PDF), **z wyborem daty — także wstecznej** (użytkownik uzupełni całą historię wyników) |
| 8 | Dostęp | Publiczna domena + HTTPS, logowanie hasłem (**dwa konta**, bez publicznej rejestracji — konta zakładane seedem), długa sesja na zaufanych urządzeniach, rate-limit na login |
| 14 | Użytkownicy | **Dokładnie 2 konta (para).** Pełna separacja danych osobistych (każde widzi tylko swoje: treningi, waga, zdrowie, cele); wspólne: baza posiłków, rozpiski dnia, lista zakupów, budżet AI. Scoping po `user_id` wbudowany od M0 |
| 9 | Język i jednostki | Polski UI, jednostki metryczne (kg, km, min/km) |
| 10 | Deploy | Docker Compose na VPS (app, MySQL, worker/scheduler); wybór VPS i domeny odłożony do milestone'u M7 |
| 11 | Kolejki/harmonogram | Natywny Laravel: queue driver `database` + scheduler w cronie (bez Redisa — YAGNI) |
| 12 | Wykresy | Chart.js |
| 13 | Backup | Codzienny `mysqldump` cronem, retencja 30 dni, udokumentowana procedura odtworzenia |

**Świadomie POZA zakresem (YAGNI):** dziennik posiłków z bazą produktów, zdjęcia sylwetki,
biblioteka instruktażowa ćwiczeń, pełny tryb offline, webhook Stravy (opcjonalnie później),
więcej niż 2 użytkowników / publiczna rejestracja / role i uprawnienia, aplikacja natywna,
upload PDF badań, wspólne planowanie treningów par (plany zawsze osobne).

## 4. Moduły aplikacji

### 4.1 Dashboard („centrum dowodzenia")

Pierwszy ekran po otwarciu — **per użytkownik** (każde widzi swój). Sekcje:

- **Dziś:** co w planie (bieg / siłownia / sport / odpoczynek) + przycisk startu sesji
  (siłownia → live-logger; bieg → skrót do synchronizacji Stravy po powrocie).
- **Przycisk „Pobierz ze Stravy"** — główny punkt synchronizacji.
- **Waga:** aktualna średnia 7-dniowa, trend tygodniowy (np. „↓0,4 kg"), dystans do celu 80 kg.
- **Cel biegowy:** pasek postępu aktywnego celu (np. „7,5 km do 13.09 — na dobrej drodze").
- **Zdrowie:** ostatnie ciśnienie, liczba dni do następnych badań kontrolnych.
- **Streak:** seria dni z ważeniem, tygodnie ze zrealizowanym planem.

### 4.2 Trening biegowy

- Cele: „dystans X do daty Y, opcjonalnie w czasie Z" (model generyczny; pierwszy realny cel
  zostanie ustawiony przez użytkownika po starcie aplikacji). Statusy: aktywny/osiągnięty/porzucony.
- Plan tygodnia od AI (interwały, spokojne wybiegania, marszobiegi — adekwatnie do poziomu).
- Import ze Stravy (patrz 5. Integracja Strava): dystans, czas, tempo, tętno śr./max.
- Po imporcie biegu: prośba o opcjonalny komentarz użytkownika („kolano ciągnęło",
  „czułem się mocny") + ocena samopoczucia 1–5 → wyzwala korektę planu przez AI.
- Historia z wykresami: dystans/tempo/tętno w czasie, tygodniowy kilometraż.

### 4.3 Siłownia

- Plan treningu od AI z **regułą nadrzędną: ochrona odcinka lędźwiowego** — start na lekkich
  ciężarach, progresja tylko przy braku sygnałów bólowych, ćwiczenia oznaczone flagą ryzyka
  dla lędźwi (np. martwy ciąg klasyczny) wprowadzane ostrożnie lub zastępowane bezpieczniejszymi
  wariantami (hip thrust, martwy na gumach/kettlebell itp.).
- **Live-logger** (ekran mobilny, „na spoconą rękę"): duże przyciski, lista zaplanowanych
  ćwiczeń/serii z ciężarami, odhaczanie serii, timer przerw, podpowiedź ciężaru z poprzedniego
  wykonania. Odporność na słaby zasięg: zapisy trafiają do kolejki w localStorage
  i synchronizują się po odzyskaniu sieci (optymistyczne UI).
- Po treningu: **ocena pleców 0–10** (dziennik bólu — AI widzi trend i hamuje progresję),
  ocena samopoczucia 1–5, opcjonalny komentarz → korekta planu przez AI.
- Historia progresji per ćwiczenie (ciężar × powtórzenia w czasie, szacowany 1RM opcjonalnie).

### 4.4 Sporty (tenis stołowy, squash, inne)

- Importowane ze Stravy jak biegi (typ aktywności Stravy ≠ Run → `sport` z podtypem);
  czas trwania, kcal, tętno. Intensywność wyliczana z tętna/kcal.
- Ręczny wpis jako furtka awaryjna (czas, intensywność 1–5, komentarz).
- AI traktuje sesje sportowe jako obciążenie treningowe przy układaniu planu tygodnia
  (np. 1,5 h tenisa we wtorek → bez ciężkiego akcentu w środę).

### 4.5 Ciało

- Dzienny wpis wagi — jedno pole, maksymalnie szybki (cel: <5 sekund od otwarcia apki).
- Tygodniowy pomiar obwodu pasa (opcjonalne pole — kluczowy wskaźnik tłuszczu trzewnego
  przy stłuszczeniu wątroby).
- Wykresy: waga dzienna + średnia 7-dniowa + linia celu; obwód pasa w czasie.

### 4.6 Zdrowie

- **Badania krwi:** formularz z **datą wykonania (domyślnie dziś, możliwa dowolna data
  wsteczna — użytkownik uzupełni pełną historię)**. Markery predefiniowane: cholesterol
  całkowity, LDL, HDL, trójglicerydy, ALT, AST, GGTP, glukoza — plus możliwość dodania
  własnego markera (nazwa, jednostka, norma). Wykresy trendów z zaznaczonymi normami.
- **Przypomnienia o badaniach:** konfigurowalny interwał (np. lipidogram co 3 miesiące) →
  odliczanie na dashboardzie + push.
- **Komentarz AI do badań:** po wpisaniu wyników AI omawia trend na tle wagi i treningów.
  Zawsze z zastrzeżeniem: „to nie jest porada lekarska".
- **Dziennik ciśnienia:** data + pora, skurczowe/rozkurczowe/tętno spoczynkowe; wykres,
  średnie tygodniowe.
- **Leki i suplementy:** nazwa, dawka, od kiedy, status aktywny/odstawiony — kontekst dla AI
  i historia dla lekarza.
- **Raport dla lekarza (PDF):** eksport trendów wagi, pasa, ciśnienia, markerów krwi
  i podsumowania aktywności z ostatnich N miesięcy.

### 4.7 Trener AI

- **Planowanie tygodnia** (domyślnie niedziela, konfigurowalne): push → użytkownik podaje
  dostępne dni + **opcjonalne uwagi dla trenera** (pole zawsze obecne) → AI zwraca plan
  tygodnia jako ustrukturyzowany JSON (sesje zapisywane od razu jako `workouts` planned)
  + czytelne uzasadnienie „dlaczego taki plan" + cel kaloryczny/białkowy na tydzień.
- **Korekta po treningu:** automatyczny raport z danych (Strava/live-logger) + komentarz
  użytkownika → krótka odpowiedź AI (feedback + ewentualna modyfikacja pozostałych sesji
  tygodnia, np. „plecy 6/10 → zamieniam czwartkowy martwy ciąg na hip thrust").
- **Korekta kalorii:** co tydzień przy planowaniu AI porównuje tempo spadku wagi
  (śr. 7-dniowa vs poprzedni tydzień) z celem 0,4–0,7 kg/tydz. i koryguje kalorie/białko.
- **Komentarz do badań krwi** (patrz 4.6).
- **Wszystko powyżej działa per użytkownik** — każde z dwojga ma własnego „trenera":
  własne plany tygodnia, korekty i komentarze, budowane wyłącznie z jego danych.
- **Pakiet kontekstu** budowany programistycznie z bazy (NIE historia czatu): profil +
  kontuzje, aktywne cele, skrót ostatnich 4 tygodni treningów, trend wagi/pasa, ostatnie
  badania, trend bólu pleców, leki, uwagi użytkownika — dane wyłącznie tego użytkownika,
  którego dotyczy wywołanie (wyjątek: generator posiłków łączy cele i profile żywieniowe
  obojga). Szacunkowo 3–5 tys. tokenów in, 1–2 tys. out na wywołanie.
- **Budżet:** każde wywołanie logowane z dokładną liczbą tokenów (z odpowiedzi API) ×
  cennik modelu (tabela cen w konfiguracji aplikacji, aktualizowalna). Panel kosztów
  bieżącego miesiąca. Przy 80% limitu → push „budżet się kończy — zasil konto". Przy 100% →
  AI wyłączone do końca miesiąca (plan ręczny lub kopia poprzedniego tygodnia jako fallback).
  Limit domyślny: 5 USD/mies., edytowalny w ustawieniach.
- **Generowanie rozpisek posiłków** (patrz 4.8) — przechodzi przez ten sam licznik budżetu;
  regeneracja obejmuje tylko odrzucone posiłki.
- Model: klasa Sonnet (aktualny model Sonnet w momencie implementacji); szacowany koszt
  realny: treningi ~1–2 USD/mies. (×2 użytkowników) + posiłki ~0,5–1 USD/mies.
  (rozpiska jest wspólna — jedna generacja obsługuje oboje, ~2–4 tys. tokenów).
  Łącznie ~2–3 USD/mies. — nadal z zapasem w limicie 5 USD.

### 4.8 Posiłki (planowanie, nie rozliczanie) — moduł WSPÓLNY dla obojga

Zasada: planujemy jutro pod cele kaloryczne — NIE prowadzimy dziennika zjedzonych posiłków.
Para gotuje i je razem, ale ma różne zapotrzebowanie → **wspólne dania, osobne gramatury**.

- **Generator rozpiski dnia:** dowolne z dwojga podaje liczbę posiłków (np. 4) + opcjonalne
  pole „co mam w lodówce" → AI generuje **jedną wspólną rozpiskę na następny dzień**:
  każdy posiłek z nazwą, krótkim przepisem, czasem przygotowania oraz wartościami
  odżywczymi **kcal + makra (białko/tłuszcze/węglowodany) per 100 g**, a do tego
  **gramaturą porcji osobno dla każdego użytkownika** — wyliczoną tak, by suma porcji
  dnia trafiała w cel kaloryczny i białkowy KAŻDEGO z nich z jego aktualnego planu
  tygodnia (tłuszcze/węgle jako wartości wynikowe do wglądu). Generator respektuje
  wykluczenia i alergie **obojga** oraz dzień treningowy **każdego z osobna**
  (np. on biega, ona ma odpoczynek → te same dania, inne gramatury/dodatki).
- **Pętla akceptacji:** przy każdym posiłku ✓ pasuje / ✗ nie pasuje → przycisk
  „Zaproponuj alternatywę" regeneruje **wyłącznie odrzucone** posiłki (zaakceptowane
  pozostają bez zmian — mniejsze zużycie tokenów). Iteracja aż wszystko pasuje →
  „Akceptuję" → wszystkie posiłki z rozpiski trafiają do wspólnej bazy posiłków.
  Pętlę prowadzi osoba, która wygenerowała rozpiskę (druga widzi wynik — bez
  wymogu podwójnej akceptacji, YAGNI).
- **Baza posiłków (wspólna) jako paliwo generatora:** rośnie z każdą akceptacją; AI przy
  kolejnych generacjach miksuje posiłki z bazy (sprawdzone, lubiane) z nowymi propozycjami.
  Posiłki mają licznik użyć i ocenę „lubię"; ponowna akceptacja posiłku z bazy podbija licznik.
- **Profil żywieniowy per użytkownik** (ustawienia, jednorazowo): wykluczenia, alergie,
  preferencje (np. „max 30 min gotowania"). Generator łączy profile obojga. Na stałe
  w promptach: orientacja pro-wątrobowa i pro-lipidowa (ograniczenie cukrów prostych
  i tłuszczów nasyconych, błonnik, omega-3).
- **Świadomość dnia treningowego per użytkownik:** rozpiska na dzień biegowy/siłowy ≠ dzień
  odpoczynku (węglowodany okołotreningowe, rozkład białka) — AI zna plany tygodnia obojga.
- **Lista zakupów:** po akceptacji rozpiski jeden klik generuje zagregowaną listę składników
  **zsumowaną dla obu porcji** (dla jednego lub kilku zaplanowanych dni).

Poza zakresem: odhaczanie zjedzonych posiłków, zdjęcia, skanowanie kodów, stany magazynowe.

### 4.9 Powiadomienia (Web Push, VAPID — działa w PWA od iOS 16.4)

- Rano w dzień treningowy: „dziś w planie: …".
- Poranne przypomnienie o ważeniu / pomiarze ciśnienia.
- Zbliżający się termin badań kontrolnych.
- Niedziela: „zaplanujmy tydzień" + wieczorne podsumowanie tygodnia (waga, treningi,
  realizacja planu).
- Alert budżetu AI (80%).
- Godziny powiadomień konfigurowalne w ustawieniach.

### 4.10 Motywacja

- Streaki: dni z ważeniem, tygodnie ze zrealizowanym planem.
- Kamienie milowe: „-5 kg", „pierwsze 5 km bez zatrzymania", „10 treningów siłowych" itp.

## 5. Integracja Strava

- OAuth2 (jedna aplikacja API Stravy; client id/secret w `.env`). **Połączenie per
  użytkownik i opcjonalne:** każde z dwojga może podłączyć własne konto Strava
  (tokeny/refresh tokeny per użytkownik w bazie, odświeżane automatycznie);
  bez połączenia pozostaje ręczny wpis treningów.
- **Cykl użycia (zatwierdzony przez klienta):** bieg z Apple Watch (telefon w domu) →
  po powrocie synchronizacja zegarka do Stravy → w aplikacji klik „Pobierz ze Stravy" →
  import nowych aktywności → prośba o komentarz → korekta planu przez AI.
- Import pobiera aktywności od daty ostatniej synchronizacji; **deduplikacja przez unikalny
  indeks na `strava_activity_id`** (dubel technicznie niemożliwy).
- **Import historyczny:** przy pierwszym połączeniu konta pobierana jest cała historia
  aktywności (paginacja API). Do starych treningów można ręcznie dopisać komentarz/założenie.
- Mapowanie typów: `Run` → bieg; `Workout`/`Squash`/inne → `sport` z podtypem
  (tenis stołowy, squash, inne — słownik rozszerzalny).
- Zapisywane: dystans, czas, tempo, tętno śr./max, kcal + **surowy JSON aktywności**
  (nic nie tracimy na przyszłość).
- Webhook Stravy: świadomie poza zakresem MVP; opcjonalne usprawnienie po M7.

## 6. Model danych (MySQL, konwencje Laravel)

**Zasada nadrzędna:** wszystkie tabele danych osobistych (treningi, cele, plany, pomiary,
zdrowie, streaki, przypomnienia, subskrypcje push) mają kolumnę `user_id` i **globalny
scoping per zalogowany użytkownik od M0** (np. global scope w Eloquent + testy, że user A
nigdy nie zobaczy danych usera B). Wspólne (bez scopingu per użytkownik): `meals`,
`meal_plans` (+ pozycje), `exercises`, `lab_markers`, ustawienia globalne.

### Treningi
- `workouts` — wspólna tabela sesji: `user_id`, `type` (`run`|`gym`|`sport`), `sport_subtype` (nullable,
  słownik: table_tennis, squash, …), data, status (`planned`|`completed`|`skipped`),
  FK do `weekly_plans`, komentarz użytkownika, ocena samopoczucia 1–5,
  ocena pleców 0–10 (tylko gym, nullable).
- `runs` — 1:1 z workouts (type=run): dystans [m], czas [s], tempo śr., tętno śr./max,
  kcal, `strava_activity_id` (unique), `strava_raw` JSON.
- `sport_sessions` — 1:1 z workouts (type=sport): czas trwania, kcal, tętno śr.,
  intensywność (wyliczana lub ręczna 1–5), `strava_activity_id` (unique), `strava_raw` JSON.
- `exercises` — słownik ćwiczeń: nazwa, partia mięśniowa, `lumbar_risk` (flaga ryzyka dla
  lędźwi); zasilany przez AI przy pierwszym użyciu ćwiczenia w planie.
- `gym_exercises` — ćwiczenia w ramach workoutu (kolejność, FK exercises).
- `gym_sets` — serie: ciężar, powtórzenia, planowane vs wykonane, status.

### Planowanie i AI
- `training_goals` — typ (`run_distance`: dystans+data+opcjonalny czas docelowy;
  `weight`: kg+data), status (`active`|`achieved`|`abandoned`), postęp.
- `weekly_plans` — tydzień (data początku), dostępność podana przez użytkownika (dni),
  uwagi użytkownika, cel kaloryczny + białkowy, uzasadnienie AI (tekst), status.
- `ai_interactions` — `user_id` (inicjator wywołania), cel wywołania
  (`weekly_plan`|`workout_adjustment`|`lab_comment`|`meal_plan`|…), model, tokeny in/out,
  **koszt USD** (liczony z tokenów × cennik), skrót żądania, pełna odpowiedź, timestamp.
  Źródło prawdy dla wspólnego budżetu; podział kosztów per użytkownik z `user_id`.

### Posiłki
- `meals` — baza posiłków: nazwa, przepis (krótki), **wartości per 100 g: kcal,
  białko/tłuszcze/węglowodany [g]**, domyślna gramatura porcji [g] (wartości porcji
  wyliczane, nie przechowywane), czas przygotowania [min], tagi, licznik użyć,
  ocena „lubię" (bool).
- `meal_plans` — rozpiska dnia: data, cel kcal/białko, liczba posiłków, notatka
  „co mam w lodówce", status (`draft`|`accepted`).
- `meal_plan_items` — pozycje rozpiski: FK meal_plans, FK meals (po akceptacji),
  treść propozycji (przed zapisem do bazy), flaga zaakceptowany/odrzucony, kolejność.
- `meal_plan_item_portions` — **gramatura porcji per użytkownik** dla każdej pozycji:
  FK meal_plan_items, FK users, gramy. Tak generator dopina sumę dnia do celu KAŻDEGO
  z osobna (to samo danie, różne porcje).

### Ciało i zdrowie
- `body_measurements` — data (unique), waga [kg], obwód pasa [cm] (nullable).
- `blood_pressure_readings` — data+pora, skurczowe, rozkurczowe, tętno.
- `lab_markers` — słownik: nazwa, jednostka, norma min/max, predefiniowane + własne.
- `lab_results` — badanie: **data wykonania (dowolna, także wsteczna)**, notatka.
- `lab_values` — wartości: FK lab_results, FK lab_markers, wartość.
- `medications` — nazwa, dawka, data rozpoczęcia, data odstawienia (nullable).
- `reminders` — typ (badanie/ważenie/…), interwał lub data, ostatnie wykonanie.

### Systemowe
- `users` — dokładnie dwa rekordy (seed, bez rejestracji); standardowy auth Laravela.
- `profiles` — 1:1 z users: wiek, wzrost, płeć, waga docelowa, opis kontuzji/ograniczeń,
  profil żywieniowy (wykluczenia, alergie, preferencje) — kontekst dla trenera AI.
- `strava_connections` — 1:1 z users, opcjonalne: athlete id, access/refresh token,
  data ostatniej synchronizacji.
- `push_subscriptions` — subskrypcje Web Push per użytkownik i urządzenie.
- `settings` — globalne klucz-wartość: **wspólny limit budżetu AI**, cennik modeli AI;
  per użytkownik: dzień planowania tygodnia, godziny powiadomień.
- `streaks` / `achievements` — per użytkownik: stan streaków i odblokowane kamienie milowe.

**Kluczowa zasada:** jedna tabela `workouts` dla wszystkich typów treningu — kalendarz,
plan tygodnia i statystyki mają jedno źródło; szczegóły w tabelach podrzędnych 1:1.

## 7. Warstwa techniczna

- **Stack:** Laravel 12, Inertia.js, Vue 3 (Composition API), MySQL 8, Tailwind CSS,
  Chart.js, vite-plugin-pwa.
- **PWA:** manifest, service worker, cache zasobów; live-logger odporny na słaby zasięg
  (kolejka mutacji w localStorage + retry). Pełny offline poza loggerem — poza zakresem.
- **Push:** Web Push VAPID (bez konta Apple Developer), wysyłka z Laravela
  (pakiet laravel-notification-channels/webpush lub odpowiednik), scheduler w cronie.
- **Kolejki:** queue driver `database`, jeden worker w osobnym kontenerze.
- **Bezpieczeństwo:** logowanie hasłem, długa sesja („zapamiętaj mnie"), rate-limit na login,
  HTTPS (Caddy lub nginx+certbot z auto-odnowieniem), nagłówki bezpieczeństwa,
  sekrety (Strava, Claude API) wyłącznie w `.env`.
- **Deploy:** Docker Compose — kontenery: app (PHP-FPM + nginx), mysql, worker/scheduler.
  Deploy = `git pull && docker compose up -d --build`. Wymagania VPS: 1–2 GB RAM.
- **Backup:** codzienny `mysqldump` cronem, retencja 30 dni, udokumentowana i przetestowana
  procedura odtworzenia (test odtworzenia = kryterium akceptacji M7).
- **Dev:** środowisko lokalne (Sail lub Herd), seedy z danymi przykładowymi,
  testy Pest dla logiki domenowej: liczenie budżetu AI, deduplikacja importu Stravy,
  wyliczanie trendów wagi, logika streaków, korekta kalorii.
- **Język/jednostki:** polski UI, metryka (kg, km, min/km, cm, mmHg, mg/dl).

## 8. Milestony

Kolejność ułożona tak, by każdy milestone kończył się realną wartością dla użytkownika,
a dane zbierały się od pierwszego dnia (AI w M4 startuje z kilkoma tygodniami historii).

### M0 — Fundament
Repo, Laravel + Inertia + Vue + Tailwind, Docker Compose (dev), auth (**dwa konta z seeda,
bez rejestracji**), **scoping danych po `user_id`** (global scope + testy izolacji danych),
profile użytkowników (edycja: wiek, wzrost, cele, kontuzje, preferencje żywieniowe),
layout aplikacji, PWA instalowalna na iPhone, pusty dashboard.
**Kryterium ukończenia:** aplikacja zainstalowana na ekranie głównym iPhone'a; oboje mogą
się zalogować; test dowodzi, że user A nie widzi danych usera B.

### M1 — Ciało i zdrowie
Waga + obwód pasa z wykresami (śr. 7-dniowa, linia celu), ciśnienie, badania krwi
(formularz, markery predefiniowane + własne, daty wsteczne), leki/suplementy, dashboard v1
(waga, zdrowie), przypomnienia o badaniach (na razie tylko odliczanie na dashboardzie, bez push).
**Kryterium:** użytkownik wpisuje wagę w <5 s; pełna historia badań krwi uzupełniona wstecznie;
wykresy markerów z normami działają.

### M2 — Bieganie + Strava
OAuth Strava (**połączenie per użytkownik, opcjonalne**), przycisk „Pobierz ze Stravy",
import historyczny (pełna paginacja), deduplikacja, mapowanie typów (bieg/sport),
historia i wykresy biegów, **ręczny wpis biegu i sportu** (pełnoprawna ścieżka dla
użytkownika bez Stravy), cele biegowe z paskiem postępu, dashboard v2 (cel biegowy,
przycisk synchronizacji).
**Kryterium:** cała historia ze Stravy w bazie bez duplikatów (drugi import = 0 nowych
rekordów); nowy bieg pojawia się po jednym kliknięciu.

### M3 — Siłownia
Słownik ćwiczeń z flagą ryzyka lędźwiowego, tworzenie treningu (na razie ręczne — plany AI
dojdą w M4), live-logger (odhaczanie serii, timer przerw, podpowiedź poprzedniego ciężaru,
kolejka localStorage), ocena pleców 0–10 + trend, historia progresji per ćwiczenie.
**Kryterium:** pełny trening siłowy zalogowany wyłącznie telefonem, w tym przy chwilowym
braku zasięgu; wykres progresji ćwiczenia działa.

### M4 — Trener AI
Integracja Claude API, budowanie pakietu kontekstu z bazy, planowanie tygodnia
(dostępność + uwagi → plan JSON → rekordy workouts + uzasadnienie + cele kaloryczne),
korekta po treningu (bieg i siłownia), komentarz do badań, cotygodniowa korekta kalorii
wg tempa spadku wagi, licznik kosztów + twardy limit + fallback (plan ręczny / kopia tygodnia).
**Kryterium:** pełna pętla raport→plan działa; koszt każdego wywołania widoczny w panelu
i zgodny z fakturą Anthropic; po przekroczeniu limitu AI odmawia, aplikacja działa dalej.

### M5 — Posiłki (moduł wspólny)
Profile żywieniowe (per użytkownik), generator wspólnej rozpiski dnia (liczba posiłków +
„co mam w lodówce"), **gramatury porcji per użytkownik** dopinające cel kaloryczny/białkowy
każdego z osobna, pętla akceptacji z regeneracją wyłącznie odrzuconych posiłków, wspólna
baza posiłków (licznik użyć, ocena „lubię") zasilająca kolejne generacje, świadomość dnia
treningowego obojga, lista zakupów zsumowana dla obu porcji, wartości odżywcze per 100 g.
**Kryterium:** pełna pętla generuj → odrzuć część → alternatywy → akceptuj → posiłki w bazie
działa; regeneracja nie zmienia zaakceptowanych posiłków; suma porcji każdego użytkownika
= jego cel dnia (±5%), przy wspólnych daniach.

### M6 — Powiadomienia i motywacja
Web Push (subskrypcja z PWA na iOS), wszystkie powiadomienia z 4.9, niedzielne podsumowanie
tygodnia, streaki i kamienie milowe, raport PDF dla lekarza.
**Kryterium:** push dochodzi na iPhone przy zamkniętej aplikacji; raport PDF zawiera trendy
wagi/ciśnienia/markerów/aktywności za wybrany okres.

### M7 — Wdrożenie produkcyjne
Decyzje odłożone na ten etap: wybór VPS i domeny. HTTPS, deploy Docker Compose, migracja
danych z dev, backup cronem + **test odtworzenia z backupu**, rate-limit, przegląd bezpieczeństwa.
**Kryterium:** aplikacja dostępna 24/7 pod własną domeną z ważnym certyfikatem; odtworzenie
bazy z wczorajszego backupu przetestowane i udokumentowane.

Uwaga: M7 można przyspieszyć (wstawić po M1–M2), jeśli użytkownik zechce korzystać
z aplikacji „w terenie" przed ukończeniem całości.

## 9. Kwestie otwarte (decyzje na później)

- Wybór dostawcy VPS i domeny (etap M7).
- Docelowa nazwa aplikacji (robocza: „Centrum").
- Webhook Stravy (po M7, jeśli ręczna synchronizacja zacznie męczyć).
- Konkretny model Claude i aktualny cennik — do potwierdzenia w M4 (przyjąć aktualny model
  klasy Sonnet; cennik w konfiguracji, nie w kodzie).
- Pierwszy realny cel biegowy — użytkownik ustawi po starcie (bieg 7,5 km z września 2026
  prawdopodobnie odbędzie się przed ukończeniem aplikacji).
- Źródło treningów partnerki (własna Strava vs wpis ręczny) — do ustalenia po starcie;
  architektura wspiera oba warianty.

## 10. Wskazówki dla sesji implementacyjnej

- Implementować milestone'ami w kolejności M0→M7; każdy milestone = osobny plan implementacji
  i osobna weryfikacja kryteriów ukończenia z użytkownikiem.
- Użytkownik pracuje w tym stacku zawodowo — kod ma być idiomatyczny (konwencje Laravela,
  Form Requesty, Eloquent, Composition API), bez przemądrzałych abstrakcji.
- Logika domenowa (budżet AI, trendy, deduplikacja, streaki, korekta kalorii) — pokryta
  testami Pest przed implementacją UI.
- Wszystkie prompty do AI trzymać w dedykowanych klasach/plikach (łatwa iteracja),
  odpowiedzi planów wymuszać jako structured output/JSON.
- Komentarz AI do badań krwi zawsze z dopiskiem, że nie zastępuje porady lekarskiej.
