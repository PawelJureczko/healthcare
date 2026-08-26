# Centrum

Osobiste centrum dowodzenia zdrowiem i treningiem — aplikacja webowa (PWA) dla
dwojga użytkowników (para, osobne konta), self-hosted. Pełna specyfikacja:
[`specyfikacja.md`](specyfikacja.md). Plan i historia implementacji M0:
[`docs/superpowers/plans/2026-08-24-m0-fundament.md`](docs/superpowers/plans/2026-08-24-m0-fundament.md).

## Stack

Laravel 12 + Inertia.js + Vue 3 (Composition API) + MySQL 8 + Tailwind CSS,
Laravel Sail (Docker Compose) do dev, Pest do testów, vite-plugin-pwa.

## Uruchomienie lokalne

Wymagania: Docker Desktop (uruchomiony), Composer, PHP 8.2+ — tylko do
pierwszego `composer install` jeśli `vendor/` nie istnieje. Dalej wszystko
idzie przez kontener Saila.

```bash
# 1. Zależności (jeśli vendor/ lub node_modules/ nie istnieją)
composer install
cp .env.example .env
php artisan key:generate

# 2. Kontenery (app + MySQL)
./vendor/bin/sail up -d

# 3. Migracje + seed (zakłada dokładnie 2 konta)
./vendor/bin/sail artisan migrate --seed

# 4. Assety frontendowe
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev   # dev z hot-reload, albo `npm run build` na build produkcyjny
```

Aplikacja: http://localhost. Logowanie danymi z `.env`
(`SEED_USER_ONE_EMAIL` / `SEED_USER_ONE_PASSWORD`, analogicznie `_TWO_`) —
domyślne wartości w `.env.example` to `user1@centrum.local` /
`changeme123` i `user2@centrum.local` / `changeme123`. **Zmień je w swoim
`.env` przed jakimkolwiek wdrożeniem poza lokalny dev.**

Seed jest idempotentny — `migrate --seed` można odpalać wielokrotnie, zawsze
zostają dokładnie 2 konta.

### Testy

```bash
./vendor/bin/sail artisan test
```

### Częste komendy

```bash
./vendor/bin/sail artisan tinker      # REPL
./vendor/bin/sail artisan route:list  # lista tras
./vendor/bin/sail down                # zatrzymanie kontenerów
```

## Konwencje projektu (ustalone w M0 — czytaj przed M1+)

### Izolacja danych: `BelongsToUser`

Każdy model z danymi **osobistymi** (waga, treningi, badania, cele, streaki —
wszystko co w specyfikacji §6 jest oznaczone jako prywatne) musi używać
trait `App\Models\Concerns\BelongsToUser` (`app/Models/Concerns/BelongsToUser.php`):

```php
use App\Models\Concerns\BelongsToUser;

class Weight extends Model
{
    use BelongsToUser;
    // ...
}
```

Trait daje: globalny scope filtrujący po `user_id` zalogowanego użytkownika,
automatyczne przypisanie `user_id` przy tworzeniu rekordu, relację `user()`.

**Modele współdzielone NIE dostają tego traita** — spec §6 wymienia:
`meals`, `meal_plans` (+ pozycje), `exercises`, `lab_markers`, ustawienia
globalne (budżet AI, cennik modeli). Te tabele są celowo widoczne dla obojga.

**Ważne — scope jest no-opem poza kontekstem HTTP.** `UserOwnedScope` filtruje
tylko gdy `Auth::check()` zwraca `true`. W joba kolejki, komendzie
scheduler'a czy tinkerze bez zalogowanego usera scope nic nie filtruje —
`Model::all()` zwróci dane obojga. Trener AI (M4) i powiadomienia (M6) będą
działać właśnie w takich kontekstach (queue worker), więc tam trzeba
scopeować jawnie:

```php
Profile::forUser($user)->get();   // jawny, bezpieczny odpowiednik poza HTTP
```

`forUser()` jest zdefiniowany w `BelongsToUser` i dostępny na każdym modelu,
który go używa.

### Język UI

Cały tekst widoczny dla użytkownika — po polsku, od M0 w górę. Jednostki
metryczne (kg, km, min/km, cm, mmHg, mg/dl). Wyjątek świadomie odłożony:
komunikaty walidacji Laravela (`UpdateProfileDetailsRequest` i inne Form
Requesty) są nadal po angielsku — `APP_LOCALE` zostaje `en` do czasu
dodania `lang/pl/validation.php` (`php artisan lang:publish` + tłumaczenie).

### Breeze: `AuthenticatedLayout.vue`, nie `AppLayout.vue`

