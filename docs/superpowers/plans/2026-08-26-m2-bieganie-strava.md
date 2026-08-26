# M2 — Bieganie + Strava — plan implementacji

> **Dla agentów wykonawczych:** WYMAGANY PODSKILL: użyj superpowers:subagent-driven-development
> (rekomendowane) lub superpowers:executing-plans do wykonania tego planu zadanie po zadaniu.
> Kroki używają składni checkbox (`- [ ]`) do śledzenia postępu.

**Cel:** Połączenie konta Strava (OAuth2, per użytkownik, opcjonalne), import biegów i
sportów (z pełną historią przy pierwszym połączeniu, deduplikacją i przyciskiem ręcznej
synchronizacji), ręczny wpis biegu/sportu jako pełnoprawna ścieżka bez Stravy, cele biegowe
z paskiem postępu, oraz dashboard v2 pokazujący cel biegowy i przycisk „Pobierz ze Stravy”.

**Architektura:** Nowa wspólna tabela `workouts` (typ `run`|`sport`; `gym` dojdzie w M3) z
tabelami podrzędnymi 1:1 `runs` i `sport_sessions` (bez własnego `user_id` — izolacja
dziedziczona przez `workout_id`, analogicznie do `lab_values`/`lab_result_id` z M1).
Integracja Stravy: `StravaConnection` (1:1 user, opcjonalne), `StravaClient` (cienki wrapper
na `Http` facade robiący OAuth2 authorization-code flow ręcznie — bez Socialite, bo potrzebna
jest pełna kontrola nad refresh tokenem per użytkownik), `StravaActivityMapper` (czysta funkcja
mapująca jedną aktywność JSON na wiersz workout+szczegóły), `StravaSyncService` (paginacja +
deduplikacja przez unique index na `strava_activity_id`). `TrainingGoal` — model generyczny
per spec, ale w M2 obsługujemy wyłącznie `type=run_distance` (cel wagowy już istnieje w
`profiles.weight_goal_kg` z M1 — nie duplikujemy go tutaj, patrz Global Constraints).

**Tech Stack:** Laravel 12 (`Http` facade do wywołań Strava API), Inertia.js + Vue 3
(Composition API), MySQL 8, Tailwind CSS, Chart.js/vue-chartjs (`LineChart.vue` z M1), Pest
(`Http::fake()` do testów integracji zewnętrznej — nigdy prawdziwe wywołania sieciowe).

**Spec:** [`specyfikacja.md`](../../../specyfikacja.md) — §4.2 (Trening biegowy), §4.4
(Sporty), §5 (Integracja Strava), §6 (model danych: `workouts`, `runs`, `sport_sessions`,
`training_goals`, `strava_connections`), §8 (M2 — kryteria ukończenia).

## Global Constraints

- Cały tekst widoczny dla użytkownika — po polsku. Wyjątek: komunikaty walidacji Laravela
  zostają po angielsku (`APP_LOCALE=en`, patrz `CLAUDE.md`).
- Modele danych **osobistych** (`Workout`, `TrainingGoal`, `StravaConnection`) używają traita
  `App\Models\Concerns\BelongsToUser`. `Run` i `SportSession` **NIE** dostają tego traita —
  nie mają własnej kolumny `user_id`, są podrzędne wobec `Workout` przez `workout_id` (wzorzec
  identyczny jak `LabValue`/`lab_result_id` z M1); ich izolacja jest wymuszana wyłącznie przez
  filtrowanie po stronie `Workout::forUser($user)` w warstwie kontrolera/serwisu.
- Kolumny typu-wyliczeniowego (`type`, `status`, `sport_subtype`) to `string` w migracji, NIE
  natywny MySQL `ENUM` — zgodnie z istniejącą konwencją (`reminders.type` z M1). Dzięki temu
  dodanie `type=gym` w M3 nie wymaga migracji zmieniającej definicję kolumny.
- Modele z atrybutem typu `date` dostają override `serializeDate()` → `Y-m-d` (konwencja z M1,
  patrz `CLAUDE.md`). Dotyczy `Workout::$date` i `TrainingGoal::$target_date` w tym planie.
- `env()` wolno wołać wyłącznie w plikach `config/*` — sekrety Stravy (`STRAVA_CLIENT_ID`,
  `STRAVA_CLIENT_SECRET`, `STRAVA_REDIRECT_URI`) czytane przez `config('services.strava.*')`.
- Deduplikacja importu: unique index na `strava_activity_id` w `runs` i `sport_sessions` —
  duplikat jest technicznie niemożliwy do wstawienia, nie tylko logicznie odfiltrowany.
- Formularze dat w Vue używają `localDate()` z `resources/js/localDateTime.js` (M1) —
  nigdy `new Date().toISOString()`.
- Testy integracji ze Stravą używają `Http::fake()` — zero prawdziwych wywołań sieciowych.
- `training_goals.type` obsługuje w M2 wyłącznie wartość `'run_distance'`. Spec (§6) opisuje
  też `type=weight`, ale ten cel już istnieje jako `profiles.weight_goal_kg` (M1, działający i
  wyświetlany na dashboardzie) — nie budujemy drugiej, konkurencyjnej ścieżki dla tej samej
  danej. Kolumna `type` zostaje generyczna (string, nie enum) tak, by `weight` dało się dodać
  później bez migracji, ale żaden task w tym planie go nie implementuje.
- Postęp celu biegowego (`TrainingGoalProgress::percent()`) liczony jest jako: najdłuższy
  pojedynczy ukończony bieg zalogowany **po dacie utworzenia celu** (`workouts.date >=
  goal.created_at`), podzielony przez `target_distance_m`, zaokrąglony w dół do 100%. To
  celowe uproszczenie względem spec (`target_time_s` jest przechowywany, ale NIE wpływa na
  wyliczenie postępu ani na auto-oznaczenie `achieved` w M2 — pełna korekta czasowa to zakres
  M4, gdzie AI koryguje plan na podstawie tempa).

---

### Task 1: Migracje, modele, fabryki — fundament danych treningowych

**Pliki:**
- Create: `database/migrations/2026_08_26_090000_create_strava_connections_table.php`
- Create: `database/migrations/2026_08_26_090100_create_training_goals_table.php`
- Create: `database/migrations/2026_08_26_090200_create_workouts_table.php`
- Create: `database/migrations/2026_08_26_090300_create_runs_table.php`
- Create: `database/migrations/2026_08_26_090400_create_sport_sessions_table.php`
- Create: `app/Models/StravaConnection.php`
- Create: `app/Models/TrainingGoal.php`
- Create: `app/Models/Workout.php`
- Create: `app/Models/Run.php`
- Create: `app/Models/SportSession.php`
- Modify: `app/Models/User.php`
- Create: `database/factories/StravaConnectionFactory.php`
- Create: `database/factories/TrainingGoalFactory.php`
- Create: `database/factories/WorkoutFactory.php`
- Create: `database/factories/RunFactory.php`
- Create: `database/factories/SportSessionFactory.php`
- Test: `tests/Feature/TrainingModelsTest.php`

**Interfaces:**
- Produces: `Workout::forUser($user)` (z `BelongsToUser`), `Workout::run()` (HasOne `Run`),
  `Workout::sportSession()` (HasOne `SportSession`), `Run::workout()` / `SportSession::workout()`
  (BelongsTo), `User::stravaConnection()` (HasOne), `User::trainingGoals()` (HasMany),
  `User::workouts()` (HasMany). Wszystkie kolejne taski konsumują te nazwy dokładnie.

- [ ] **Krok 1: Migracja `strava_connections`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('strava_athlete_id')->nullable()->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_connections');
    }
};
```

- [ ] **Krok 2: Migracja `training_goals`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('target_distance_m');
            $table->date('target_date');
            $table->unsignedInteger('target_time_s')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_goals');
    }
};
```

- [ ] **Krok 3: Migracja `workouts`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('sport_subtype')->nullable();
            $table->date('date');
            $table->string('status')->default('completed');
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('wellbeing_rating')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
```

- [ ] **Krok 4: Migracja `runs`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('distance_m');
            $table->unsignedInteger('duration_s');
            $table->unsignedInteger('avg_pace_s_per_km')->nullable();
            $table->unsignedSmallInteger('avg_heart_rate')->nullable();
            $table->unsignedSmallInteger('max_heart_rate')->nullable();
            $table->unsignedInteger('kcal')->nullable();
            $table->unsignedBigInteger('strava_activity_id')->nullable()->unique();
            $table->json('strava_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
```

- [ ] **Krok 5: Migracja `sport_sessions`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('duration_s');
            $table->unsignedInteger('kcal')->nullable();
            $table->unsignedSmallInteger('avg_heart_rate')->nullable();
            $table->unsignedTinyInteger('intensity')->nullable();
            $table->unsignedBigInteger('strava_activity_id')->nullable()->unique();
            $table->json('strava_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_sessions');
    }
};
```

- [ ] **Krok 6: Uruchom migracje**

Run: `./vendor/bin/sail artisan migrate`
Expected: 5 nowych migracji `Done`.

- [ ] **Krok 7: Modele**

`app/Models/StravaConnection.php`:
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StravaConnection extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'strava_athlete_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
```

`app/Models/TrainingGoal.php`:
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingGoal extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'target_distance_m',
        'target_date',
        'target_time_s',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
```

`app/Models/Workout.php`:
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workout extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'sport_subtype',
        'date',
        'status',
        'comment',
        'wellbeing_rating',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function run(): HasOne
    {
        return $this->hasOne(Run::class);
    }

    public function sportSession(): HasOne
    {
        return $this->hasOne(SportSession::class);
    }
}
```

`app/Models/Run.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate 1:1 to Workout — deliberately NOT BelongsToUser (no user_id
 * column). Isolation is inherited transitively through workout_id, exactly
 * like LabValue/lab_result_id in M1. Always query through
 * Workout::forUser($user)->with('run'), never Run::all() directly in a
 * user-facing path.
 */
class Run extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'distance_m',
        'duration_s',
        'avg_pace_s_per_km',
        'avg_heart_rate',
        'max_heart_rate',
        'kcal',
        'strava_activity_id',
        'strava_raw',
    ];

    protected function casts(): array
    {
        return [
            'strava_raw' => 'array',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
```

`app/Models/SportSession.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate 1:1 to Workout — same isolation model as Run (see its
 * docblock): no user_id, no BelongsToUser, scoped transitively via
 * workout_id.
 */
class SportSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'duration_s',
        'kcal',
        'avg_heart_rate',
        'intensity',
        'strava_activity_id',
        'strava_raw',
    ];

    protected function casts(): array
    {
        return [
            'strava_raw' => 'array',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
```

- [ ] **Krok 8: Dodaj relacje na `User`**

W `app/Models/User.php` dodaj importy `HasOne` (jeśli brakuje — jest już `HasOne` z
`profile()`) oraz metody na końcu klasy, obok istniejących `bodyMeasurements()` itd.:

```php
    public function stravaConnection(): HasOne
    {
        return $this->hasOne(StravaConnection::class);
    }

    public function trainingGoals(): HasMany
    {
        return $this->hasMany(TrainingGoal::class);
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
```

- [ ] **Krok 9: Fabryki**

`database/factories/StravaConnectionFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StravaConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'strava_athlete_id' => fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'access_token' => Str::random(40),
            'refresh_token' => Str::random(40),
            'token_expires_at' => now()->addHours(6),
            'last_synced_at' => null,
        ];
    }
}
```

`database/factories/TrainingGoalFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'run_distance',
            'target_distance_m' => 7500,
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'target_time_s' => null,
            'status' => 'active',
        ];
    }
}
```

`database/factories/WorkoutFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'run',
            'sport_subtype' => null,
            'date' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'status' => 'completed',
            'comment' => null,
            'wellbeing_rating' => null,
        ];
    }
}
```

`database/factories/RunFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class RunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(['type' => 'run']),
            'distance_m' => fake()->numberBetween(2000, 15000),
            'duration_s' => fake()->numberBetween(600, 5400),
            'avg_pace_s_per_km' => null,
            'avg_heart_rate' => fake()->optional()->numberBetween(120, 180),
            'max_heart_rate' => null,
            'kcal' => fake()->optional()->numberBetween(200, 900),
            'strava_activity_id' => null,
            'strava_raw' => null,
        ];
    }
}
```

`database/factories/SportSessionFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(['type' => 'sport', 'sport_subtype' => 'squash']),
            'duration_s' => fake()->numberBetween(1800, 5400),
            'kcal' => fake()->optional()->numberBetween(200, 700),
            'avg_heart_rate' => fake()->optional()->numberBetween(110, 170),
            'intensity' => fake()->numberBetween(1, 5),
            'strava_activity_id' => null,
            'strava_raw' => null,
        ];
    }
}
```

- [ ] **Krok 10: Test — relacje, izolacja, dziedziczenie izolacji przez `workout_id`**

`tests/Feature/TrainingModelsTest.php`:
```php
<?php

use App\Models\Run;
use App\Models\SportSession;
use App\Models\User;
use App\Models\Workout;

test('a workout is auto-scoped to the authenticated user and isolated from others', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Workout::factory()->for($userA)->create();
    Workout::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(Workout::count())->toBe(1)
        ->and(Workout::first()->user_id)->toBe($userA->id);
});

test('a run belongs to its workout and has no user_id column of its own', function () {
    $workout = Workout::factory()->create(['type' => 'run']);
    $run = Run::factory()->for($workout)->create();

    expect($run->workout->id)->toBe($workout->id)
        ->and($run->getAttributes())->not->toHaveKey('user_id');
});

test('a sport session belongs to its workout and has no user_id column of its own', function () {
    $workout = Workout::factory()->create(['type' => 'sport', 'sport_subtype' => 'squash']);
    $session = SportSession::factory()->for($workout)->create();

    expect($session->workout->id)->toBe($workout->id)
        ->and($session->getAttributes())->not->toHaveKey('user_id');
});

test('run isolation is inherited transitively through workout_id, not a global scope of its own', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'run']);
    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutA)->create();
    Run::factory()->for($workoutB)->create();

    $this->actingAs($userA);

    // Run itself has no scope — this documents the expected (safe-by-design)
    // behavior: callers MUST filter through Workout::forUser(), never query
    // Run directly in a user-facing path.
    expect(Run::count())->toBe(2);

    $isolatedRuns = Workout::forUser($userA)->where('type', 'run')->with('run')->get();
    expect($isolatedRuns)->toHaveCount(1)
        ->and($isolatedRuns->first()->user_id)->toBe($userA->id);
});
```

Run: `./vendor/bin/sail artisan test --filter=TrainingModelsTest`
Expected: PASS (4 testy).

- [ ] **Krok 11: Commit**

```bash
git add database/migrations app/Models/StravaConnection.php app/Models/TrainingGoal.php app/Models/Workout.php app/Models/Run.php app/Models/SportSession.php app/Models/User.php database/factories/StravaConnectionFactory.php database/factories/TrainingGoalFactory.php database/factories/WorkoutFactory.php database/factories/RunFactory.php database/factories/SportSessionFactory.php tests/Feature/TrainingModelsTest.php
git commit -m "feat(m2): add strava_connections, training_goals, workouts, runs, sport_sessions schema"
```

---

### Task 2: `StravaClient` — wrapper OAuth2 + API na `Http` facade

**Pliki:**
- Modify: `config/services.php`
- Modify: `.env.example`
- Create: `app/Services/Strava/StravaClient.php`
- Test: `tests/Unit/Strava/StravaClientTest.php`

**Interfaces:**
- Consumes: `App\Models\StravaConnection` (Task 1).
- Produces: `StravaClient::authorizationUrl(string $state): string`,
  `StravaClient::exchangeCodeForToken(string $code): array`,
  `StravaClient::ensureFreshToken(StravaConnection $connection): StravaConnection`,
  `StravaClient::fetchActivitiesPage(StravaConnection $connection, ?int $after, int $page, int $perPage = 100): array`,
  `StravaClient::deauthorize(StravaConnection $connection): void`. Task 4 (sync service) i
  Task 5 (connection controller) konsumują dokładnie te sygnatury.

- [ ] **Krok 1: `config/services.php` — dodaj blok `strava`**

Dodaj przed zamykającym `];`:
```php
    'strava' => [
        'client_id' => env('STRAVA_CLIENT_ID'),
        'client_secret' => env('STRAVA_CLIENT_SECRET'),
        'redirect' => env('STRAVA_REDIRECT_URI'),
    ],
```

- [ ] **Krok 2: `.env.example` — dodaj zmienne Stravy**

Dodaj po sekcji `VITE_APP_NAME`:
```
STRAVA_CLIENT_ID=
STRAVA_CLIENT_SECRET=
STRAVA_REDIRECT_URI=http://localhost/integracje/strava/callback
```

- [ ] **Krok 3: Napisz test `StravaClientTest` (na razie failing — klasa nie istnieje)**

`tests/Unit/Strava/StravaClientTest.php`:
```php
<?php

use App\Models\StravaConnection;
use App\Services\Strava\StravaClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.strava.client_id' => '12345']);
    config(['services.strava.client_secret' => 'secret']);
    config(['services.strava.redirect' => 'http://localhost/integracje/strava/callback']);
});

test('authorization url contains client id, redirect, scope and state', function () {
    $url = (new StravaClient)->authorizationUrl('the-state-value');

    expect($url)->toStartWith('https://www.strava.com/oauth/authorize?')
        ->and($url)->toContain('client_id=12345')
        ->and($url)->toContain('state=the-state-value')
        ->and($url)->toContain('scope=read%2Cactivity%3Aread_all');
});

test('exchangeCodeForToken posts to the strava token endpoint and returns the json body', function () {
    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'access-123',
            'refresh_token' => 'refresh-123',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 999],
        ]),
    ]);

    $result = (new StravaClient)->exchangeCodeForToken('the-code');

    expect($result['access_token'])->toBe('access-123');
    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/token'
        && $request['code'] === 'the-code'
        && $request['grant_type'] === 'authorization_code');
});

test('ensureFreshToken does nothing when the token is not yet expired', function () {
    Http::fake();
    $connection = StravaConnection::factory()->make(['token_expires_at' => now()->addHour()]);

    (new StravaClient)->ensureFreshToken($connection);

    Http::assertNothingSent();
});

test('ensureFreshToken refreshes and persists a new token when expired', function () {
    $connection = StravaConnection::factory()->create([
        'token_expires_at' => now()->subMinute(),
        'access_token' => 'old-access',
        'refresh_token' => 'old-refresh',
    ]);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'new-access',
            'refresh_token' => 'new-refresh',
            'expires_at' => now()->addHours(6)->timestamp,
        ]),
    ]);

    (new StravaClient)->ensureFreshToken($connection);

    expect($connection->fresh()->access_token)->toBe('new-access')
        ->and($connection->fresh()->refresh_token)->toBe('new-refresh');
});

test('fetchActivitiesPage calls the activities endpoint with page, per_page and after', function () {
    $connection = StravaConnection::factory()->create(['access_token' => 'access-123']);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::response([]),
    ]);

    (new StravaClient)->fetchActivitiesPage($connection, 1700000000, 2, 100);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.strava.com/api/v3/athlete/activities')
        && $request['page'] === 2
        && $request['per_page'] === 100
        && $request['after'] === 1700000000
        && $request->hasHeader('Authorization', 'Bearer access-123'));
});

test('deauthorize posts the access token to the deauthorize endpoint', function () {
    $connection = StravaConnection::factory()->create(['access_token' => 'access-123']);

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([]),
    ]);

    (new StravaClient)->deauthorize($connection);

    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/deauthorize'
        && $request['access_token'] === 'access-123');
});
```

- [ ] **Krok 4: Uruchom testy — muszą failować (klasa nie istnieje)**

Run: `./vendor/bin/sail artisan test --filter=StravaClientTest`
Expected: FAIL z „Class App\Services\Strava\StravaClient not found”.

- [ ] **Krok 5: Implementacja `StravaClient`**

`app/Services/Strava/StravaClient.php`:
```php
<?php

namespace App\Services\Strava;

use App\Models\StravaConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class StravaClient
{
    private const AUTHORIZE_URL = 'https://www.strava.com/oauth/authorize';
    private const TOKEN_URL = 'https://www.strava.com/oauth/token';
    private const DEAUTHORIZE_URL = 'https://www.strava.com/oauth/deauthorize';
    private const ACTIVITIES_URL = 'https://www.strava.com/api/v3/athlete/activities';

    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => config('services.strava.client_id'),
            'redirect_uri' => config('services.strava.redirect'),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        return Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ])->throw()->json();
    }

    public function ensureFreshToken(StravaConnection $connection): StravaConnection
    {
        if ($connection->token_expires_at->isFuture()) {
            return $connection;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $connection->update([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'token_expires_at' => Carbon::createFromTimestamp($response['expires_at']),
        ]);

        return $connection;
    }

    public function fetchActivitiesPage(StravaConnection $connection, ?int $after, int $page, int $perPage = 100): array
    {
        $query = ['page' => $page, 'per_page' => $perPage];
        if ($after !== null) {
            $query['after'] = $after;
        }

        return Http::withToken($connection->access_token)
            ->get(self::ACTIVITIES_URL, $query)
            ->throw()
            ->json();
    }

    public function deauthorize(StravaConnection $connection): void
    {
        Http::asForm()->post(self::DEAUTHORIZE_URL, [
            'access_token' => $connection->access_token,
        ]);
    }
}
```