Zainstalowana wersja Breeze (2.4.2) generuje layout pod nazwą
`resources/js/Layouts/AuthenticatedLayout.vue`. Jeśli gdzieś w starszej
dokumentacji/planach pojawi się `AppLayout.vue` — to nazwa z założenia
planu, nieaktualna wobec realnie zainstalowanej wersji Breeze.

### Rate limiting logowania

Obsługiwany wyłącznie przez wbudowany limiter Breeze
(`app/Http/Requests/Auth/LoginRequest.php`) — nie dodawaj drugiego
`RateLimiter::for('login', ...)`. Wcześniejsza własna implementacja została
usunięta w finalnym review M0, bo liczyła też udane logowania i mogła
zablokować prawowitego użytkownika.

### Sekrety i konfiguracja

`env()` wolno wołać tylko wewnątrz plików w `config/` (np.
`config/seed.php`) — nigdzie indziej, bo po `php artisan config:cache` na
produkcji `env()` poza configiem zwraca `null`. Seed danych startowych czyta
przez `config('seed.*')`, nie `env()` bezpośrednio.

### Serializacja dat: `serializeDate()` na modelach z castem `date`

Domyślnie Laravel serializuje każdy atrybut z castem `date`/`datetime` do
JSON jako pełny znacznik ISO-8601 UTC (`2026-01-01T00:00:00.000000Z`) — źle
wygląda w polskojęzycznym, datowym (nie czasowym) UI. Modele z atrybutem
typu `date` dostają override:

```php
protected function serializeDate(\DateTimeInterface $date): string
{
    return $date->format('Y-m-d');
}
```

Ma to obecnie `BodyMeasurement`, `LabResult`, `Medication` (atrybuty typu
`date`) oraz `BloodPressureReading` (atrybut `measured_at` typu `datetime`
— dla M1 formatowany tak samo jako `Y-m-d`, bo wykres rysuje tylko po
dniach, a godzinę i tak wpisuje się osobno w formularzu). Nowy model z
atrybutem daty w M2+ powinien dostać ten sam override, jeśli trafia do
Inertia props.

### Strefa czasowa: `Europe/Warsaw`, nie UTC

`config/app.php` (`APP_TIMEZONE` w `.env`) ustawia `Europe/Warsaw` — to
wpływa na `now()`, `Carbon::today()` i domyślne daty wszędzie w backendzie.
Konsekwencja dla frontendu: **nie używaj `new Date().toISOString()`** do
podpowiadania dzisiejszej daty/godziny w formularzach — to zawsze UTC i
przy dobrym wietrze da inny dzień/godzinę niż lokalny zegar użytkownika.
Zamiast tego użyj `localDate()` / `localDateTime()` z
`resources/js/localDateTime.js`, które budują string z lokalnych getterów
(`getFullYear()`, `getHours()` itd.).

### Integracje zewnętrzne (OAuth2) — ręcznie przez `Http`, nie Socialite

Gdy potrzebna jest pełna kontrola nad przechowywaniem/odświeżaniem refresh
tokenów per użytkownik (jak w Strava), OAuth2 implementujemy ręcznie przez
`Http` facade w dedykowanej klasie klienta w `app/Services/<Dostawca>/`
(np. `App\Services\Strava\StravaClient`), a nie przez Socialite — Socialite
nie daje wygodnego dostępu do przechowywania/odświeżania refresh tokenów
per użytkownik w naszym modelu danych.

### Testy integracji zewnętrznych — zawsze `Http::fake()`

Żaden test nie może wykonywać prawdziwych wywołań sieciowych do
zewnętrznych API (Strava, docelowo Claude API w M4) — zawsze
`Http::fake([...])` + `Http::assertSent(...)`.

### Tabele podrzędne bez własnego `user_id`

Wzorzec izolacji tabel podrzędnych, użyty już trzykrotnie (`LabValue`,
`Run`, `SportSession`): tabela szczegółowa 1:1 lub N:1 z rodzicem
zawierającym dane osobiste NIE dostaje własnego `user_id`/`BelongsToUser`
— izolacja jest dziedziczona transytywnie, zawsze przez zapytanie przez
`forUser()` rodzica (np. `Workout::forUser($user)->with('run')`), nigdy
przez bezpośrednie zapytanie do tabeli podrzędnej poza wewnętrznym kodem
deduplikacji/lookupu.

## Milestone'y

M0 (fundament) i M1 (Ciało i zdrowie) ukończone, M2 (Bieganie + Strava)
ukończony — patrz plan wyżej po pełną listę zadań i decyzji. Kolejność
dalszych milestone'ów: M3 Siłownia → M4 Trener AI → M5 Posiłki →
M6 Powiadomienia i motywacja → M7 Wdrożenie produkcyjne. Szczegóły każdego
w `specyfikacja.md` §8.