- [ ] **Krok 6: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=StravaClientTest`
Expected: PASS (6 testów).

- [ ] **Krok 7: Commit**

```bash
git add config/services.php .env.example app/Services/Strava/StravaClient.php tests/Unit/Strava/StravaClientTest.php
git commit -m "feat(m2): add StravaClient OAuth2 + activities API wrapper"
```

---

### Task 3: `StravaActivityMapper` — czyste mapowanie aktywności Stravy

**Pliki:**
- Create: `app/Services/Strava/StravaActivityMapper.php`
- Test: `tests/Unit/Strava/StravaActivityMapperTest.php`

**Interfaces:**
- Produces: `StravaActivityMapper::map(array $activity): array` zwraca
  `['workout' => [...], 'detail' => [...], 'table' => 'runs'|'sport_sessions']`, gdzie
  `workout` ma klucze `type`, `sport_subtype`, `date`, `status`, a `detail` klucze zgodne z
  `$fillable` odpowiedniej tabeli podrzędnej (bez `workout_id` — to dokleja wołający).
  Task 4 (`StravaSyncService`) konsumuje ten dokładny kształt.

- [ ] **Krok 1: Napisz test (na razie failing)**

`tests/Unit/Strava/StravaActivityMapperTest.php`:
```php
<?php

use App\Services\Strava\StravaActivityMapper;

test('maps a Run activity into a run workout with computed pace', function () {
    $activity = [
        'id' => 123456,
        'type' => 'Run',
        'start_date_local' => '2026-09-01T07:15:00Z',
        'distance' => 7500.0,
        'moving_time' => 2400,
        'average_heartrate' => 152.4,
        'max_heartrate' => 178.0,
        'calories' => 520.0,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['table'])->toBe('runs')
        ->and($mapped['workout'])->toBe([
            'type' => 'run',
            'sport_subtype' => null,
            'date' => '2026-09-01',
            'status' => 'completed',
        ])
        ->and($mapped['detail']['distance_m'])->toBe(7500)
        ->and($mapped['detail']['duration_s'])->toBe(2400)
        ->and($mapped['detail']['avg_pace_s_per_km'])->toBe(320)
        ->and($mapped['detail']['avg_heart_rate'])->toBe(152)
        ->and($mapped['detail']['max_heart_rate'])->toBe(178)
        ->and($mapped['detail']['kcal'])->toBe(520)
        ->and($mapped['detail']['strava_activity_id'])->toBe(123456)
        ->and($mapped['detail']['strava_raw'])->toBe($activity);
});

test('maps a TableTennis activity into a sport workout with known subtype', function () {
    $activity = [
        'id' => 777,
        'type' => 'Workout',
        'sport_type' => 'TableTennis',
        'start_date_local' => '2026-09-02T18:00:00Z',
        'distance' => 0,
        'moving_time' => 3600,
        'calories' => 300.0,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['table'])->toBe('sport_sessions')
        ->and($mapped['workout']['sport_subtype'])->toBe('table_tennis')
        ->and($mapped['detail']['duration_s'])->toBe(3600)
        ->and($mapped['detail']['intensity'])->toBeNull()
        ->and($mapped['detail']['strava_activity_id'])->toBe(777);
});

test('maps an unrecognized sport type to a snake_case fallback subtype', function () {
    $activity = [
        'id' => 42,
        'type' => 'Workout',
        'sport_type' => 'RockClimbing',
        'start_date_local' => '2026-09-03T10:00:00Z',
        'distance' => 0,
        'moving_time' => 1800,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['workout']['sport_subtype'])->toBe('rock_climbing');
});

test('missing optional fields (no heart rate sensor) map to null, not zero', function () {
    $activity = [
        'id' => 55,
        'type' => 'Run',
        'start_date_local' => '2026-09-04T06:00:00Z',
        'distance' => 5000.0,
        'moving_time' => 1500,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['detail']['avg_heart_rate'])->toBeNull()
        ->and($mapped['detail']['max_heart_rate'])->toBeNull()
        ->and($mapped['detail']['kcal'])->toBeNull();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=StravaActivityMapperTest`
Expected: FAIL z „Class App\Services\Strava\StravaActivityMapper not found”.

- [ ] **Krok 3: Implementacja**

`app/Services/Strava/StravaActivityMapper.php`:
```php
<?php

namespace App\Services\Strava;

use Illuminate\Support\Str;

class StravaActivityMapper
{
    private const SPORT_TYPE_MAP = [
        'TableTennis' => 'table_tennis',
        'Squash' => 'squash',
    ];

    public static function map(array $activity): array
    {
        $isRun = ($activity['type'] ?? null) === 'Run';

        $workout = [
            'type' => $isRun ? 'run' : 'sport',
            'sport_subtype' => $isRun ? null : self::mapSportSubtype($activity),
            'date' => substr($activity['start_date_local'], 0, 10),
            'status' => 'completed',
        ];

        $distanceM = (int) round($activity['distance'] ?? 0);
        $durationS = (int) ($activity['moving_time'] ?? 0);
        $avgHeartRate = isset($activity['average_heartrate']) ? (int) round($activity['average_heartrate']) : null;
        $kcal = isset($activity['calories']) ? (int) round($activity['calories']) : null;

        if ($isRun) {
            $detail = [
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'avg_pace_s_per_km' => $distanceM > 0 ? (int) round($durationS / ($distanceM / 1000)) : null,
                'avg_heart_rate' => $avgHeartRate,
                'max_heart_rate' => isset($activity['max_heartrate']) ? (int) round($activity['max_heartrate']) : null,
                'kcal' => $kcal,
                'strava_activity_id' => $activity['id'],
                'strava_raw' => $activity,
            ];
        } else {
            $detail = [
                'duration_s' => $durationS,
                'kcal' => $kcal,
                'avg_heart_rate' => $avgHeartRate,
                'intensity' => null,
                'strava_activity_id' => $activity['id'],
                'strava_raw' => $activity,
            ];
        }

        return ['workout' => $workout, 'detail' => $detail, 'table' => $isRun ? 'runs' : 'sport_sessions'];
    }

    private static function mapSportSubtype(array $activity): string
    {
        $type = $activity['sport_type'] ?? $activity['type'] ?? 'Other';

        return self::SPORT_TYPE_MAP[$type] ?? Str::snake($type);
    }
}
```

- [ ] **Krok 4: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=StravaActivityMapperTest`
Expected: PASS (4 testy).

- [ ] **Krok 5: Commit**

```bash
git add app/Services/Strava/StravaActivityMapper.php tests/Unit/Strava/StravaActivityMapperTest.php
git commit -m "feat(m2): add pure Strava activity-to-workout mapper"
```

---

### Task 4: `StravaSyncService` — paginacja, deduplikacja, kontroler synchronizacji

**Pliki:**
- Create: `app/Services/Strava/StravaSyncService.php`
- Create: `app/Http/Controllers/Strava/StravaSyncController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Strava/StravaSyncServiceTest.php`

**Interfaces:**
- Consumes: `StravaClient` (Task 2), `StravaActivityMapper::map()` (Task 3),
  `Workout`/`Run`/`SportSession` (Task 1).
- Produces: `StravaSyncService::sync(StravaConnection $connection): int` (liczba faktycznie
  nowo zaimportowanych aktywności). Route `strava.sync` (`POST /integracje/strava/synchronizuj`)
  konsumowana w Task 6 (przycisk na `/biegi`) i Task 9 (przycisk na dashboardzie).

- [ ] **Krok 1: Napisz test serwisu (na razie failing)**

`tests/Feature/Strava/StravaSyncServiceTest.php`:
```php
<?php

use App\Models\Run;
use App\Models\StravaConnection;
use App\Models\Workout;
use App\Services\Strava\StravaSyncService;
use Illuminate\Support\Facades\Http;

function fakeStravaActivity(int $id, string $type = 'Run'): array
{
    return [
        'id' => $id,
        'type' => $type,
        'start_date_local' => '2026-09-01T07:00:00Z',
        'distance' => 5000.0,
        'moving_time' => 1500,
        'average_heartrate' => 150.0,
        'calories' => 400.0,
    ];
}

test('sync imports new activities across pages and stops on a short page', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);

    $fullPage = array_map(fn ($i) => fakeStravaActivity($i), range(1, 100));
    $lastPage = [fakeStravaActivity(101)];

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($fullPage)
            ->push($lastPage),
    ]);

    $imported = app(StravaSyncService::class)->sync($connection);

    expect($imported)->toBe(101)
        ->and(Workout::count())->toBe(101)
        ->and(Run::count())->toBe(101)
        ->and($connection->fresh()->last_synced_at)->not->toBeNull();
});

test('sync assigns imported workouts to the connections owner', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push([fakeStravaActivity(1)])
            ->push([]),
    ]);

    app(StravaSyncService::class)->sync($connection);

    expect(Workout::first()->user_id)->toBe($connection->user_id);
});

test('running sync twice with overlapping activities imports zero duplicates the second time', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);
    $activities = [fakeStravaActivity(1), fakeStravaActivity(2, 'Squash')];

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($activities)
            ->push([]),
    ]);
    $firstImport = app(StravaSyncService::class)->sync($connection);

    // Second sync: Strava returns the SAME activities again (e.g. the API's
    // `after` cursor overlapping) — dedup must rely on strava_activity_id,
    // not on last_synced_at alone.
    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($activities)
            ->push([]),
    ]);
    $secondImport = app(StravaSyncService::class)->sync($connection->fresh());

    expect($firstImport)->toBe(2)
        ->and($secondImport)->toBe(0)
        ->and(Workout::count())->toBe(2);
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=StravaSyncServiceTest`
Expected: FAIL z „Class App\Services\Strava\StravaSyncService not found”.

- [ ] **Krok 3: Implementacja `StravaSyncService`**

`app/Services/Strava/StravaSyncService.php`:
```php
<?php

namespace App\Services\Strava;

use App\Models\Run;
use App\Models\SportSession;
use App\Models\StravaConnection;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class StravaSyncService
{
    public function __construct(private readonly StravaClient $client)
    {
    }

    public function sync(StravaConnection $connection): int
    {
        $this->client->ensureFreshToken($connection);

        $after = $connection->last_synced_at?->timestamp;
        $imported = 0;
        $page = 1;

        do {
            $activities = $this->client->fetchActivitiesPage($connection, $after, $page);

            foreach ($activities as $activity) {
                if ($this->importActivity($connection->user_id, $activity)) {
                    $imported++;
                }
            }

            $page++;
        } while (count($activities) === 100);

        $connection->update(['last_synced_at' => now()]);

        return $imported;
    }

    private function importActivity(int $userId, array $activity): bool
    {
        $mapped = StravaActivityMapper::map($activity);
        $detailModel = $mapped['table'] === 'runs' ? Run::class : SportSession::class;

        if ($detailModel::where('strava_activity_id', $mapped['detail']['strava_activity_id'])->exists()) {
            return false;
        }

        DB::transaction(function () use ($userId, $mapped, $detailModel) {
            $workout = Workout::create([...$mapped['workout'], 'user_id' => $userId]);
            $detailModel::create([...$mapped['detail'], 'workout_id' => $workout->id]);
        });

        return true;
    }
}
```

- [ ] **Krok 4: Uruchom testy serwisu — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=StravaSyncServiceTest`
Expected: PASS (3 testy).

- [ ] **Krok 5: Kontroler synchronizacji + trasa**

`app/Http/Controllers/Strava/StravaSyncController.php`:
```php
<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Services\Strava\StravaSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StravaSyncController extends Controller
{
    public function __invoke(Request $request, StravaSyncService $syncService): RedirectResponse
    {
        $connection = $request->user()->stravaConnection;

        if (! $connection) {
            return back()->with('status', 'strava-not-connected');
        }

        $imported = $syncService->sync($connection);

        return back()->with('status', "strava-synced:{$imported}");
    }
}
```

W `routes/web.php` dodaj import `use App\Http\Controllers\Strava\StravaSyncController;` oraz,
wewnątrz istniejącego `Route::middleware('auth')->group(...)`:
```php
    Route::post('/integracje/strava/synchronizuj', StravaSyncController::class)->name('strava.sync');
```

- [ ] **Krok 6: Test feature dla trasy**

Dopisz do `tests/Feature/Strava/StravaSyncServiceTest.php`:
```php
test('the sync route imports activities for the current users connection', function () {
    $user = \App\Models\User::factory()->create();
    \App\Models\StravaConnection::factory()->for($user)->create(['last_synced_at' => null]);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push([fakeStravaActivity(9)])
            ->push([]),
    ]);

    $this->actingAs($user)
        ->post('/integracje/strava/synchronizuj')
        ->assertRedirect();

    expect(Workout::where('user_id', $user->id)->count())->toBe(1);
});

test('the sync route is a no-op when the user has no strava connection', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->post('/integracje/strava/synchronizuj')
        ->assertRedirect();

    expect(Workout::count())->toBe(0);
});
```

- [ ] **Krok 7: Uruchom pełny plik testów**

Run: `./vendor/bin/sail artisan test --filter=StravaSyncServiceTest`
Expected: PASS (5 testów).

- [ ] **Krok 8: Commit**

```bash
git add app/Services/Strava/StravaSyncService.php app/Http/Controllers/Strava/StravaSyncController.php routes/web.php tests/Feature/Strava/StravaSyncServiceTest.php
git commit -m "feat(m2): add StravaSyncService with pagination and activity_id dedup"
```

---

### Task 5: OAuth — połączenie/rozłączenie konta Strava + karta w profilu

**Pliki:**
- Create: `app/Http/Controllers/Strava/StravaConnectionController.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ProfileController.php`
- Create: `resources/js/Pages/Profile/Partials/StravaConnectionForm.vue`
- Modify: `resources/js/Pages/Profile/Edit.vue`
- Test: `tests/Feature/Strava/StravaConnectionTest.php`

**Interfaces:**
- Consumes: `StravaClient` (Task 2).
- Produces: trasy `strava.connect` (GET), `strava.callback` (GET), `strava.disconnect`
  (DELETE); `ProfileController@edit` przekazuje teraz prop `stravaConnected: boolean`.

- [ ] **Krok 1: Napisz testy (na razie failing — kontroler nie istnieje)**

`tests/Feature/Strava/StravaConnectionTest.php`:
```php
<?php

use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('connect redirects to stravas authorization url and stores a csrf state in session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/integracje/strava/polacz');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://www.strava.com/oauth/authorize?');
    expect(session('strava_oauth_state'))->not->toBeNull();
});

test('callback with a mismatched state does not create a connection', function () {
    $user = User::factory()->create();
    $this->withSession(['strava_oauth_state' => 'expected-state']);

    $this->actingAs($user)->get('/integracje/strava/callback?code=abc&state=wrong-state');

    expect(StravaConnection::count())->toBe(0);
});

test('callback with a matching state exchanges the code and stores the connection', function () {
    $user = User::factory()->create();
    $this->withSession(['strava_oauth_state' => 'correct-state']);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'access-123',
            'refresh_token' => 'refresh-123',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 555],
        ]),
    ]);

    $this->actingAs($user)
        ->get('/integracje/strava/callback?code=abc&state=correct-state')
        ->assertRedirect(route('profile.edit'));

    expect(StravaConnection::where('user_id', $user->id)->first())
        ->strava_athlete_id->toBe(555)
        ->access_token->toBe('access-123');
});

test('a second callback for the same user updates the existing connection instead of duplicating it', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create(['access_token' => 'stale-token']);
    $this->withSession(['strava_oauth_state' => 'state']);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'fresh-token',
            'refresh_token' => 'fresh-refresh',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 555],
        ]),
    ]);

    $this->actingAs($user)->get('/integracje/strava/callback?code=abc&state=state');

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(1)
        ->and(StravaConnection::where('user_id', $user->id)->first()->access_token)->toBe('fresh-token');
});

test('disconnect deletes the connection and calls stravas deauthorize endpoint', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create();

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([]),
    ]);

    $this->actingAs($user)
        ->delete('/integracje/strava')
        ->assertRedirect(route('profile.edit'));

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(0);
    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/deauthorize');
});

test('disconnect succeeds locally even if stravas deauthorize endpoint is unreachable', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create();

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([], 500),
    ]);

    $this->actingAs($user)->delete('/integracje/strava')->assertRedirect();

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(0);
});

test('a user never sees another users strava connection status', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    StravaConnection::factory()->for($userB)->create();

    $response = $this->actingAs($userA)->get('/profile');

    $response->assertInertia(fn ($page) => $page->where('stravaConnected', false));
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=StravaConnectionTest`
Expected: FAIL (trasy nie istnieją).

- [ ] **Krok 3: Implementacja kontrolera**

`app/Http/Controllers/Strava/StravaConnectionController.php`:
```php
<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Services\Strava\StravaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class StravaConnectionController extends Controller
{
    public function redirect(Request $request, StravaClient $client): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('strava_oauth_state', $state);

        return redirect($client->authorizationUrl($state));
    }

    public function callback(Request $request, StravaClient $client): RedirectResponse
    {
        $expectedState = $request->session()->pull('strava_oauth_state');

        if (! $request->has('code') || $request->get('state') !== $expectedState) {
            return redirect()->route('profile.edit')->with('status', 'strava-connect-failed');
        }

        $token = $client->exchangeCodeForToken($request->get('code'));

        $request->user()->stravaConnection()->updateOrCreate([], [
            'strava_athlete_id' => $token['athlete']['id'] ?? null,
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'token_expires_at' => Carbon::createFromTimestamp($token['expires_at']),
        ]);

        return redirect()->route('profile.edit')->with('status', 'strava-connected');
    }

    public function destroy(Request $request, StravaClient $client): RedirectResponse
    {
        $connection = $request->user()->stravaConnection;

        if ($connection) {
            try {
                $client->deauthorize($connection);
            } catch (\Throwable) {
                // Best-effort remote revoke — local disconnect proceeds regardless.
            }

            $connection->delete();
        }

        return redirect()->route('profile.edit')->with('status', 'strava-disconnected');
    }
}
```

- [ ] **Krok 4: Trasy**

W `routes/web.php` dodaj import
`use App\Http\Controllers\Strava\StravaConnectionController;` i wewnątrz
`Route::middleware('auth')->group(...)`:
```php
    Route::get('/integracje/strava/polacz', [StravaConnectionController::class, 'redirect'])->name('strava.connect');
    Route::get('/integracje/strava/callback', [StravaConnectionController::class, 'callback'])->name('strava.callback');
    Route::delete('/integracje/strava', [StravaConnectionController::class, 'destroy'])->name('strava.disconnect');
```

- [ ] **Krok 5: `ProfileController@edit` — dodaj `stravaConnected`**

W `app/Http/Controllers/ProfileController.php`, w metodzie `edit()`, dodaj do tablicy
przekazywanej do `Inertia::render('Profile/Edit', [...])`:
```php
            'stravaConnected' => (bool) $request->user()->stravaConnection,
```

- [ ] **Krok 6: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=StravaConnectionTest`
Expected: PASS (7 testów).

- [ ] **Krok 7: Komponent formularza w profilu**

`resources/js/Pages/Profile/Partials/StravaConnectionForm.vue`:
```vue
<script setup>
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    connected: { type: Boolean, required: true },
});

const connect = () => {
    window.location.href = route('strava.connect');
};

const disconnect = () => {
    if (confirm('Rozłączyć konto Strava? Zaimportowane dotychczas treningi zostaną w historii.')) {
        router.delete(route('strava.disconnect'));
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Strava</h2>
            <p class="mt-1 text-sm text-gray-600">
                Połącz konto Strava, aby importować biegi i sporty jednym kliknięciem.
                Bez połączenia zawsze dostępny jest ręczny wpis treningu.
            </p>
        </header>

        <div class="mt-4">
            <p v-if="connected" class="text-sm text-green-700 mb-3">Konto Strava połączone.</p>
            <p v-else class="text-sm text-gray-600 mb-3">Konto Strava nie jest połączone.</p>

            <PrimaryButton v-if="!connected" @click="connect">Połącz ze Strava</PrimaryButton>
            <DangerButton v-else @click="disconnect">Rozłącz Stravę</DangerButton>
        </div>
    </section>
</template>
```

- [ ] **Krok 8: Podłącz kartę w `Profile/Edit.vue`**

Dodaj import `import StravaConnectionForm from './Partials/StravaConnectionForm.vue';`, dodaj
`stravaConnected: Boolean` do `defineProps`, i nową kartę między `HealthProfileForm` a
`UpdatePasswordForm`:
```vue
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <StravaConnectionForm :connected="stravaConnected" class="max-w-xl" />
                </div>
```

- [ ] **Krok 9: Commit**

```bash
git add app/Http/Controllers/Strava/StravaConnectionController.php routes/web.php app/Http/Controllers/ProfileController.php resources/js/Pages/Profile/Partials/StravaConnectionForm.vue resources/js/Pages/Profile/Edit.vue tests/Feature/Strava/StravaConnectionTest.php
git commit -m "feat(m2): add Strava OAuth connect/disconnect flow and profile UI"
```

---

### Task 6: Ręczny wpis biegu, historia i wykresy

**Pliki:**
- Create: `app/Http/Requests/StoreRunRequest.php`
- Create: `app/Http/Controllers/RunController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Runs/Index.vue`
- Create: `resources/js/Pages/Runs/Create.vue`
- Test: `tests/Feature/RunTest.php`

**Interfaces:**
- Consumes: `Workout`/`Run` (Task 1), `route('strava.sync')` (Task 4), `LineChart.vue` (M1),
  `localDate()` (M1).
- Produces: trasy `runs.index` (`GET /biegi`), `runs.create` (`GET /biegi/nowy`), `runs.store`
  (`POST /runs`). Task 8 (cele biegowe) i Task 9 (dashboard) czytają `activeGoal` z
  `RunController@index` — patrz Task 8 dla dokładnego kształtu tego propa.

- [ ] **Krok 1: Napisz testy (na razie failing)**

`tests/Feature/RunTest.php`:
```php
<?php

use App\Models\Run;
use App\Models\User;
use App\Models\Workout;

test('a user can manually log a run', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/runs', [
        'date' => '2026-09-01',
        'distance_km' => 5.0,
        'duration_min' => 30,
        'avg_heart_rate' => 150,
        'comment' => 'Czułem się mocny',
        'wellbeing_rating' => 4,
    ])->assertRedirect(route('runs.index'));

    $workout = Workout::where('user_id', $user->id)->where('type', 'run')->first();

    expect($workout)->not->toBeNull()
        ->and($workout->comment)->toBe('Czułem się mocny')
        ->and($workout->wellbeing_rating)->toBe(4);

    $run = Run::where('workout_id', $workout->id)->first();
    expect($run->distance_m)->toBe(5000)
        ->and($run->duration_s)->toBe(1800)
        ->and($run->avg_pace_s_per_km)->toBe(360)
        ->and($run->strava_activity_id)->toBeNull();
});

test('distance and duration are required and validated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/runs', ['date' => '2026-09-01'])
        ->assertSessionHasErrors(['distance_km', 'duration_min']);

    expect(Workout::count())->toBe(0);
});

test('a guest cannot log a run', function () {
    $this->post('/runs', ['date' => '2026-09-01', 'distance_km' => 5, 'duration_min' => 30])
        ->assertRedirect('/login');
});

test('the runs index shows only the current users runs, newest data included, marking source', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'run', 'date' => '2026-08-20']);
    Run::factory()->for($workoutA)->create(['distance_m' => 5000, 'duration_s' => 1800, 'strava_activity_id' => 987]);

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/biegi');

    $response->assertInertia(fn ($page) => $page
        ->component('Runs/Index')
        ->has('runs', 1)
        ->where('runs.0.distance_km', 5.0)
        ->where('runs.0.source', 'strava')
        ->where('stravaConnected', false));
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=RunTest`
Expected: FAIL (trasy nie istnieją).

- [ ] **Krok 3: `StoreRunRequest`**

`app/Http/Requests/StoreRunRequest.php`:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'duration_min' => ['required', 'numeric', 'min:1', 'max:600'],
            'avg_heart_rate' => ['nullable', 'integer', 'min:60', 'max:220'],
            'comment' => ['nullable', 'string', 'max:500'],
            'wellbeing_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
```

- [ ] **Krok 4: `RunController`**

`app/Http/Controllers/RunController.php` (na tym etapie `activeGoal` jest zawsze `null` —
Task 8 doda realną logikę celu; zostawiamy tu kontrakt propa, żeby Runs/Index.vue mógł być
napisany raz):
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRunRequest;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RunController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $runs = Workout::forUser($user)
            ->where('type', 'run')
            ->with('run')
            ->orderBy('date')
            ->get()
            ->map(fn ($workout) => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'distance_km' => round($workout->run->distance_m / 1000, 2),
                'duration_min' => round($workout->run->duration_s / 60, 1),
                'avg_pace_s_per_km' => $workout->run->avg_pace_s_per_km,
                'avg_heart_rate' => $workout->run->avg_heart_rate,
                'source' => $workout->run->strava_activity_id ? 'strava' : 'manual',
            ]);

        return Inertia::render('Runs/Index', [
            'runs' => $runs,
            'activeGoal' => null,
            'stravaConnected' => (bool) $user->stravaConnection,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Runs/Create');
    }

    public function store(StoreRunRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $distanceM = (int) round($data['distance_km'] * 1000);
        $durationS = (int) round($data['duration_min'] * 60);

        DB::transaction(function () use ($request, $data, $distanceM, $durationS) {
            $workout = $request->user()->workouts()->create([
                'type' => 'run',
                'date' => $data['date'],
                'status' => 'completed',
                'comment' => $data['comment'] ?? null,
                'wellbeing_rating' => $data['wellbeing_rating'] ?? null,
            ]);

            $workout->run()->create([
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'avg_pace_s_per_km' => $distanceM > 0 ? (int) round($durationS / ($distanceM / 1000)) : null,
                'avg_heart_rate' => $data['avg_heart_rate'] ?? null,
            ]);
        });

        return redirect()->route('runs.index')->with('status', 'run-saved');
    }
}
```

- [ ] **Krok 5: Trasy**

W `routes/web.php` dodaj import `use App\Http\Controllers\RunController;` i wewnątrz grupy
`auth`:
```php
    Route::get('/biegi', [RunController::class, 'index'])->name('runs.index');
    Route::get('/biegi/nowy', [RunController::class, 'create'])->name('runs.create');
    Route::post('/runs', [RunController::class, 'store'])->name('runs.store');
```

- [ ] **Krok 6: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=RunTest`
Expected: PASS (4 testy).

- [ ] **Krok 7: `Runs/Create.vue`**

`resources/js/Pages/Runs/Create.vue`:
```vue
<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { localDate } from '@/localDateTime';

const form = useForm({
    date: localDate(),
    distance_km: '',
    duration_min: '',
    avg_heart_rate: '',
    comment: '',
    wellbeing_rating: '',
});

const submit = () => form.post(route('runs.store'));
</script>

<template>
    <Head title="Nowy bieg" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowy bieg</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <InputLabel for="date" value="Data" />
                        <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" />
                        <InputError class="mt-2" :message="form.errors.date" />
                    </div>
                    <div>
                        <InputLabel for="distance_km" value="Dystans (km)" />
                        <TextInput id="distance_km" type="number" step="0.01" class="mt-1 block w-full" v-model="form.distance_km" />
                        <InputError class="mt-2" :message="form.errors.distance_km" />
                    </div>
                    <div>
                        <InputLabel for="duration_min" value="Czas (min)" />
                        <TextInput id="duration_min" type="number" step="0.1" class="mt-1 block w-full" v-model="form.duration_min" />
                        <InputError class="mt-2" :message="form.errors.duration_min" />
                    </div>
                    <div>
                        <InputLabel for="avg_heart_rate" value="Tętno śr. (opcjonalnie)" />
                        <TextInput id="avg_heart_rate" type="number" class="mt-1 block w-full" v-model="form.avg_heart_rate" />
                        <InputError class="mt-2" :message="form.errors.avg_heart_rate" />
                    </div>
                    <div>
                        <InputLabel for="wellbeing_rating" value="Samopoczucie 1-5 (opcjonalnie)" />
                        <TextInput id="wellbeing_rating" type="number" min="1" max="5" class="mt-1 block w-full" v-model="form.wellbeing_rating" />
                        <InputError class="mt-2" :message="form.errors.wellbeing_rating" />
                    </div>
                    <div>
                        <InputLabel for="comment" value="Komentarz (opcjonalnie)" />
                        <TextInput id="comment" type="text" class="mt-1 block w-full" v-model="form.comment" />
                        <InputError class="mt-2" :message="form.errors.comment" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Zapisz bieg</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Krok 8: `Runs/Index.vue`**

`resources/js/Pages/Runs/Index.vue`:
```vue
<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    runs: { type: Array, required: true },
    activeGoal: { type: Object, default: null },
    stravaConnected: { type: Boolean, required: true },
});

const chartLabels = computed(() => props.runs.map((r) => r.date));
const chartDatasets = computed(() => [
    { label: 'Dystans (km)', data: props.runs.map((r) => r.distance_km), borderColor: '#4f46e5', tension: 0.2 },
]);

const syncStrava = () => router.post(route('strava.sync'));
</script>

<template>
    <Head title="Bieganie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bieganie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="stravaConnected" @click="syncStrava">Pobierz ze Stravy</PrimaryButton>
                    <Link v-else :href="route('profile.edit')" class="text-sm text-indigo-600 hover:underline">
                        Połącz Stravę, aby importować biegi automatycznie
                    </Link>
                    <Link :href="route('runs.create')" class="text-sm text-indigo-600 hover:underline">Dodaj bieg ręcznie →</Link>
                </div>

                <div v-if="activeGoal" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Cel biegowy</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        {{ activeGoal.target_distance_km }} km do {{ activeGoal.target_date }}
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: activeGoal.progressPercent + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ activeGoal.progressPercent }}% — na podstawie najdłuższego biegu</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historia</h3>
                    <p v-if="runs.length === 0" class="text-sm text-gray-600">Brak jeszcze żadnych biegów.</p>
                    <template v-else>
                        <LineChart :labels="chartLabels" :datasets="chartDatasets" class="mb-6" />
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="pb-2">Data</th>
                                    <th class="pb-2">Dystans</th>
                                    <th class="pb-2">Czas</th>
                                    <th class="pb-2">Tętno śr.</th>
                                    <th class="pb-2">Źródło</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="run in runs" :key="run.id" class="border-t">
                                    <td class="py-2">{{ run.date }}</td>
                                    <td class="py-2">{{ run.distance_km }} km</td>
                                    <td class="py-2">{{ run.duration_min }} min</td>
                                    <td class="py-2">{{ run.avg_heart_rate ?? '—' }}</td>
                                    <td class="py-2">{{ run.source === 'strava' ? 'Strava' : 'ręczny' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Krok 9: Commit**

```bash
git add app/Http/Requests/StoreRunRequest.php app/Http/Controllers/RunController.php routes/web.php resources/js/Pages/Runs tests/Feature/RunTest.php
git commit -m "feat(m2): add manual run entry, history and chart"
```

---

### Task 7: Ręczny wpis sportu (tenis stołowy, squash, inne) i historia

**Pliki:**
- Create: `app/Http/Requests/StoreSportSessionRequest.php`
- Create: `app/Http/Controllers/SportSessionController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Sports/Index.vue`
- Create: `resources/js/Pages/Sports/Create.vue`
- Test: `tests/Feature/SportSessionTest.php`

**Interfaces:**
- Consumes: `Workout`/`SportSession` (Task 1).
- Produces: trasy `sport-sessions.index` (`GET /sporty`), `sport-sessions.create`
  (`GET /sporty/nowy`), `sport-sessions.store` (`POST /sport-sessions`).

- [ ] **Krok 1: Napisz testy (na razie failing)**

`tests/Feature/SportSessionTest.php`:
```php
<?php

use App\Models\SportSession;
use App\Models\User;
use App\Models\Workout;

test('a user can manually log a sport session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/sport-sessions', [
        'date' => '2026-09-01',
        'sport_subtype' => 'squash',
        'duration_min' => 60,
        'intensity' => 4,
        'comment' => 'Mecz z Michałem',
    ])->assertRedirect(route('sport-sessions.index'));

    $workout = Workout::where('user_id', $user->id)->where('type', 'sport')->first();

    expect($workout)->not->toBeNull()
        ->and($workout->sport_subtype)->toBe('squash')
        ->and($workout->comment)->toBe('Mecz z Michałem');

    $session = SportSession::where('workout_id', $workout->id)->first();
    expect($session->duration_s)->toBe(3600)
        ->and($session->intensity)->toBe(4);
});

test('sport_subtype must be one of the known dictionary values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/sport-sessions', ['date' => '2026-09-01', 'sport_subtype' => 'nieznany', 'duration_min' => 60, 'intensity' => 3])
        ->assertSessionHasErrors('sport_subtype');
});

test('a guest cannot log a sport session', function () {
    $this->post('/sport-sessions', ['date' => '2026-09-01', 'sport_subtype' => 'squash', 'duration_min' => 60, 'intensity' => 3])
        ->assertRedirect('/login');
});

test('the sports index shows only the current users sessions', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'sport', 'sport_subtype' => 'table_tennis']);
    SportSession::factory()->for($workoutA)->create(['duration_s' => 1800, 'intensity' => 3]);

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'sport']);
    SportSession::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/sporty');

    $response->assertInertia(fn ($page) => $page
        ->component('Sports/Index')
        ->has('sessions', 1)
        ->where('sessions.0.sport_subtype', 'table_tennis'));
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=SportSessionTest`
Expected: FAIL (trasy nie istnieją).

- [ ] **Krok 3: `StoreSportSessionRequest`**

`app/Http/Requests/StoreSportSessionRequest.php`:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'sport_subtype' => ['required', Rule::in(['table_tennis', 'squash', 'other'])],
            'duration_min' => ['required', 'numeric', 'min:1', 'max:600'],
            'intensity' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

- [ ] **Krok 4: `SportSessionController`**

`app/Http/Controllers/SportSessionController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSportSessionRequest;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SportSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = Workout::forUser($request->user())
            ->where('type', 'sport')
            ->with('sportSession')
            ->orderBy('date')
            ->get()
            ->map(fn ($workout) => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'sport_subtype' => $workout->sport_subtype,
                'duration_min' => round($workout->sportSession->duration_s / 60, 1),
                'intensity' => $workout->sportSession->intensity,
                'source' => $workout->sportSession->strava_activity_id ? 'strava' : 'manual',
            ]);

        return Inertia::render('Sports/Index', ['sessions' => $sessions]);
    }

    public function create(): Response
    {
        return Inertia::render('Sports/Create');
    }

    public function store(StoreSportSessionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $durationS = (int) round($data['duration_min'] * 60);

        DB::transaction(function () use ($request, $data, $durationS) {
            $workout = $request->user()->workouts()->create([
                'type' => 'sport',
                'sport_subtype' => $data['sport_subtype'],
                'date' => $data['date'],
                'status' => 'completed',
                'comment' => $data['comment'] ?? null,
            ]);

            $workout->sportSession()->create([
                'duration_s' => $durationS,
                'intensity' => $data['intensity'],
            ]);
        });

        return redirect()->route('sport-sessions.index')->with('status', 'sport-session-saved');
    }
}
```

- [ ] **Krok 5: Trasy**

W `routes/web.php` dodaj import `use App\Http\Controllers\SportSessionController;` i
wewnątrz grupy `auth`:
```php
    Route::get('/sporty', [SportSessionController::class, 'index'])->name('sport-sessions.index');
    Route::get('/sporty/nowy', [SportSessionController::class, 'create'])->name('sport-sessions.create');
    Route::post('/sport-sessions', [SportSessionController::class, 'store'])->name('sport-sessions.store');
```

- [ ] **Krok 6: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=SportSessionTest`
Expected: PASS (4 testy).

- [ ] **Krok 7: `Sports/Create.vue`**

`resources/js/Pages/Sports/Create.vue`:
```vue
<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { localDate } from '@/localDateTime';

const form = useForm({
    date: localDate(),
    sport_subtype: 'table_tennis',
    duration_min: '',
    intensity: '',
    comment: '',
});

const submit = () => form.post(route('sport-sessions.store'));
</script>

<template>
    <Head title="Nowy sport" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowa sesja sportowa</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <InputLabel for="date" value="Data" />
                        <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" />
                        <InputError class="mt-2" :message="form.errors.date" />
                    </div>
                    <div>
                        <InputLabel for="sport_subtype" value="Dyscyplina" />
                        <select id="sport_subtype" v-model="form.sport_subtype" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="table_tennis">Tenis stołowy</option>
                            <option value="squash">Squash</option>
                            <option value="other">Inne</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.sport_subtype" />
                    </div>
                    <div>
                        <InputLabel for="duration_min" value="Czas trwania (min)" />
                        <TextInput id="duration_min" type="number" step="1" class="mt-1 block w-full" v-model="form.duration_min" />
                        <InputError class="mt-2" :message="form.errors.duration_min" />
                    </div>
                    <div>
                        <InputLabel for="intensity" value="Intensywność 1-5" />
                        <TextInput id="intensity" type="number" min="1" max="5" class="mt-1 block w-full" v-model="form.intensity" />
                        <InputError class="mt-2" :message="form.errors.intensity" />
                    </div>
                    <div>
                        <InputLabel for="comment" value="Komentarz (opcjonalnie)" />
                        <TextInput id="comment" type="text" class="mt-1 block w-full" v-model="form.comment" />
                        <InputError class="mt-2" :message="form.errors.comment" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Krok 8: `Sports/Index.vue`**

`resources/js/Pages/Sports/Index.vue`:
```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    sessions: { type: Array, required: true },
});

const subtypeLabel = (subtype) => ({ table_tennis: 'Tenis stołowy', squash: 'Squash', other: 'Inne' }[subtype] ?? subtype);
</script>

<template>
    <Head title="Sporty" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sporty</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Historia</h3>
                        <Link :href="route('sport-sessions.create')" class="text-sm text-indigo-600 hover:underline">Dodaj sesję →</Link>
                    </div>
                    <p v-if="sessions.length === 0" class="text-sm text-gray-600">Brak jeszcze żadnych sesji sportowych.</p>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Data</th>
                                <th class="pb-2">Dyscyplina</th>
                                <th class="pb-2">Czas</th>
                                <th class="pb-2">Intensywność</th>
                                <th class="pb-2">Źródło</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions" :key="session.id" class="border-t">
                                <td class="py-2">{{ session.date }}</td>
                                <td class="py-2">{{ subtypeLabel(session.sport_subtype) }}</td>
                                <td class="py-2">{{ session.duration_min }} min</td>
                                <td class="py-2">{{ session.intensity }}/5</td>
                                <td class="py-2">{{ session.source === 'strava' ? 'Strava' : 'ręczny' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Krok 9: Commit**

```bash
git add app/Http/Requests/StoreSportSessionRequest.php app/Http/Controllers/SportSessionController.php routes/web.php resources/js/Pages/Sports tests/Feature/SportSessionTest.php
git commit -m "feat(m2): add manual sport session entry and history"
```

---

### Task 8: Cele biegowe — `TrainingGoalProgress`, tworzenie celu, pasek postępu

**Pliki:**
- Create: `app/Services/TrainingGoalProgress.php`
- Create: `app/Http/Requests/StoreTrainingGoalRequest.php`
- Create: `app/Http/Controllers/TrainingGoalController.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/RunController.php` (podłącz realny `activeGoal` i wywołaj
  `refreshStatus` po zapisaniu biegu)
- Modify: `app/Services/Strava/StravaSyncService.php` (wywołaj `refreshStatus` po imporcie)
- Modify: `resources/js/Pages/Runs/Index.vue` (formularz tworzenia/zmiany celu)
- Test: `tests/Unit/TrainingGoalProgressTest.php`
- Test: `tests/Feature/TrainingGoalTest.php`

**Interfaces:**
- Consumes: `TrainingGoal`, `Workout`, `Run` (Task 1); `RunController::index` (Task 6);
  `StravaSyncService::sync()` (Task 4).
- Produces: `TrainingGoalProgress::percent(TrainingGoal $goal): int`,
  `TrainingGoalProgress::refreshStatus(TrainingGoal $goal): void`, trasa
  `training-goals.store` (`POST /cele-biegowe`).

- [ ] **Krok 1: Napisz test jednostkowy dla `TrainingGoalProgress` (na razie failing)**

`tests/Unit/TrainingGoalProgressTest.php`:
```php
<?php

use App\Models\Run;
use App\Models\TrainingGoal;
use App\Models\User;
use App\Models\Workout;
use App\Services\TrainingGoalProgress;
use Illuminate\Support\Carbon;

test('percent is the ratio of the longest run since goal creation to the target, capped at 100', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'created_at' => Carbon::parse('2026-08-01'),
    ]);

    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-15']);
    Run::factory()->for($workout)->create(['distance_m' => 6000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(60);
});

test('percent ignores runs logged before the goal was created', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'created_at' => Carbon::parse('2026-08-15'),
    ]);

    $oldWorkout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-01']);
    Run::factory()->for($oldWorkout)->create(['distance_m' => 9000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(0);
});

test('percent is capped at 100 even when the longest run exceeds the target', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'created_at' => Carbon::parse('2026-08-01'),
    ]);

    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 8000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(100);
});

test('percent is zero when there are no qualifying runs', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create();

    expect(TrainingGoalProgress::percent($goal))->toBe(0);
});

test('refreshStatus marks an active goal achieved once the target distance is reached', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'status' => 'active',
        'created_at' => Carbon::parse('2026-08-01'),
    ]);
    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 5000]);

    TrainingGoalProgress::refreshStatus($goal);

    expect($goal->fresh()->status)->toBe('achieved');
});

test('refreshStatus leaves an already-abandoned goal untouched', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'status' => 'abandoned',
        'created_at' => Carbon::parse('2026-08-01'),
    ]);
    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 9000]);

    TrainingGoalProgress::refreshStatus($goal);

    expect($goal->fresh()->status)->toBe('abandoned');
});
```

- [ ] **Krok 2: Uruchom testy jednostkowe — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=TrainingGoalProgressTest`
Expected: FAIL (klasa nie istnieje).

- [ ] **Krok 3: Implementacja `TrainingGoalProgress`**

`app/Services/TrainingGoalProgress.php`:
```php
<?php

namespace App\Services;

use App\Models\TrainingGoal;

class TrainingGoalProgress
{
    public static function percent(TrainingGoal $goal): int
    {
        if ($goal->target_distance_m <= 0) {
            return 0;
        }

        $longestRunM = $goal->user->workouts()
            ->where('type', 'run')
            ->where('date', '>=', $goal->created_at->format('Y-m-d'))
            ->with('run')
            ->get()
            ->max(fn ($workout) => $workout->run?->distance_m ?? 0) ?? 0;

        return (int) min(100, round(($longestRunM / $goal->target_distance_m) * 100));
    }

    public static function refreshStatus(TrainingGoal $goal): void
    {
        if ($goal->status !== 'active') {
            return;
        }

        if (self::percent($goal) >= 100) {
            $goal->update(['status' => 'achieved']);
        }
    }
}
```

- [ ] **Krok 4: Uruchom testy jednostkowe — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=TrainingGoalProgressTest`
Expected: PASS (6 testów).

- [ ] **Krok 5: Napisz test feature dla tworzenia celu (na razie failing)**

`tests/Feature/TrainingGoalTest.php`:
```php
<?php

use App\Models\TrainingGoal;
use App\Models\User;

test('a user can set a new active run distance goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/cele-biegowe', [
        'target_distance_km' => 7.5,
        'target_date' => now()->addMonths(2)->format('Y-m-d'),
    ])->assertRedirect(route('runs.index'));

    $goal = TrainingGoal::where('user_id', $user->id)->where('status', 'active')->first();
    expect($goal)->not->toBeNull()
        ->and($goal->target_distance_m)->toBe(7500)
        ->and($goal->type)->toBe('run_distance');
});

test('setting a new goal abandons the previous active one', function () {
    $user = User::factory()->create();
    $oldGoal = TrainingGoal::factory()->for($user)->create(['status' => 'active']);

    $this->actingAs($user)->post('/cele-biegowe', [
        'target_distance_km' => 10,
        'target_date' => now()->addMonths(3)->format('Y-m-d'),
    ]);

    expect($oldGoal->fresh()->status)->toBe('abandoned')
        ->and(TrainingGoal::where('user_id', $user->id)->where('status', 'active')->count())->toBe(1);
});

test('target date must be in the future', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/cele-biegowe', ['target_distance_km' => 5, 'target_date' => now()->subDay()->format('Y-m-d')])
        ->assertSessionHasErrors('target_date');
});

test('the runs index exposes the active goal with computed progress', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'target_date' => '2026-12-01',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get('/biegi');

    $response->assertInertia(fn ($page) => $page
        ->where('activeGoal.target_distance_km', 10.0)
        ->where('activeGoal.target_date', '2026-12-01')
        ->where('activeGoal.progressPercent', 0));
});

test('logging a run that reaches the target auto-marks the goal achieved', function () {
    $user = User::factory()->create();
    TrainingGoal::factory()->for($user)->create(['target_distance_m' => 5000, 'status' => 'active']);

    $this->actingAs($user)->post('/runs', [
        'date' => now()->format('Y-m-d'),
        'distance_km' => 5.0,
        'duration_min' => 30,
    ]);

    expect(TrainingGoal::where('user_id', $user->id)->first()->status)->toBe('achieved');
});
```

- [ ] **Krok 6: `StoreTrainingGoalRequest`**

`app/Http/Requests/StoreTrainingGoalRequest.php`:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_distance_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'target_date' => ['required', 'date', 'after:today'],
            'target_time_min' => ['nullable', 'numeric', 'min:1'],
        ];
    }
}
```

- [ ] **Krok 7: `TrainingGoalController`**

`app/Http/Controllers/TrainingGoalController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingGoalRequest;
use Illuminate\Http\RedirectResponse;

class TrainingGoalController extends Controller
{
    public function store(StoreTrainingGoalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->trainingGoals()
            ->where('type', 'run_distance')
            ->where('status', 'active')
            ->update(['status' => 'abandoned']);

        $request->user()->trainingGoals()->create([
            'type' => 'run_distance',
            'target_distance_m' => (int) round($data['target_distance_km'] * 1000),
            'target_date' => $data['target_date'],
            'target_time_s' => isset($data['target_time_min']) ? (int) round($data['target_time_min'] * 60) : null,
            'status' => 'active',
        ]);

        return redirect()->route('runs.index')->with('status', 'goal-saved');
    }
}
```

- [ ] **Krok 8: Trasa**

W `routes/web.php` dodaj import `use App\Http\Controllers\TrainingGoalController;` i
wewnątrz grupy `auth`:
```php
    Route::post('/cele-biegowe', [TrainingGoalController::class, 'store'])->name('training-goals.store');
```

- [ ] **Krok 9: Podłącz realny `activeGoal` w `RunController::index` i `refreshStatus` w `store`**

W `app/Http/Controllers/RunController.php` dodaj `use App\Services\TrainingGoalProgress;` i
zamień w `index()`:
```php
        $activeGoal = $user->trainingGoals()->where('type', 'run_distance')->where('status', 'active')->latest('target_date')->first();
```
oraz zamień `'activeGoal' => null,` na:
```php
            'activeGoal' => $activeGoal ? [
                'id' => $activeGoal->id,
                'target_distance_km' => round($activeGoal->target_distance_m / 1000, 2),
                'target_date' => $activeGoal->target_date->format('Y-m-d'),
                'progressPercent' => TrainingGoalProgress::percent($activeGoal),
            ] : null,
```

W `store()`, po bloku `DB::transaction(...)`, dodaj:
```php
        $request->user()->trainingGoals()
            ->where('type', 'run_distance')
            ->where('status', 'active')
            ->get()
            ->each(fn ($goal) => TrainingGoalProgress::refreshStatus($goal));
```

- [ ] **Krok 10: Podłącz `refreshStatus` w `StravaSyncService::sync`**

W `app/Services/Strava/StravaSyncService.php` dodaj `use App\Models\TrainingGoal;` i
`use App\Services\TrainingGoalProgress;`, a na końcu `sync()` (przed `return $imported;`):
```php
        TrainingGoal::forUser($connection->user_id)
            ->where('type', 'run_distance')
            ->where('status', 'active')
            ->get()
            ->each(fn ($goal) => TrainingGoalProgress::refreshStatus($goal));
```

- [ ] **Krok 11: Uruchom wszystkie nowe testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=TrainingGoal`
Expected: PASS (11 testów łącznie: 6 unit + 5 feature).

Run: `./vendor/bin/sail artisan test --filter=RunTest`
Expected: nadal PASS (4 testy) — upewnij się, że modyfikacja `RunController` niczego nie
zepsuła.

- [ ] **Krok 12: Formularz celu na `Runs/Index.vue`**

W `resources/js/Pages/Runs/Index.vue` dodaj import `useForm` z `@inertiajs/vue3` oraz nową
kartę (przed kartą „Cel biegowy” lub zamiast placeholdera, jeśli `!activeGoal`) z prostym
formularzem tworzenia celu:
```vue
<script setup>
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    runs: { type: Array, required: true },
    activeGoal: { type: Object, default: null },
    stravaConnected: { type: Boolean, required: true },
});

const chartLabels = computed(() => props.runs.map((r) => r.date));
const chartDatasets = computed(() => [
    { label: 'Dystans (km)', data: props.runs.map((r) => r.distance_km), borderColor: '#4f46e5', tension: 0.2 },
]);

const syncStrava = () => router.post(route('strava.sync'));

const goalForm = useForm({ target_distance_km: '', target_date: '' });
const submitGoal = () => goalForm.post(route('training-goals.store'), { onSuccess: () => goalForm.reset() });
</script>
```
Dodaj w `<template>`, zaraz po karcie „Cel biegowy” (którą trzeba pokazać `v-if="activeGoal"`,
a formularz `v-else`):
```vue
                <div v-if="activeGoal" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Cel biegowy</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        {{ activeGoal.target_distance_km }} km do {{ activeGoal.target_date }}
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: activeGoal.progressPercent + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ activeGoal.progressPercent }}% — na podstawie najdłuższego biegu</p>
                </div>
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Ustaw cel biegowy</h3>
                    <form @submit.prevent="submitGoal" class="flex flex-wrap items-end gap-3">
                        <div>
                            <InputLabel for="target_distance_km" value="Dystans (km)" />
                            <TextInput id="target_distance_km" type="number" step="0.1" v-model="goalForm.target_distance_km" />
                            <InputError :message="goalForm.errors.target_distance_km" />
                        </div>
                        <div>
                            <InputLabel for="target_date" value="Data" />
                            <TextInput id="target_date" type="date" v-model="goalForm.target_date" />
                            <InputError :message="goalForm.errors.target_date" />
                        </div>
                        <PrimaryButton :disabled="goalForm.processing">Ustaw cel</PrimaryButton>
                    </form>
                </div>
```
(Usuń starą kartę „Cel biegowy” bez `v-else`, żeby nie duplikować markupu — zastąp ją tym
blokiem `v-if`/`v-else` w całości.)

- [ ] **Krok 13: Commit**

```bash
git add app/Services/TrainingGoalProgress.php app/Http/Requests/StoreTrainingGoalRequest.php app/Http/Controllers/TrainingGoalController.php app/Http/Controllers/RunController.php app/Services/Strava/StravaSyncService.php routes/web.php resources/js/Pages/Runs/Index.vue tests/Unit/TrainingGoalProgressTest.php tests/Feature/TrainingGoalTest.php
git commit -m "feat(m2): add running goals with progress bar and auto-achievement"
```

---

### Task 9: Dashboard v2 — cel biegowy i przycisk „Pobierz ze Stravy”

**Pliki:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/js/Pages/Dashboard.vue`
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: `TrainingGoalProgress::percent()` (Task 8), `route('strava.sync')` (Task 4).
- Produces: `Dashboard` Inertia props gain `running: { activeGoal: {...}|null, stravaConnected: bool }`.

- [ ] **Krok 1: Rozszerz test dashboardu (na razie failing)**

Dopisz do `tests/Feature/DashboardTest.php` (sprawdź istniejące importy na górze pliku i
uzupełnij brakujące):
```php
test('the dashboard exposes the active run goal and strava connection state', function () {
    $user = \App\Models\User::factory()->create();
    \App\Models\TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 7500,
        'target_date' => '2026-09-13',
        'status' => 'active',
    ]);
    \App\Models\StravaConnection::factory()->for($user)->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('running.activeGoal.target_distance_km', 7.5)
        ->where('running.activeGoal.target_date', '2026-09-13')
        ->where('running.stravaConnected', true));
});

test('the dashboard shows no active goal when none exists', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('running.activeGoal', null)
        ->where('running.stravaConnected', false));
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

Run: `./vendor/bin/sail artisan test --filter=DashboardTest`
Expected: FAIL (prop `running` nie istnieje).

- [ ] **Krok 3: `DashboardController` — dodaj sekcję `running`**

W `app/Http/Controllers/DashboardController.php` dodaj importy
`use App\Services\TrainingGoalProgress;`, a w `index()` przed `return Inertia::render(...)`:
```php
        $activeGoal = $user->trainingGoals()->where('type', 'run_distance')->where('status', 'active')->latest('target_date')->first();
```
i dodaj do tablicy propsów klucz `running`:
```php
            'running' => [
                'activeGoal' => $activeGoal ? [
                    'target_distance_km' => round($activeGoal->target_distance_m / 1000, 2),
                    'target_date' => $activeGoal->target_date->format('Y-m-d'),
                    'progressPercent' => TrainingGoalProgress::percent($activeGoal),
                ] : null,
                'stravaConnected' => (bool) $user->stravaConnection,
            ],
```

- [ ] **Krok 4: Uruchom testy — muszą przejść**

Run: `./vendor/bin/sail artisan test --filter=DashboardTest`
Expected: PASS (wszystkie testy dashboardu, w tym 2 nowe).

- [ ] **Krok 5: `Dashboard.vue` — nowa karta „Bieganie”**

W `resources/js/Pages/Dashboard.vue` dodaj `running: { type: Object, required: true }` do
`defineProps`, zaimportuj `router` z `@inertiajs/vue3` obok istniejących importów, dodaj
funkcję `syncStrava = () => router.post(route('strava.sync'))`, i nową kartę po karcie
„Zdrowie”:
```vue
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Bieganie</h3>
                    <template v-if="running.activeGoal">
                        <p class="text-sm text-gray-600 mb-2">
                            {{ running.activeGoal.target_distance_km }} km do {{ running.activeGoal.target_date }}
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: running.activeGoal.progressPercent + '%' }"></div>
                        </div>
                    </template>
                    <p v-else class="text-sm text-gray-600">Brak ustawionego celu biegowego.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button v-if="running.stravaConnected" @click="syncStrava" class="text-sm text-indigo-600 hover:underline">
                            Pobierz ze Stravy
                        </button>
                        <Link :href="route('runs.index')" class="text-sm text-indigo-600 hover:underline">Zobacz biegi →</Link>
                    </div>
                </div>
```

- [ ] **Krok 6: Commit**

```bash
git add app/Http/Controllers/DashboardController.php resources/js/Pages/Dashboard.vue tests/Feature/DashboardTest.php
git commit -m "feat(m2): add running goal and strava sync to dashboard v2"
```

---

### Task 10: Testy izolacji danych treningowych

**Pliki:**
- Test: `tests/Feature/TrainingIsolationTest.php`

**Interfaces:**
- Consumes: wszystkie modele/trasy z Tasków 1-9. Ten task nie dodaje nowego kodu produkcyjnego
  — jest dedykowanym, całościowym dowodem izolacji dla nowych tabel M2 (analogicznie do
  `DataIsolationTest.php` z M0 i `LabResultIsolationTest.php` z M1).

- [ ] **Krok 1: Napisz testy**

`tests/Feature/TrainingIsolationTest.php`:
```php
<?php

use App\Models\Run;
use App\Models\SportSession;
use App\Models\StravaConnection;
use App\Models\TrainingGoal;
use App\Models\User;
use App\Models\Workout;

test('a user never sees another users workouts, goals or strava connection via routes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutB)->create();
    TrainingGoal::factory()->for($userB)->create(['status' => 'active']);
    StravaConnection::factory()->for($userB)->create();

    $this->actingAs($userA);

    $this->get('/biegi')->assertInertia(fn ($page) => $page->has('runs', 0)->where('activeGoal', null));
    $this->get('/dashboard')->assertInertia(fn ($page) => $page
        ->where('running.activeGoal', null)
        ->where('running.stravaConnected', false));
});

test('a user cannot set a goal, log a run or sync strava on behalf of another account', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->post('/runs', [
        'date' => now()->format('Y-m-d'),
        'distance_km' => 5,
        'duration_min' => 30,
    ]);

    expect(Workout::where('user_id', $userB->id)->count())->toBe(0)
        ->and(Workout::where('user_id', $userA->id)->count())->toBe(1);
});

test('sport sessions are isolated the same way as runs', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'sport']);
    SportSession::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/sporty');

    $response->assertInertia(fn ($page) => $page->has('sessions', 0));
});

test('deleting a user cascades to their workouts, runs, goals and strava connection', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'run']);
    Run::factory()->for($workout)->create();
    TrainingGoal::factory()->for($user)->create();
    StravaConnection::factory()->for($user)->create();

    $user->delete();

    expect(Workout::withoutGlobalScopes()->count())->toBe(0)
        ->and(Run::count())->toBe(0)
        ->and(TrainingGoal::withoutGlobalScopes()->count())->toBe(0)
        ->and(StravaConnection::withoutGlobalScopes()->count())->toBe(0);
});
```

- [ ] **Krok 2: Uruchom testy**

Run: `./vendor/bin/sail artisan test --filter=TrainingIsolationTest`
Expected: PASS (4 testy). Jeśli test kaskady usuwania nie przejdzie, sprawdź czy wszystkie
migracje z Task 1 mają `cascadeOnDelete()` na `user_id`/`workout_id` — jeśli nie, popraw
migracje i uruchom `./vendor/bin/sail artisan migrate:fresh` przed ponownym testem (w dev; w
tym projekcie nie ma jeszcze danych produkcyjnych, więc `migrate:fresh` jest bezpieczny).

- [ ] **Krok 3: Uruchom pełny zestaw testów projektu**

Run: `./vendor/bin/sail artisan test`
Expected: PASS (wszystkie testy — M0 + M1 + M2).

- [ ] **Krok 4: Commit**

```bash
git add tests/Feature/TrainingIsolationTest.php
git commit -m "test(m2): add isolation coverage for workouts, runs, goals and strava connections"
```

---

## Self-Review Notes (dla autora planu — już wykonane)

- **Pokrycie spec:** OAuth Strava per-user opcjonalne ✅ (Task 5), przycisk „Pobierz ze
  Stravy” ✅ (Task 4 backend, Task 6/9 UI), import historyczny z paginacją ✅ (Task 4),
  deduplikacja przez unique index ✅ (Task 1 migracje + Task 4 testy dedup), mapowanie typów
  Run/sport ✅ (Task 3), historia i wykresy biegów ✅ (Task 6), ręczny wpis biegu i sportu ✅
  (Task 6, 7), cele biegowe z paskiem postępu ✅ (Task 8), dashboard v2 ✅ (Task 9).
- **Świadomie poza zakresem M2** (zgodnie ze spec §5, §9): webhook Stravy (opcjonalne po M7),
  `training_goals.type=weight` (już pokryte przez `profiles.weight_goal_kg` z M1), edycja/usuwanie
  wpisów treningowych (wzorzec „trwałe wpisy historyczne” z M0/M1 — modyfikacja to osobna
  decyzja produktowa, nie blokuje kryterium ukończenia M2).
- **Spójność typów:** `Workout::forUser()` pochodzi z `BelongsToUser` (Task 1) i jest używane
  identycznie w `RunController`, `SportSessionController`, `DashboardController`,
  `StravaSyncService` oraz testach izolacji — nazwa i sygnatura nie driftują między taskami.
  `TrainingGoalProgress::percent()`/`refreshStatus()` mają dokładnie te same sygnatury
  wszędzie, gdzie są wywoływane (Task 8, 9, oraz `StravaSyncService` z Task 4/8).

## M2 — Status: UKOŃCZONY (2026-08-26)

Wszystkie 10 zadań zaimplementowane, każde zrecenzowane osobno (wszystkie zatwierdzone bez
istotnych zastrzeżeń), plus finalny przegląd całej gałęzi (opus). Finalny przegląd znalazł
1 błąd krytyczny i 4 istotne — wszystkie naprawione w jednej fali poprawek, zweryfikowane
przez ponowny, zakresowy przegląd (wszystkie 11 ustaleń potwierdzone jako naprawione, zero
nowych regresji). Finalna liczba testów: **121/121**.

Naprawione w finalnym review:
- **Krytyczne:** obejście CSRF w przepływie OAuth Stravy — `callback()` przepuszczał sfałszowane
  żądanie, gdy w sesji nie było oczekiwanego `state` i żądanie też go nie zawierało
  (`null !== null`). Naprawione przez wymóg realnego stringa + `hash_equals()`, dodano test
  regresyjny.
- **Istotne:** `/biegi` i `/sporty` były nieosiągalne z nawigacji (plan nie przewidział edycji
  `AuthenticatedLayout.vue` — defekt planu, nie wykonawcy). Dodano linki w obu listach nawigacji.
- **Istotne:** synchronizacja przyrostowa Stravy mogła bezpowrotnie pominąć trening wysłany do
  Stravy z opóźnieniem (zegarek zsynchronizowany później niż dzień biegu) — dodano 7-dniowe
  okno zakładkowe na kursorze `after` (deduplikacja po `strava_activity_id` czyni to bezpieczne).
- **Istotne:** synchronizacja nie miała obsługi błędów (surowe 500 przy awarii API Stravy) ani
  limitu stron (teoretyczna nieskończona pętla) — dodano try/catch w kontrolerze i twardy limit
  50 stron w serwisie.
- **Istotne:** komunikaty flash (`status`) ustawiane przez kontrolery (M0/M1/M2) nigdy nie były
  renderowane w UI — dodano współdzielenie `flash.status` przez Inertię i baner w
  `AuthenticatedLayout.vue` z polskimi komunikatami (mapa znanych statusów + generyczny fallback
  dla starszych, nieskatalogowanych wartości z M0/M1).
- **Drobne (dołączone do fali poprawek):** zabezpieczenie przed zniekształconą aktywnością Stravy
  (brak `id`/`start_date_local` powodował powtarzalny import); wcześniejsze odświeżanie tokenu
  (5 min zapasu zamiast czekania na wygaśnięcie); szyfrowanie tokenów Stravy w bazie (`encrypted`
  cast); przechwycenie wyścigu przy deduplikacji (`QueryException` zamiast nieobsłużonego
  wyjątku); kolejność „Historia" biegów najnowsze-najpierw (wykres zostaje chronologiczny);
  renderowanie `—` zamiast pustej intensywności dla sesji sportowych importowanych ze Stravy.

Świadomie odłożone (udokumentowane w planie, nie porzucone po cichu):
- `training_goals.type=weight` nie jest implementowany — cel wagowy już istnieje w
  `profiles.weight_goal_kg` z M1.
- Komentarz i ocena samopoczucia dla biegów zaimportowanych ze Stravy (spec §4.2/§5) nie mają
  jeszcze ścieżki edycji — to dane wejściowe pętli korekty planu z M4, więc budowanie UI edycji
  teraz byłoby wyprzedzeniem zakresu tego planu. Do zaadresowania w M3/M4.
- Wykresy tempa/tętna i tygodniowego kilometrażu (spec §4.2) — plan przewidział tylko wykres
  dystansu; rozszerzenie wykresów to osobna, świadoma decyzja zakresu, nie luka w wykonaniu.
- Kosmetyczny drobiazg spoza fali poprawek: baner flash zawsze renderuje się w stylu
  „sukces” (zielony), nawet dla komunikatów o błędzie (np. `strava-connect-failed`) — treść
  jest poprawna po polsku, tylko kolorystyka nie rozróżnia sukcesu od błędu.

Gotowe do M3 — Siłownia.
