# M1 — Ciało i zdrowie Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-user body tracking (daily weight + weekly waist circumference with charts), blood pressure logging, blood lab results (predefined + custom markers, backdated entries, trend charts with norm bands), medications/supplements tracking, a configurable lab-test reminder (dashboard countdown only, no push yet), and a real Dashboard v1 showing weight trend + health summary.

**Architecture:** Every new personal-data table (`body_measurements`, `blood_pressure_readings`, `lab_results`, `medications`, `reminders`) uses the `App\Models\Concerns\BelongsToUser` trait established in M0 — this milestone is the first real proof that the mechanism generalizes beyond `Profile`. `lab_markers` is the one new table that is **shared** (per spec §6: predefined + custom markers live in one dictionary both users read), so it does NOT use `BelongsToUser`. Domain logic that needs testing before UI (per `CLAUDE.md`/spec §7) — the 7-day weight average/trend calculation and the reminder due-date countdown — lives in small `app/Services/*` classes with their own Pest unit tests, not buried in controllers. Charts use Chart.js via the `vue-chartjs` wrapper (thin, idiomatic, avoids hand-rolling canvas code) behind one reusable `LineChart.vue` component so every chart in this milestone (and future ones) looks and behaves the same way.

**Tech Stack:** Laravel 12, Inertia + Vue 3 (Composition API), MySQL 8, Tailwind CSS, Chart.js + vue-chartjs, Pest.

**Spec:** `specyfikacja.md` (repo root) — §2 (data separation rules), §4.1 (Dashboard v1 fields), §4.5 (Ciało), §4.6 (Zdrowie, minus AI comment and PDF report which are M4/M6), §6 (data model: `body_measurements`, `blood_pressure_readings`, `lab_markers`, `lab_results`, `lab_values`, `medications`, `reminders`), §8 → M1.

**Conventions established in M0 — read before starting:** `CLAUDE.md` (repo root).

---

## Global Constraints

- Every personal-data table gets `user_id` + `use BelongsToUser` (spec §6 "Kluczowa zasada"). Exception: `lab_markers` is shared (spec §6 explicitly lists it alongside `meals`/`exercises` as unscoped).
- `lab_values` is a subordinate detail table (like `gym_sets` will be in M3) — no `user_id` of its own; it is only ever reached through its scoped parent `lab_result_id`, so ownership is inherited, not duplicated.
- Polski UI, jednostki metryczne (kg, cm, mmHg, mg/dl) — spec §3 #9, `CLAUDE.md`.
- Waga: wpis w mniej niż 5 sekund od otwarcia aplikacji (spec §4.5, §8 M1 Kryterium) — the dashboard itself must have an inline one-field weight form, not just a link to a separate page.
- Badania krwi: data wykonania domyślnie dziś, ale dowolna wsteczna data musi być akceptowana — użytkownik uzupełnia całą historię (spec §4.6, §3 #7).
- Predefined lab markers (spec §4.6): cholesterol całkowity, LDL, HDL, trójglicerydy, ALT, AST, GGTP, glukoza. Norm ranges seeded are general reference ranges for context only — not medical advice (this becomes an explicit AI disclaimer later in M4; for M1 it's just a UI note).
- Przypomnienia o badaniach: only a countdown on the dashboard in M1 — no push notification (that's M6, spec §8).
- Kod idiomatyczny (Form Requests, Eloquent, Composition API), bez przemądrzałych abstrakcji — domain logic (trend calc, reminder countdown) gets a small `app/Services` class + Pest test, nothing heavier.
- Use `AuthenticatedLayout.vue` (not `AppLayout.vue`) for all new pages — `CLAUDE.md` convention from M0.
- Containers are already up from M0 (`./vendor/bin/sail up -d`) — every command below uses `./vendor/bin/sail ...`.

---

## File Structure

**Charts:**
- `resources/js/Components/LineChart.vue` — create: reusable Chart.js line-chart wrapper (dataset(s), optional goal/norm reference lines).

**Ciało (body measurements):**
- `database/migrations/xxxx_create_body_measurements_table.php` — create.
- `app/Models/BodyMeasurement.php` — create.
- `database/factories/BodyMeasurementFactory.php` — create.
- `app/Services/WeightTrend.php` — create: 7-day average + weekly trend calculation.
- `app/Http/Requests/StoreBodyMeasurementRequest.php` — create.
- `app/Http/Controllers/BodyMeasurementController.php` — create: `store`, `index`.
- `resources/js/Pages/Body/Index.vue` — create: history + charts (weight w/ 7-day avg + goal line, waist over time) + entry form.
- `tests/Unit/WeightTrendTest.php` — create.
- `tests/Feature/BodyMeasurementTest.php` — create.

**Ciśnienie (blood pressure):**
- `database/migrations/xxxx_create_blood_pressure_readings_table.php` — create.
- `app/Models/BloodPressureReading.php` — create.
- `database/factories/BloodPressureReadingFactory.php` — create.
- `app/Http/Requests/StoreBloodPressureReadingRequest.php` — create.
- `app/Http/Controllers/BloodPressureReadingController.php` — create: `store`, `index`.
- `resources/js/Pages/Health/BloodPressure.vue` — create.
- `tests/Feature/BloodPressureReadingTest.php` — create.

**Badania krwi (lab results):**
- `database/migrations/xxxx_create_lab_markers_table.php` — create (shared, no `user_id`).
- `database/migrations/xxxx_create_lab_results_table.php` — create.
- `database/migrations/xxxx_create_lab_values_table.php` — create.
- `app/Models/LabMarker.php` — create.
- `app/Models/LabResult.php` — create.
- `app/Models/LabValue.php` — create.
- `database/factories/LabMarkerFactory.php`, `LabResultFactory.php`, `LabValueFactory.php` — create.
- `database/seeders/LabMarkerSeeder.php` — create: 8 predefined markers.
- `database/seeders/DatabaseSeeder.php` — modify: call `LabMarkerSeeder`.
- `app/Http/Requests/StoreLabResultRequest.php` — create.
- `app/Http/Requests/StoreLabMarkerRequest.php` — create.
- `app/Http/Controllers/LabResultController.php` — create: `index`, `create`, `store`.
- `app/Http/Controllers/LabMarkerController.php` — create: `store` (add a custom marker).
- `resources/js/Pages/Health/Labs/Index.vue` — create: history + trend charts with norm bands.
- `resources/js/Pages/Health/Labs/Create.vue` — create: backdated form, per-marker value inputs, add-custom-marker inline.
- `tests/Feature/LabResultTest.php` — create.

**Leki i suplementy (medications):**
- `database/migrations/xxxx_create_medications_table.php` — create.
- `app/Models/Medication.php` — create.
- `database/factories/MedicationFactory.php` — create.
- `app/Http/Requests/StoreMedicationRequest.php`, `UpdateMedicationRequest.php` — create.
- `app/Http/Controllers/MedicationController.php` — create: `index`, `store`, `update`.
- `resources/js/Pages/Health/Medications.vue` — create.
- `tests/Feature/MedicationTest.php` — create.

**Przypomnienia (reminders):**
- `database/migrations/xxxx_create_reminders_table.php` — create.
- `app/Models/Reminder.php` — create.
- `database/factories/ReminderFactory.php` — create.
- `app/Services/ReminderStatus.php` — create: days-until-due calculation.
- `app/Http/Requests/StoreReminderRequest.php` — create.
- `app/Http/Controllers/ReminderController.php` — create: `index`, `store`, `update` (mark done).
- `resources/js/Pages/Health/Reminders.vue` — create.
- `tests/Unit/ReminderStatusTest.php` — create.
- `tests/Feature/ReminderTest.php` — create.

**Dashboard v1:**
- `app/Http/Controllers/DashboardController.php` — create (replaces the closure route from M0).
- `routes/web.php` — modify: replace `/dashboard` closure with controller, add all new resource routes.
- `resources/js/Pages/Dashboard.vue` — modify: weight section (7-day avg, trend, distance to goal, inline quick-entry) + health section (last BP, days to next lab reminder).
- `resources/js/Layouts/AuthenticatedLayout.vue` — modify: add nav links to the new pages (Ciało, Ciśnienie, Badania, Leki, Przypomnienia).
- `tests/Feature/DashboardTest.php` — create.

---

## Task 1: Chart.js wiring + reusable `LineChart.vue`

**Files:**
- Modify: `package.json` (add `chart.js`, `vue-chartjs`)
- Create: `resources/js/Components/LineChart.vue`

**Interfaces:**
- Consumes: nothing.
- Produces: `<LineChart :labels="[...]" :datasets="[...]" :height="200" />` — every later task's chart uses this component. `datasets` is Chart.js's native dataset array (`[{ label, data, borderColor, ... }]`), so callers keep full control without `LineChart.vue` inventing its own prop shape.

- [ ] **Step 1: Install packages**

```bash
./vendor/bin/sail npm install chart.js@^4 vue-chartjs@^5
```

Expected: `package.json` `devDependencies` (matching the existing convention where all frontend deps in this project live under `devDependencies`) gains `chart.js` and `vue-chartjs`.

- [ ] **Step 2: Create the reusable chart component**

Create `resources/js/Components/LineChart.vue`:

```vue
<script setup>
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

const props = defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    height: { type: Number, default: 220 },
});

const chartData = () => ({
    labels: props.labels,
    datasets: props.datasets,
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'bottom' },
    },
    scales: {
        y: { beginAtZero: false },
    },
};
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Line :data="chartData()" :options="chartOptions" />
    </div>
</template>
```

- [ ] **Step 3: Verify it builds**

```bash
./vendor/bin/sail npm run build
```

Expected: no build errors (the component isn't used anywhere yet, but it must compile standalone — Vite tree-shakes unused files, so this only proves syntax correctness; real usage is verified in Task 4).

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json resources/js/Components/LineChart.vue
git commit -m "feat: add Chart.js + reusable LineChart component"
```

---

## Task 2: `BodyMeasurement` model + `WeightTrend` service (TDD)

**Files:**
- Create: `database/migrations/xxxx_create_body_measurements_table.php`
- Create: `app/Models/BodyMeasurement.php`
- Create: `database/factories/BodyMeasurementFactory.php`
- Create: `app/Services/WeightTrend.php`
- Test: `tests/Unit/WeightTrendTest.php`

**Interfaces:**
- Consumes: `BelongsToUser` trait (M0).
- Produces: `BodyMeasurement` model (`user_id`, `date`, `weight_kg`, `waist_cm` nullable). `WeightTrend::sevenDayAverage(User $user, ?Carbon $asOf = null): ?float` and `WeightTrend::weeklyTrend(User $user, ?Carbon $asOf = null): ?float` — Task 14 (Dashboard) calls both by these exact names.

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration create_body_measurements_table
```

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
```

One weigh-in per user per day — the unique index means a second submission on the same date must update, not duplicate (Task 3 handles this with `updateOrCreate`).

- [ ] **Step 2: Create the model**

Create `app/Models/BodyMeasurement.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodyMeasurement extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'date',
        'weight_kg',
        'waist_cm',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight_kg' => 'decimal:2',
            'waist_cm' => 'decimal:2',
        ];
    }
}
```

- [ ] **Step 3: Create the factory**

Create `database/factories/BodyMeasurementFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BodyMeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->unique()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'weight_kg' => fake()->randomFloat(2, 60, 120),
            'waist_cm' => fake()->optional()->randomFloat(2, 70, 120),
        ];
    }
}
```

- [ ] **Step 4: Write the failing test for `WeightTrend`**

Create `tests/Unit/WeightTrendTest.php`:

```php
<?php

use App\Models\BodyMeasurement;
use App\Models\User;
use App\Services\WeightTrend;
use Illuminate\Support\Carbon;

test('seven day average is the mean weight over the trailing 7 calendar days', function () {
    $user = User::factory()->create();
    $today = Carbon::parse('2026-08-25');

    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-24', 'weight_kg' => 92.0]);
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-19', 'weight_kg' => 94.0]);
    // Outside the trailing-7-day window (2026-08-18 is day 8 back) — must be excluded.
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-18', 'weight_kg' => 999.0]);

    expect(WeightTrend::sevenDayAverage($user, $today))->toBe(92.0);
});

test('seven day average is null when there is no data in the window', function () {
    $user = User::factory()->create();

    expect(WeightTrend::sevenDayAverage($user, Carbon::parse('2026-08-25')))->toBeNull();
});

test('weekly trend is the difference between this weeks average and last weeks', function () {
    $user = User::factory()->create();
    $today = Carbon::parse('2026-08-25');

    // This week (2026-08-19..25): avg 90.0
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);
    // Last week (2026-08-12..18): avg 90.4
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-18', 'weight_kg' => 90.4]);

    expect(WeightTrend::weeklyTrend($user, $today))->toBe(-0.4);
});

test('weekly trend is null when either window is missing data', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);

    expect(WeightTrend::weeklyTrend($user, Carbon::parse('2026-08-25')))->toBeNull();
});
```

- [ ] **Step 5: Run the test to confirm it fails**

```bash
./vendor/bin/sail artisan test --filter=WeightTrendTest
```

Expected: FAIL — `App\Services\WeightTrend` not found.

- [ ] **Step 6: Implement `WeightTrend`**

Create `app/Services/WeightTrend.php`:

```php
<?php

namespace App\Services;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Support\Carbon;

class WeightTrend
{
    public static function sevenDayAverage(User $user, ?Carbon $asOf = null): ?float
    {
        $asOf ??= Carbon::today();

        $average = BodyMeasurement::forUser($user)
            ->whereBetween('date', [
                $asOf->copy()->subDays(6)->toDateString(),
                $asOf->toDateString(),
            ])
            ->avg('weight_kg');

        return $average !== null ? round((float) $average, 1) : null;
    }

    public static function weeklyTrend(User $user, ?Carbon $asOf = null): ?float
    {
        $asOf ??= Carbon::today();

        $current = self::sevenDayAverage($user, $asOf);
        $previous = self::sevenDayAverage($user, $asOf->copy()->subDays(7));

        if ($current === null || $previous === null) {
            return null;
        }

        return round($current - $previous, 1);
    }
}
```

- [ ] **Step 7: Run the test to confirm it passes**

```bash
./vendor/bin/sail artisan test --filter=WeightTrendTest
```

Expected: PASS (4 tests).

- [ ] **Step 8: Migrate and commit**

```bash
./vendor/bin/sail artisan migrate
git add database/migrations database/factories/BodyMeasurementFactory.php app/Models/BodyMeasurement.php app/Services/WeightTrend.php tests/Unit/WeightTrendTest.php
git commit -m "feat: add BodyMeasurement model and WeightTrend service"
```

---

## Task 3: Weight/waist entry endpoint + test

**Files:**
- Create: `app/Http/Requests/StoreBodyMeasurementRequest.php`
- Create: `app/Http/Controllers/BodyMeasurementController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/BodyMeasurementTest.php`

**Interfaces:**
- Consumes: `BodyMeasurement` model (Task 2).
- Produces: `POST /body-measurements` (upserts today's-or-given-date entry), `GET /body-measurements` (history page, built out fully in Task 4). Task 14 (Dashboard) posts to the same `POST /body-measurements` route from its inline quick-entry form.

- [ ] **Step 1: Write the Form Request**

Create `app/Http/Requests/StoreBodyMeasurementRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBodyMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'waist_cm' => ['nullable', 'numeric', 'min:40', 'max:200'],
        ];
    }
}
```

- [ ] **Step 2: Write the controller**

Create `app/Http/Controllers/BodyMeasurementController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBodyMeasurementRequest;
use App\Models\BodyMeasurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BodyMeasurementController extends Controller
{
    public function index(Request $request): Response
    {
        $measurements = BodyMeasurement::forUser($request->user())
            ->orderBy('date')
            ->get(['date', 'weight_kg', 'waist_cm']);

        return Inertia::render('Body/Index', [
            'measurements' => $measurements,
            'weightGoalKg' => $request->user()->profile?->weight_goal_kg,
        ]);
    }

    public function store(StoreBodyMeasurementRequest $request): RedirectResponse
    {
        $request->user()->bodyMeasurements()->updateOrCreate(
            ['date' => $request->validated('date')],
            $request->validated()
        );

        return back()->with('status', 'body-measurement-saved');
    }
}
```

This calls `$request->user()->bodyMeasurements()` — add that relation now:

- [ ] **Step 3: Add the `bodyMeasurements()` relation to `User`**

In `app/Models/User.php`, add alongside the existing `profile()` relation:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function bodyMeasurements(): HasMany
{
    return $this->hasMany(BodyMeasurement::class);
}
```

(Add `use App\Models\BodyMeasurement;` to the top of the file if not already imported via the same namespace — it isn't, since `BodyMeasurement` lives in `App\Models` alongside `User`, no import needed.)

- [ ] **Step 4: Add routes**

In `routes/web.php`, inside the existing `Route::middleware('auth')->group(function () { ... })` block (next to the `/profile` routes):

```php
use App\Http\Controllers\BodyMeasurementController;

Route::get('/cialo', [BodyMeasurementController::class, 'index'])->name('body.index');
Route::post('/body-measurements', [BodyMeasurementController::class, 'store'])->name('body-measurements.store');
```

- [ ] **Step 5: Write the feature test**

Create `tests/Feature/BodyMeasurementTest.php`:

```php
<?php

use App\Models\BodyMeasurement;
use App\Models\User;

test('a user can log a new weight entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/body-measurements', [
            'date' => '2026-08-25',
            'weight_kg' => 89.5,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('body_measurements', [
        'user_id' => $user->id,
        'date' => '2026-08-25',
        'weight_kg' => 89.5,
    ]);
});

test('logging a second entry for the same date updates it instead of duplicating', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);

    $this->actingAs($user)->post('/body-measurements', [
        'date' => '2026-08-25',
        'weight_kg' => 89.0,
    ]);

    expect(BodyMeasurement::where('user_id', $user->id)->where('date', '2026-08-25')->count())->toBe(1)
        ->and(BodyMeasurement::where('user_id', $user->id)->first()->weight_kg)->toEqual(89.0);
});

test('a user cannot see another users body measurements on the history page', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    BodyMeasurement::factory()->for($userB)->create(['weight_kg' => 77.0]);

    $response = $this->actingAs($userA)->get('/cialo');

    $response->assertInertia(fn ($page) => $page->component('Body/Index')->has('measurements', 0));
});

test('a guest cannot log a weight entry', function () {
    $this->post('/body-measurements', ['date' => '2026-08-25', 'weight_kg' => 80])
        ->assertRedirect('/login');
});
```

- [ ] **Step 6: Run tests**

```bash
./vendor/bin/sail artisan test --filter=BodyMeasurementTest
```

Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/StoreBodyMeasurementRequest.php app/Http/Controllers/BodyMeasurementController.php app/Models/User.php routes/web.php tests/Feature/BodyMeasurementTest.php
git commit -m "feat: add body measurement entry endpoint"
```

---

## Task 4: Body history page — charts + quick-entry form

**Files:**
- Create: `resources/js/Pages/Body/Index.vue`

**Interfaces:**
- Consumes: `LineChart.vue` (Task 1), `measurements`/`weightGoalKg` Inertia props from `BodyMeasurementController@index` (Task 3).
- Produces: nothing further consumed by later tasks (leaf page).

- [ ] **Step 1: Build the page**

Create `resources/js/Pages/Body/Index.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    measurements: { type: Array, default: () => [] },
    weightGoalKg: { type: [Number, String, null], default: null },
});

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    weight_kg: '',
    waist_cm: '',
});

const submit = () => {
    form.post(route('body-measurements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('weight_kg', 'waist_cm'),
    });
};

const weightLabels = computed(() => props.measurements.map((m) => m.date));

const weightDatasets = computed(() => {
    const datasets = [
        {
            label: 'Waga (kg)',
            data: props.measurements.map((m) => Number(m.weight_kg)),
            borderColor: '#4f46e5',
            tension: 0.2,
        },
    ];

    if (props.weightGoalKg) {
        datasets.push({
            label: 'Cel',
            data: props.measurements.map(() => Number(props.weightGoalKg)),
            borderColor: '#dc2626',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    return datasets;
});

const waistMeasurements = computed(() => props.measurements.filter((m) => m.waist_cm !== null));
const waistLabels = computed(() => waistMeasurements.value.map((m) => m.date));
const waistDatasets = computed(() => [
    {
        label: 'Obwód pasa (cm)',
        data: waistMeasurements.value.map((m) => Number(m.waist_cm)),
        borderColor: '#0891b2',
        tension: 0.2,
    },
]);
</script>

<template>
    <Head title="Ciało" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ciało</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowy wpis</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="date" value="Data" />
                            <TextInput id="date" type="date" class="mt-1 block w-full" v-model="form.date" max="9999-12-31" />
                            <InputError class="mt-2" :message="form.errors.date" />
                        </div>
                        <div>
                            <InputLabel for="weight_kg" value="Waga (kg)" />
                            <TextInput id="weight_kg" type="number" step="0.1" class="mt-1 block w-full" v-model="form.weight_kg" autofocus />
                            <InputError class="mt-2" :message="form.errors.weight_kg" />
                        </div>
                        <div>
                            <InputLabel for="waist_cm" value="Obwód pasa (cm, opcjonalnie)" />
                            <TextInput id="waist_cm" type="number" step="0.1" class="mt-1 block w-full" v-model="form.waist_cm" />
                            <InputError class="mt-2" :message="form.errors.waist_cm" />
                        </div>
                        <div class="sm:col-span-3">
                            <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-gray-600">Zapisano.</span>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Waga w czasie</h3>
                    <LineChart v-if="measurements.length" :labels="weightLabels" :datasets="weightDatasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych wpisów.</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Obwód pasa w czasie</h3>
                    <LineChart v-if="waistMeasurements.length" :labels="waistLabels" :datasets="waistDatasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych pomiarów obwodu pasa.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Build and verify**

```bash
./vendor/bin/sail npm run build
```

Expected: no build errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Body/Index.vue
git commit -m "feat: body history page with weight/waist charts and entry form"
```

---

## Task 5: `BloodPressureReading` model + entry/history

**Files:**
- Create: `database/migrations/xxxx_create_blood_pressure_readings_table.php`
- Create: `app/Models/BloodPressureReading.php`
- Create: `database/factories/BloodPressureReadingFactory.php`
- Create: `app/Http/Requests/StoreBloodPressureReadingRequest.php`
- Create: `app/Http/Controllers/BloodPressureReadingController.php`
- Modify: `routes/web.php`, `app/Models/User.php`
- Create: `resources/js/Pages/Health/BloodPressure.vue`
- Test: `tests/Feature/BloodPressureReadingTest.php`

**Interfaces:**
- Consumes: `BelongsToUser` (M0), `LineChart.vue` (Task 1).
- Produces: `BloodPressureReading` model (`user_id`, `measured_at` datetime, `systolic`, `diastolic`, `resting_pulse` nullable). Task 14 (Dashboard) reads the latest reading via `BloodPressureReading::forUser($user)->latest('measured_at')->first()`.

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration create_blood_pressure_readings_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_pressure_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('measured_at');
            $table->unsignedSmallInteger('systolic');
            $table->unsignedSmallInteger('diastolic');
            $table->unsignedSmallInteger('resting_pulse')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_pressure_readings');
    }
};
```

- [ ] **Step 2: Create the model**

Create `app/Models/BloodPressureReading.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodPressureReading extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'measured_at',
        'systolic',
        'diastolic',
        'resting_pulse',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 3: Create the factory**

Create `database/factories/BloodPressureReadingFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodPressureReadingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'systolic' => fake()->numberBetween(110, 135),
            'diastolic' => fake()->numberBetween(70, 88),
            'resting_pulse' => fake()->optional()->numberBetween(55, 80),
        ];
    }
}
```

- [ ] **Step 4: Add the `bloodPressureReadings()` relation to `User`**

In `app/Models/User.php`, add next to `bodyMeasurements()`:

```php
public function bloodPressureReadings(): HasMany
{
    return $this->hasMany(BloodPressureReading::class);
}
```

- [ ] **Step 5: Write the Form Request**

Create `app/Http/Requests/StoreBloodPressureReadingRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloodPressureReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measured_at' => ['required', 'date', 'before_or_equal:now'],
            'systolic' => ['required', 'integer', 'min:60', 'max:260'],
            'diastolic' => ['required', 'integer', 'min:40', 'max:200'],
            'resting_pulse' => ['nullable', 'integer', 'min:30', 'max:220'],
        ];
    }
}
```

- [ ] **Step 6: Write the controller**

Create `app/Http/Controllers/BloodPressureReadingController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodPressureReadingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BloodPressureReadingController extends Controller
{
    public function index(Request $request): Response
    {
        $readings = $request->user()->bloodPressureReadings()
            ->orderBy('measured_at')
            ->get(['measured_at', 'systolic', 'diastolic', 'resting_pulse']);

        return Inertia::render('Health/BloodPressure', [
            'readings' => $readings,
        ]);
    }

    public function store(StoreBloodPressureReadingRequest $request): RedirectResponse
    {
        $request->user()->bloodPressureReadings()->create($request->validated());

        return back()->with('status', 'blood-pressure-saved');
    }
}
```

- [ ] **Step 7: Add routes**

In `routes/web.php`, inside the `auth` middleware group:

```php
use App\Http\Controllers\BloodPressureReadingController;

Route::get('/cisnienie', [BloodPressureReadingController::class, 'index'])->name('blood-pressure.index');
Route::post('/blood-pressure-readings', [BloodPressureReadingController::class, 'store'])->name('blood-pressure-readings.store');
```

- [ ] **Step 8: Build the Vue page**

Create `resources/js/Pages/Health/BloodPressure.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    readings: { type: Array, default: () => [] },
});

const form = useForm({
    measured_at: new Date().toISOString().slice(0, 16),
    systolic: '',
    diastolic: '',
    resting_pulse: '',
});

const submit = () => {
    form.post(route('blood-pressure-readings.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('systolic', 'diastolic', 'resting_pulse'),
    });
};

const labels = computed(() => props.readings.map((r) => r.measured_at));
const datasets = computed(() => [
    { label: 'Skurczowe', data: props.readings.map((r) => r.systolic), borderColor: '#dc2626', tension: 0.2 },
    { label: 'Rozkurczowe', data: props.readings.map((r) => r.diastolic), borderColor: '#2563eb', tension: 0.2 },
]);
</script>

<template>
    <Head title="Ciśnienie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ciśnienie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowy pomiar</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div>
                            <InputLabel for="measured_at" value="Data i godzina" />
                            <TextInput id="measured_at" type="datetime-local" class="mt-1 block w-full" v-model="form.measured_at" />
                            <InputError class="mt-2" :message="form.errors.measured_at" />
                        </div>
                        <div>
                            <InputLabel for="systolic" value="Skurczowe" />
                            <TextInput id="systolic" type="number" class="mt-1 block w-full" v-model="form.systolic" />
                            <InputError class="mt-2" :message="form.errors.systolic" />
                        </div>
                        <div>
                            <InputLabel for="diastolic" value="Rozkurczowe" />
                            <TextInput id="diastolic" type="number" class="mt-1 block w-full" v-model="form.diastolic" />
                            <InputError class="mt-2" :message="form.errors.diastolic" />
                        </div>
                        <div>
                            <InputLabel for="resting_pulse" value="Tętno spoczynkowe (opcjonalnie)" />
                            <TextInput id="resting_pulse" type="number" class="mt-1 block w-full" v-model="form.resting_pulse" />
                            <InputError class="mt-2" :message="form.errors.resting_pulse" />
                        </div>
                        <div class="sm:col-span-4">
                            <PrimaryButton :disabled="form.processing">Zapisz</PrimaryButton>
                            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-gray-600">Zapisano.</span>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historia</h3>
                    <LineChart v-if="readings.length" :labels="labels" :datasets="datasets" />
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych pomiarów.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 9: Write the feature test**

Create `tests/Feature/BloodPressureReadingTest.php`:

```php
<?php

use App\Models\BloodPressureReading;
use App\Models\User;

test('a user can log a blood pressure reading', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/blood-pressure-readings', [
            'measured_at' => '2026-08-25 08:00:00',
            'systolic' => 125,
            'diastolic' => 80,
            'resting_pulse' => 62,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('blood_pressure_readings', [
        'user_id' => $user->id,
        'systolic' => 125,
        'diastolic' => 80,
    ]);
});

test('a user only sees their own readings on the history page', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    BloodPressureReading::factory()->for($userB)->create();

    $response = $this->actingAs($userA)->get('/cisnienie');

    $response->assertInertia(fn ($page) => $page->component('Health/BloodPressure')->has('readings', 0));
});

test('a guest cannot log a blood pressure reading', function () {
    $this->post('/blood-pressure-readings', ['measured_at' => now(), 'systolic' => 120, 'diastolic' => 80])
        ->assertRedirect('/login');
});
```

- [ ] **Step 10: Migrate, run tests, commit**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --filter=BloodPressureReadingTest
./vendor/bin/sail npm run build
git add database/migrations database/factories/BloodPressureReadingFactory.php app/Models/BloodPressureReading.php app/Models/User.php app/Http/Requests/StoreBloodPressureReadingRequest.php app/Http/Controllers/BloodPressureReadingController.php routes/web.php resources/js/Pages/Health/BloodPressure.vue tests/Feature/BloodPressureReadingTest.php
git commit -m "feat: blood pressure logging with history chart"
```

---

## Task 6: Lab markers dictionary (shared) + predefined seed

**Files:**
- Create: `database/migrations/xxxx_create_lab_markers_table.php`
- Create: `app/Models/LabMarker.php`
- Create: `database/factories/LabMarkerFactory.php`
- Create: `database/seeders/LabMarkerSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/LabMarkerSeederTest.php`

**Interfaces:**
- Consumes: nothing (first lab-related task).
- Produces: `LabMarker` model — **no `BelongsToUser`, no `user_id`** (shared dictionary per spec §6). Fields: `name`, `unit`, `norm_min` nullable, `norm_max` nullable, `is_predefined` bool. Task 7/9/10 reference these exact field names.

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration create_lab_markers_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_markers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->decimal('norm_min', 8, 2)->nullable();
            $table->decimal('norm_max', 8, 2)->nullable();
            $table->boolean('is_predefined')->default(false);
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_markers');
    }
};
```

No `user_id` — this is intentional (shared dictionary, spec §6).

- [ ] **Step 2: Create the model**

Create `app/Models/LabMarker.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabMarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'norm_min',
        'norm_max',
        'is_predefined',
    ];

    protected function casts(): array
    {
        return [
            'norm_min' => 'decimal:2',
            'norm_max' => 'decimal:2',
            'is_predefined' => 'boolean',
        ];
    }
}
```

Deliberately no `use BelongsToUser` — this model is shared across both users by design.

- [ ] **Step 3: Create the factory**

Create `database/factories/LabMarkerFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LabMarkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'unit' => 'mg/dl',
            'norm_min' => null,
            'norm_max' => fake()->randomFloat(2, 100, 200),
            'is_predefined' => false,
        ];
    }
}
```

- [ ] **Step 4: Create the seeder with the 8 predefined markers**

Create `database/seeders/LabMarkerSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\LabMarker;
use Illuminate\Database\Seeder;

class LabMarkerSeeder extends Seeder
{
    /**
     * Reference ranges are general, commonly-cited defaults for context
     * only — not medical advice. Editable per spec by adding custom
     * markers alongside these; predefined ranges are not user-editable in M1.
     */
    public function run(): void
    {
        $markers = [
            ['name' => 'Cholesterol całkowity', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 200],
            ['name' => 'LDL', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 100],
            ['name' => 'HDL', 'unit' => 'mg/dl', 'norm_min' => 40, 'norm_max' => null],
            ['name' => 'Trójglicerydy', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 150],
            ['name' => 'ALT', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 41],
            ['name' => 'AST', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 40],
            ['name' => 'GGTP', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 60],
            ['name' => 'Glukoza', 'unit' => 'mg/dl', 'norm_min' => 70, 'norm_max' => 99],
        ];

        foreach ($markers as $marker) {
            LabMarker::firstOrCreate(
                ['name' => $marker['name']],
                [...$marker, 'is_predefined' => true]
            );
        }
    }
}
```

`firstOrCreate` keyed on `name` — safe to re-run, matches the idempotent-seeder convention from M0.

- [ ] **Step 5: Wire it into `DatabaseSeeder`**

In `database/seeders/DatabaseSeeder.php`, add at the end of the `run()` method (after the two users are seeded):

```php
$this->call(LabMarkerSeeder::class);
```

- [ ] **Step 6: Write the test**

Create `tests/Feature/LabMarkerSeederTest.php`:

```php
<?php

use App\Models\LabMarker;

test('seeding creates exactly the 8 predefined lab markers', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);

    expect(LabMarker::where('is_predefined', true)->count())->toBe(8)
        ->and(LabMarker::where('name', 'LDL')->first()->norm_max)->toEqual(100.00);
});

test('re-seeding lab markers is idempotent', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);

    expect(LabMarker::count())->toBe(8);
});
```

- [ ] **Step 7: Migrate, run tests, commit**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --filter=LabMarkerSeederTest
git add database/migrations database/factories/LabMarkerFactory.php database/seeders/LabMarkerSeeder.php database/seeders/DatabaseSeeder.php app/Models/LabMarker.php tests/Feature/LabMarkerSeederTest.php
git commit -m "feat: shared lab marker dictionary with 8 predefined markers"
```

---

## Task 7: `LabResult` + `LabValue` models

**Files:**
- Create: `database/migrations/xxxx_create_lab_results_table.php`
- Create: `database/migrations/xxxx_create_lab_values_table.php`
- Create: `app/Models/LabResult.php`
- Create: `app/Models/LabValue.php`
- Create: `database/factories/LabResultFactory.php`, `database/factories/LabValueFactory.php`
- Modify: `app/Models/User.php`, `app/Models/LabMarker.php`
- Test: `tests/Feature/LabResultIsolationTest.php`

**Interfaces:**
- Consumes: `BelongsToUser` (M0), `LabMarker` (Task 6).
- Produces: `LabResult` (`user_id`, `performed_at` date, `note` nullable) with `values(): HasMany` → `LabValue`. `LabValue` (`lab_result_id`, `lab_marker_id`, `value`) — no `user_id` (subordinate to `LabResult`, per Global Constraints). Task 8 (entry form) and Task 9 (trends) consume `LabResult::values` and `LabValue::marker`.

- [ ] **Step 1: Create the migrations**

```bash
./vendor/bin/sail artisan make:migration create_lab_results_table
./vendor/bin/sail artisan make:migration create_lab_values_table
```

`create_lab_results_table`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('performed_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
```

`create_lab_values_table` (must run after `lab_results` and `lab_markers` exist — verify with `ls database/migrations` that this file's timestamp sorts after both; if `artisan make:migration` gave it an earlier timestamp than `create_lab_markers_table` from Task 6, that's fine since Task 6 already ran, but double check `create_lab_results_table` above still sorts before this one):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_marker_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 8, 2);
            $table->timestamps();

            $table->unique(['lab_result_id', 'lab_marker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_values');
    }
};
```

No `user_id` on `lab_values` — ownership is inherited through `lab_result_id`.

- [ ] **Step 2: Create the models**

Create `app/Models/LabResult.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabResult extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'performed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(LabValue::class);
    }
}
```

Create `app/Models/LabValue.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_result_id',
        'lab_marker_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function labResult(): BelongsTo
    {
        return $this->belongsTo(LabResult::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(LabMarker::class, 'lab_marker_id');
    }
}
```

No `BelongsToUser` on `LabValue` — it has no `user_id` column; ownership comes from `labResult`.

- [ ] **Step 3: Create the factories**

Create `database/factories/LabResultFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'performed_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'note' => null,
        ];
    }
}
```

Create `database/factories/LabValueFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\LabMarker;
use App\Models\LabResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lab_result_id' => LabResult::factory(),
            'lab_marker_id' => LabMarker::factory(),
            'value' => fake()->randomFloat(2, 10, 250),
        ];
    }
}
```

- [ ] **Step 4: Add relations**

In `app/Models/User.php`, add:

```php
public function labResults(): HasMany
{
    return $this->hasMany(LabResult::class);
}
```

In `app/Models/LabMarker.php`, add:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function values(): HasMany
{
    return $this->hasMany(LabValue::class);
}
```

- [ ] **Step 5: Write the isolation test**

Create `tests/Feature/LabResultIsolationTest.php`:

```php
<?php

use App\Models\LabResult;
use App\Models\LabValue;
use App\Models\User;

test('a user never sees another users lab results', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    LabResult::factory()->for($userA)->create();
    LabResult::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(LabResult::count())->toBe(1)
        ->and(LabResult::first()->user_id)->toBe($userA->id);
});

test('lab values are reachable only through their owning users lab result', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $resultA = LabResult::factory()->for($userA)->create();
    $resultB = LabResult::factory()->for($userB)->create();
    LabValue::factory()->for($resultA, 'labResult')->create();
    LabValue::factory()->for($resultB, 'labResult')->create();

    $this->actingAs($userA);

    // LabResult::first() is already scoped to userA; its values() relation
    // is a plain hasMany with no independent scope, but since it can only
    // be reached starting from an already-scoped LabResult, isolation holds.
    expect(LabResult::first()->values()->count())->toBe(1);
});
```

- [ ] **Step 6: Migrate, run tests, commit**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --filter=LabResultIsolationTest
git add database/migrations database/factories/LabResultFactory.php database/factories/LabValueFactory.php app/Models/LabResult.php app/Models/LabValue.php app/Models/LabMarker.php app/Models/User.php tests/Feature/LabResultIsolationTest.php
git commit -m "feat: add LabResult/LabValue models"
```

---

## Task 8: Lab result entry (backdated form, per-marker values, custom marker)

**Files:**
- Create: `app/Http/Requests/StoreLabResultRequest.php`
- Create: `app/Http/Requests/StoreLabMarkerRequest.php`
- Create: `app/Http/Controllers/LabResultController.php`
- Create: `app/Http/Controllers/LabMarkerController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Health/Labs/Create.vue`
- Test: `tests/Feature/LabResultTest.php`

**Interfaces:**
- Consumes: `LabResult`, `LabValue`, `LabMarker` (Task 6/7).
- Produces: `GET /badania/nowe` (form), `POST /lab-results` (creates a result + its values in one transaction), `POST /lab-markers` (adds a custom marker to the shared dictionary). Task 9 (trends page) reads what this task writes.

- [ ] **Step 1: Write the Form Requests**

Create `app/Http/Requests/StoreLabResultRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'performed_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:2000'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.lab_marker_id' => ['required', 'integer', 'exists:lab_markers,id'],
            'values.*.value' => ['required', 'numeric', 'min:0', 'max:99999'],
        ];
    }
}
```

Create `app/Http/Requests/StoreLabMarkerRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabMarkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:lab_markers,name'],
            'unit' => ['required', 'string', 'max:50'],
            'norm_min' => ['nullable', 'numeric'],
            'norm_max' => ['nullable', 'numeric', 'gte:norm_min'],
        ];
    }
}
```

- [ ] **Step 2: Write the controllers**

Create `app/Http/Controllers/LabResultController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabResultRequest;
use App\Models\LabMarker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LabResultController extends Controller
{
    public function index(Request $request): Response
    {
        $results = $request->user()->labResults()
            ->with('values.marker')
            ->orderBy('performed_at')
            ->get();

        return Inertia::render('Health/Labs/Index', [
            'results' => $results,
            'markers' => LabMarker::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Health/Labs/Create', [
            'markers' => LabMarker::orderBy('name')->get(),
        ]);
    }

    public function store(StoreLabResultRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $result = $request->user()->labResults()->create([
                'performed_at' => $request->validated('performed_at'),
                'note' => $request->validated('note'),
            ]);

            $result->values()->createMany($request->validated('values'));
        });

        return redirect()->route('lab-results.index')->with('status', 'lab-result-saved');
    }
}
```

Create `app/Http/Controllers/LabMarkerController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabMarkerRequest;
use App\Models\LabMarker;
use Illuminate\Http\RedirectResponse;

class LabMarkerController extends Controller
{
    public function store(StoreLabMarkerRequest $request): RedirectResponse
    {
        LabMarker::create([
            ...$request->validated(),
            'is_predefined' => false,
        ]);

        return back()->with('status', 'lab-marker-added');
    }
}
```

- [ ] **Step 3: Add routes**

In `routes/web.php`, inside the `auth` middleware group:

```php
use App\Http\Controllers\LabMarkerController;
use App\Http\Controllers\LabResultController;

Route::get('/badania', [LabResultController::class, 'index'])->name('lab-results.index');
Route::get('/badania/nowe', [LabResultController::class, 'create'])->name('lab-results.create');
Route::post('/lab-results', [LabResultController::class, 'store'])->name('lab-results.store');
Route::post('/lab-markers', [LabMarkerController::class, 'store'])->name('lab-markers.store');
```

- [ ] **Step 4: Build the entry form**

Create `resources/js/Pages/Health/Labs/Create.vue`:

```vue
<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    markers: { type: Array, required: true },
});

const form = useForm({
    performed_at: new Date().toISOString().slice(0, 10),
    note: '',
    values: props.markers.map((marker) => ({ lab_marker_id: marker.id, value: '' })),
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        values: data.values.filter((v) => v.value !== '' && v.value !== null),
    })).post(route('lab-results.store'));
};

const showNewMarkerForm = ref(false);
const newMarkerForm = useForm({ name: '', unit: 'mg/dl', norm_min: '', norm_max: '' });

const submitNewMarker = () => {
    newMarkerForm.post(route('lab-markers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newMarkerForm.reset();
            showNewMarkerForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Nowe badanie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nowe badanie krwi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="performed_at" value="Data wykonania" />
                            <TextInput id="performed_at" type="date" class="mt-1 block w-full" v-model="form.performed_at" />
                            <InputError class="mt-2" :message="form.errors.performed_at" />
                            <p class="mt-1 text-xs text-gray-500">Można wpisać dowolną datę wsteczną, żeby uzupełnić historię.</p>
                        </div>

                        <div v-for="(row, index) in form.values" :key="row.lab_marker_id" class="grid grid-cols-2 gap-4 items-end">
                            <InputLabel :value="`${markers[index].name} (${markers[index].unit})`" />
                            <div>
                                <TextInput type="number" step="0.01" class="mt-1 block w-full" v-model="row.value" />
                                <InputError class="mt-2" :message="form.errors[`values.${index}.value`]" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="note" value="Notatka (opcjonalnie)" />
                            <textarea id="note" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" v-model="form.note" rows="2"></textarea>
                        </div>

                        <PrimaryButton :disabled="form.processing">Zapisz badanie</PrimaryButton>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <button type="button" class="text-sm text-indigo-600 hover:underline" @click="showNewMarkerForm = !showNewMarkerForm">
                        + Dodaj własny marker
                    </button>

                    <form v-if="showNewMarkerForm" @submit.prevent="submitNewMarker" class="mt-4 grid grid-cols-2 gap-4 items-end">
                        <div>
                            <InputLabel for="new_marker_name" value="Nazwa" />
                            <TextInput id="new_marker_name" class="mt-1 block w-full" v-model="newMarkerForm.name" />
                            <InputError class="mt-2" :message="newMarkerForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_unit" value="Jednostka" />
                            <TextInput id="new_marker_unit" class="mt-1 block w-full" v-model="newMarkerForm.unit" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_norm_min" value="Norma min (opcjonalnie)" />
                            <TextInput id="new_marker_norm_min" type="number" step="0.01" class="mt-1 block w-full" v-model="newMarkerForm.norm_min" />
                        </div>
                        <div>
                            <InputLabel for="new_marker_norm_max" value="Norma max (opcjonalnie)" />
                            <TextInput id="new_marker_norm_max" type="number" step="0.01" class="mt-1 block w-full" v-model="newMarkerForm.norm_max" />
                            <InputError class="mt-2" :message="newMarkerForm.errors.norm_max" />
                        </div>
                        <div class="col-span-2">
                            <SecondaryButton :disabled="newMarkerForm.processing">Dodaj marker</SecondaryButton>
                            <p class="mt-2 text-xs text-gray-500">Nowy marker pojawi się przy następnym wejściu na tę stronę.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

Note: a newly-added custom marker won't retroactively appear in `form.values` on this already-loaded page (it's built from the `markers` prop at page load) — the note under the form says so explicitly rather than attempting a live-reload, which would be over-engineering for M1.

- [ ] **Step 5: Write the feature test**

Create `tests/Feature/LabResultTest.php`:

```php
<?php

use App\Models\LabMarker;
use App\Models\LabResult;
use App\Models\User;

test('a user can save a lab result with values for multiple markers', function () {
    $user = User::factory()->create();
    $cholesterol = LabMarker::factory()->create(['name' => 'Cholesterol całkowity']);
    $glucose = LabMarker::factory()->create(['name' => 'Glukoza']);

    $this->actingAs($user)
        ->post('/lab-results', [
            'performed_at' => '2025-01-15',
            'note' => 'Na czczo',
            'values' => [
                ['lab_marker_id' => $cholesterol->id, 'value' => 190.5],
                ['lab_marker_id' => $glucose->id, 'value' => 88],
            ],
        ])
        ->assertRedirect(route('lab-results.index'));

    $result = LabResult::where('user_id', $user->id)->first();
    expect($result->performed_at->toDateString())->toBe('2025-01-15')
        ->and($result->values()->count())->toBe(2);
});

test('a lab result can use a backdated date from over a year ago', function () {
    $user = User::factory()->create();
    $marker = LabMarker::factory()->create();

    $this->actingAs($user)->post('/lab-results', [
        'performed_at' => '2020-03-01',
        'values' => [['lab_marker_id' => $marker->id, 'value' => 100]],
    ])->assertRedirect();

    $this->assertDatabaseHas('lab_results', ['user_id' => $user->id, 'performed_at' => '2020-03-01']);
});

test('a user can add a custom lab marker to the shared dictionary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/lab-markers', [
        'name' => 'Witamina D',
        'unit' => 'ng/ml',
        'norm_min' => 30,
        'norm_max' => 50,
    ])->assertRedirect();

    $this->assertDatabaseHas('lab_markers', ['name' => 'Witamina D', 'is_predefined' => false]);
});

test('a guest cannot save a lab result', function () {
    $marker = LabMarker::factory()->create();

    $this->post('/lab-results', [
        'performed_at' => '2026-01-01',
        'values' => [['lab_marker_id' => $marker->id, 'value' => 10]],
    ])->assertRedirect('/login');
});
```

- [ ] **Step 6: Run tests, build, commit**

```bash
./vendor/bin/sail artisan test --filter=LabResultTest
./vendor/bin/sail npm run build
git add app/Http/Requests/StoreLabResultRequest.php app/Http/Requests/StoreLabMarkerRequest.php app/Http/Controllers/LabResultController.php app/Http/Controllers/LabMarkerController.php routes/web.php resources/js/Pages/Health/Labs/Create.vue tests/Feature/LabResultTest.php
git commit -m "feat: lab result entry with backdated date and custom markers"
```

---

## Task 9: Lab trends page with norm bands

**Files:**
- Create: `resources/js/Pages/Health/Labs/Index.vue`

**Interfaces:**
- Consumes: `results`/`markers` Inertia props from `LabResultController@index` (Task 8), `LineChart.vue` (Task 1).
- Produces: nothing further (leaf page).

- [ ] **Step 1: Build the page**

Create `resources/js/Pages/Health/Labs/Index.vue`:

```vue
<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart from '@/Components/LineChart.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    results: { type: Array, default: () => [] },
    markers: { type: Array, default: () => [] },
});

const markersWithHistory = computed(() =>
    props.markers
        .map((marker) => {
            const points = props.results
                .flatMap((result) =>
                    result.values
                        .filter((v) => v.lab_marker_id === marker.id)
                        .map((v) => ({ date: result.performed_at, value: Number(v.value) }))
                )
                .sort((a, b) => a.date.localeCompare(b.date));

            return { marker, points };
        })
        .filter((entry) => entry.points.length > 0)
);

const datasetsFor = (entry) => {
    const datasets = [
        {
            label: `${entry.marker.name} (${entry.marker.unit})`,
            data: entry.points.map((p) => p.value),
            borderColor: '#4f46e5',
            tension: 0.2,
        },
    ];

    if (entry.marker.norm_max !== null) {
        datasets.push({
            label: 'Norma max',
            data: entry.points.map(() => Number(entry.marker.norm_max)),
            borderColor: '#dc2626',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    if (entry.marker.norm_min !== null) {
        datasets.push({
            label: 'Norma min',
            data: entry.points.map(() => Number(entry.marker.norm_min)),
            borderColor: '#f59e0b',
            borderDash: [6, 6],
            pointRadius: 0,
        });
    }

    return datasets;
};
</script>

<template>
    <Head title="Badania krwi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Badania krwi</h2>
                <Link :href="route('lab-results.create')">
                    <PrimaryButton>+ Nowe badanie</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <p class="text-xs text-gray-500">
                    Zaznaczone normy to ogólne wartości referencyjne — nie stanowią porady lekarskiej.
                </p>

                <div v-if="!markersWithHistory.length" class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    Brak jeszcze żadnych wyników. Kliknij „Nowe badanie", żeby dodać pierwsze — także z datą wsteczną.
                </div>

                <div v-for="entry in markersWithHistory" :key="entry.marker.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ entry.marker.name }}</h3>
                    <LineChart :labels="entry.points.map((p) => p.date)" :datasets="datasetsFor(entry)" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Build and verify**

```bash
./vendor/bin/sail npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Health/Labs/Index.vue
git commit -m "feat: lab result trend charts with norm bands"
```

---

## Task 10: `Medication` model + CRUD

**Files:**
- Create: `database/migrations/xxxx_create_medications_table.php`
- Create: `app/Models/Medication.php`
- Create: `database/factories/MedicationFactory.php`
- Create: `app/Http/Requests/StoreMedicationRequest.php`, `UpdateMedicationRequest.php`
- Create: `app/Http/Controllers/MedicationController.php`
- Modify: `routes/web.php`, `app/Models/User.php`
- Create: `resources/js/Pages/Health/Medications.vue`
- Test: `tests/Feature/MedicationTest.php`

**Interfaces:**
- Consumes: `BelongsToUser` (M0).
- Produces: `Medication` model (`user_id`, `name`, `dose`, `started_at`, `stopped_at` nullable). "Status" (aktywny/odstawiony) is derived from `stopped_at === null`, not stored as a separate column — one source of truth.

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration create_medications_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('dose');
            $table->date('started_at');
            $table->date('stopped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
```

- [ ] **Step 2: Create the model**

Create `app/Models/Medication.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'dose',
        'started_at',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'stopped_at' => 'date',
        ];
    }

    public function isActive(): bool
    {
        return $this->stopped_at === null;
    }
}
```

- [ ] **Step 3: Create the factory**

Create `database/factories/MedicationFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Omega-3', 'Witamina D', 'Atorwastatyna']),
            'dose' => fake()->randomElement(['1000 mg', '2000 IU', '10 mg']),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'stopped_at' => null,
        ];
    }
}
```

- [ ] **Step 4: Add the relation to `User`**

```php
public function medications(): HasMany
{
    return $this->hasMany(Medication::class);
}
```

- [ ] **Step 5: Write the Form Requests**

Create `app/Http/Requests/StoreMedicationRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dose' => ['required', 'string', 'max:100'],
            'started_at' => ['required', 'date'],
        ];
    }
}
```

Create `app/Http/Requests/UpdateMedicationRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('medication')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'stopped_at' => ['nullable', 'date'],
        ];
    }
}
```

`authorize()` here does an explicit ownership check because this route takes a route-model-bound `{medication}` id directly — unlike `profile/details`, which always operates on `$request->user()`'s own row, this endpoint could otherwise be pointed at another user's medication id. The global scope on `Medication` (via `BelongsToUser`) already makes route-model binding 404 for another user's id before `authorize()` even runs, but the explicit check makes the intent self-documenting and safe if the binding logic ever changes.

- [ ] **Step 6: Write the controller**

Create `app/Http/Controllers/MedicationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Health/Medications', [
            'medications' => $request->user()->medications()->orderByDesc('started_at')->get(),
        ]);
    }

    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        $request->user()->medications()->create($request->validated());

        return back()->with('status', 'medication-added');
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $medication->update($request->validated());

        return back()->with('status', 'medication-updated');
    }
}
```

- [ ] **Step 7: Add routes**

In `routes/web.php`, inside the `auth` middleware group:

```php
use App\Http\Controllers\MedicationController;

Route::get('/leki', [MedicationController::class, 'index'])->name('medications.index');
Route::post('/medications', [MedicationController::class, 'store'])->name('medications.store');
Route::patch('/medications/{medication}', [MedicationController::class, 'update'])->name('medications.update');
```

- [ ] **Step 8: Build the Vue page**

Create `resources/js/Pages/Health/Medications.vue`:

```vue
<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    medications: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    dose: '',
    started_at: new Date().toISOString().slice(0, 10),
});

const submit = () => {
    form.post(route('medications.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const stop = (medication) => {
    useForm({ stopped_at: new Date().toISOString().slice(0, 10) }).patch(
        route('medications.update', medication.id),
        { preserveScroll: true }
    );
};
</script>

<template>
    <Head title="Leki i suplementy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leki i suplementy</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dodaj</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="name" value="Nazwa" />
                            <TextInput id="name" class="mt-1 block w-full" v-model="form.name" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="dose" value="Dawka" />
                            <TextInput id="dose" class="mt-1 block w-full" v-model="form.dose" />
                            <InputError class="mt-2" :message="form.errors.dose" />
                        </div>
                        <div>
                            <InputLabel for="started_at" value="Od kiedy" />
                            <TextInput id="started_at" type="date" class="mt-1 block w-full" v-model="form.started_at" />
                            <InputError class="mt-2" :message="form.errors.started_at" />
                        </div>
                        <div class="sm:col-span-3">
                            <PrimaryButton :disabled="form.processing">Dodaj</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y">
                    <div v-for="medication in medications" :key="medication.id" class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ medication.name }} — {{ medication.dose }}</p>
                            <p class="text-sm text-gray-500">
                                od {{ medication.started_at }}
                                <span v-if="medication.stopped_at"> · odstawiony {{ medication.stopped_at }}</span>
                                <span v-else class="text-green-700"> · aktywny</span>
                            </p>
                        </div>
                        <SecondaryButton v-if="!medication.stopped_at" @click="stop(medication)">Odstaw</SecondaryButton>
                    </div>
                    <p v-if="!medications.length" class="p-4 text-sm text-gray-600">Brak wpisów.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 9: Write the feature test**

Create `tests/Feature/MedicationTest.php`:

```php
<?php

use App\Models\Medication;
use App\Models\User;

test('a user can add a medication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/medications', [
        'name' => 'Omega-3',
        'dose' => '1000 mg',
        'started_at' => '2026-01-01',
    ])->assertRedirect();

    $this->assertDatabaseHas('medications', ['user_id' => $user->id, 'name' => 'Omega-3']);
});

test('a user can mark their own medication as stopped', function () {
    $user = User::factory()->create();
    $medication = Medication::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch("/medications/{$medication->id}", ['stopped_at' => '2026-06-01'])
        ->assertRedirect();

    expect($medication->fresh()->stopped_at->toDateString())->toBe('2026-06-01');
});

test('a user cannot update another users medication', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $medicationB = Medication::factory()->for($userB)->create();

    $this->actingAs($userA)
        ->patch("/medications/{$medicationB->id}", ['stopped_at' => '2026-06-01'])
        ->assertStatus(404);
});
```

- [ ] **Step 10: Migrate, run tests, build, commit**

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test --filter=MedicationTest
./vendor/bin/sail npm run build
git add database/migrations database/factories/MedicationFactory.php app/Models/Medication.php app/Models/User.php app/Http/Requests/StoreMedicationRequest.php app/Http/Requests/UpdateMedicationRequest.php app/Http/Controllers/MedicationController.php routes/web.php resources/js/Pages/Health/Medications.vue tests/Feature/MedicationTest.php
git commit -m "feat: medications and supplements tracking"
```

---

## Task 11: `Reminder` model + `ReminderStatus` service (TDD)

**Files:**
- Create: `database/migrations/xxxx_create_reminders_table.php`
- Create: `app/Models/Reminder.php`
- Create: `database/factories/ReminderFactory.php`
- Create: `app/Services/ReminderStatus.php`
- Test: `tests/Unit/ReminderStatusTest.php`

**Interfaces:**
- Consumes: `BelongsToUser` (M0).
- Produces: `Reminder` model (`user_id`, `type`, `interval_days`, `last_completed_at` nullable). `ReminderStatus::daysUntilDue(Reminder $reminder, ?Carbon $asOf = null): ?int` — negative means overdue, `null` means never completed (due now). Task 12 (settings UI) and Task 14 (Dashboard) call this by this exact name.

- [ ] **Step 1: Create the migration**

```bash
./vendor/bin/sail artisan make:migration create_reminders_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('interval_days');
            $table->date('last_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
```

- [ ] **Step 2: Create the model**

Create `app/Models/Reminder.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'interval_days',
        'last_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_completed_at' => 'date',
        ];
    }
}
```

- [ ] **Step 3: Create the factory**

Create `database/factories/ReminderFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReminderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'Lipidogram',
            'interval_days' => 90,
            'last_completed_at' => null,
        ];
    }
}
```

- [ ] **Step 4: Add the relation to `User`**

```php
public function reminders(): HasMany
{
    return $this->hasMany(Reminder::class);
}
```

- [ ] **Step 5: Write the failing test for `ReminderStatus`**

Create `tests/Unit/ReminderStatusTest.php`:

```php
<?php

use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderStatus;
use Illuminate\Support\Carbon;

test('days until due counts down from last completion plus the interval', function () {
    $reminder = Reminder::factory()->make([
        'interval_days' => 90,
        'last_completed_at' => '2026-06-01',
    ]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBe(29);
});

test('days until due is negative when overdue', function () {
    $reminder = Reminder::factory()->make([
        'interval_days' => 90,
        'last_completed_at' => '2026-01-01',
    ]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBe(-122);
});

test('days until due is null when the reminder has never been completed', function () {
    $reminder = Reminder::factory()->make(['last_completed_at' => null]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBeNull();
});
```

- [ ] **Step 6: Run the test to confirm it fails**

```bash
./vendor/bin/sail artisan test --filter=ReminderStatusTest
```

Expected: FAIL — `App\Services\ReminderStatus` not found.

- [ ] **Step 7: Implement `ReminderStatus`**

Create `app/Services/ReminderStatus.php`:

```php
<?php

namespace App\Services;

use App\Models\Reminder;
use Illuminate\Support\Carbon;

class ReminderStatus
{
    public static function daysUntilDue(Reminder $reminder, ?Carbon $asOf = null): ?int
    {
        if ($reminder->last_completed_at === null) {
            return null;
        }

        $asOf ??= Carbon::today();
        $dueDate = $reminder->last_completed_at->copy()->addDays($reminder->interval_days);

        return (int) $asOf->diffInDays($dueDate, false);
    }
}
```

- [ ] **Step 8: Run the test to confirm it passes**

```bash
./vendor/bin/sail artisan test --filter=ReminderStatusTest
```

Expected: PASS (3 tests).

- [ ] **Step 9: Migrate and commit**

```bash
./vendor/bin/sail artisan migrate
git add database/migrations database/factories/ReminderFactory.php app/Models/Reminder.php app/Models/User.php app/Services/ReminderStatus.php tests/Unit/ReminderStatusTest.php
git commit -m "feat: add Reminder model and ReminderStatus service"
```

---

## Task 12: Reminder settings page

**Files:**
- Create: `app/Http/Requests/StoreReminderRequest.php`
- Create: `app/Http/Controllers/ReminderController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Health/Reminders.vue`
- Test: `tests/Feature/ReminderTest.php`

**Interfaces:**
- Consumes: `Reminder` model, `ReminderStatus` (Task 11).
- Produces: `GET /przypomnienia`, `POST /reminders`, `PATCH /reminders/{reminder}` (mark done → sets `last_completed_at` to today). Task 14 (Dashboard) reads the same `ReminderStatus::daysUntilDue` for its countdown.

- [ ] **Step 1: Write the Form Request**

Create `app/Http/Requests/StoreReminderRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
```

- [ ] **Step 2: Write the controller**

Create `app/Http/Controllers/ReminderController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReminderRequest;
use App\Models\Reminder;
use App\Services\ReminderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    public function index(Request $request): Response
    {
        $reminders = $request->user()->reminders()->get()->map(fn (Reminder $reminder) => [
            'id' => $reminder->id,
            'type' => $reminder->type,
            'interval_days' => $reminder->interval_days,
            'last_completed_at' => $reminder->last_completed_at?->toDateString(),
            'days_until_due' => ReminderStatus::daysUntilDue($reminder),
        ]);

        return Inertia::render('Health/Reminders', ['reminders' => $reminders]);
    }

    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $request->user()->reminders()->create($request->validated());

        return back()->with('status', 'reminder-added');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->user_id === $request->user()->id, 404);

        $reminder->update(['last_completed_at' => now()->toDateString()]);

        return back()->with('status', 'reminder-marked-done');
    }
}
```

- [ ] **Step 3: Add routes**

In `routes/web.php`, inside the `auth` middleware group:

```php
use App\Http\Controllers\ReminderController;

Route::get('/przypomnienia', [ReminderController::class, 'index'])->name('reminders.index');
Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
Route::patch('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
```

- [ ] **Step 4: Build the Vue page**

Create `resources/js/Pages/Health/Reminders.vue`:

```vue
<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    reminders: { type: Array, default: () => [] },
});

const form = useForm({ type: '', interval_days: 90 });

const submit = () => {
    form.post(route('reminders.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const markDone = (reminder) => {
    router.patch(route('reminders.update', reminder.id), {}, { preserveScroll: true });
};

const statusLabel = (days) => {
    if (days === null) return 'nigdy nie wykonane';
    if (days < 0) return `zaległe o ${Math.abs(days)} dni`;
    if (days === 0) return 'termin dziś';
    return `za ${days} dni`;
};
</script>

<template>
    <Head title="Przypomnienia" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypomnienia o badaniach</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nowe przypomnienie</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <InputLabel for="type" value="Rodzaj badania" />
                            <TextInput id="type" class="mt-1 block w-full" v-model="form.type" placeholder="np. Lipidogram" />
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>
                        <div>
                            <InputLabel for="interval_days" value="Co ile dni" />
                            <TextInput id="interval_days" type="number" class="mt-1 block w-full" v-model="form.interval_days" />
                            <InputError class="mt-2" :message="form.errors.interval_days" />
                        </div>
                        <div>
                            <PrimaryButton :disabled="form.processing">Dodaj</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y">
                    <div v-for="reminder in reminders" :key="reminder.id" class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ reminder.type }} — co {{ reminder.interval_days }} dni</p>
                            <p class="text-sm" :class="reminder.days_until_due !== null && reminder.days_until_due < 0 ? 'text-red-600' : 'text-gray-500'">
                                {{ statusLabel(reminder.days_until_due) }}
                            </p>
                        </div>
                        <SecondaryButton @click="markDone(reminder)">Wykonane dziś</SecondaryButton>
                    </div>
                    <p v-if="!reminders.length" class="p-4 text-sm text-gray-600">Brak przypomnień.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Write the feature test**

Create `tests/Feature/ReminderTest.php`:

```php
<?php

use App\Models\Reminder;
use App\Models\User;

test('a user can create a reminder', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/reminders', [
        'type' => 'Lipidogram',
        'interval_days' => 90,
    ])->assertRedirect();

    $this->assertDatabaseHas('reminders', ['user_id' => $user->id, 'type' => 'Lipidogram']);
});

test('marking a reminder done sets last_completed_at to today', function () {
    $user = User::factory()->create();
    $reminder = Reminder::factory()->for($user)->create(['last_completed_at' => null]);

    $this->actingAs($user)->patch("/reminders/{$reminder->id}")->assertRedirect();

    expect($reminder->fresh()->last_completed_at->toDateString())->toBe(now()->toDateString());
});

test('a user cannot mark another users reminder as done', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $reminderB = Reminder::factory()->for($userB)->create();

    $this->actingAs($userA)->patch("/reminders/{$reminderB->id}")->assertStatus(404);
});
```

- [ ] **Step 6: Run tests, build, commit**

```bash
./vendor/bin/sail artisan test --filter=ReminderTest
./vendor/bin/sail npm run build
git add app/Http/Requests/StoreReminderRequest.php app/Http/Controllers/ReminderController.php routes/web.php resources/js/Pages/Health/Reminders.vue tests/Feature/ReminderTest.php
git commit -m "feat: lab test reminder settings with due-date countdown"
```

---

## Task 13: Nav links for the new pages

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue`

**Interfaces:**
- Consumes: named routes from Tasks 3/5/8/10/12.
- Produces: nothing (leaf UI change).

- [ ] **Step 1: Add nav links**

In `resources/js/Layouts/AuthenticatedLayout.vue`, in the desktop `<NavLink>` group (next to the existing "Panel" link), add:

```vue
<NavLink :href="route('body.index')" :active="route().current('body.index')">Ciało</NavLink>
<NavLink :href="route('blood-pressure.index')" :active="route().current('blood-pressure.index')">Ciśnienie</NavLink>
<NavLink :href="route('lab-results.index')" :active="route().current('lab-results.*')">Badania</NavLink>
<NavLink :href="route('medications.index')" :active="route().current('medications.index')">Leki</NavLink>
<NavLink :href="route('reminders.index')" :active="route().current('reminders.index')">Przypomnienia</NavLink>
```

Add the exact same five links (using `ResponsiveNavLink` instead of `NavLink`) in the mobile/responsive nav section further down the same file — Breeze duplicates nav items in two places, same as the M0 Task 7 translation did.

- [ ] **Step 2: Build and verify**

```bash
./vendor/bin/sail npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat: add nav links for M1 health pages"
```

---

## Task 14: Dashboard v1 — weight + health summary

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Pages/Dashboard.vue`
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: `WeightTrend` (Task 2), `BodyMeasurement` (Task 2/3), `BloodPressureReading` (Task 5), `Reminder`/`ReminderStatus` (Task 11/12).
- Produces: the real `GET /dashboard` route (replacing M0's placeholder closure).

- [ ] **Step 1: Write the controller**

Create `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\BloodPressureReading;
use App\Services\ReminderStatus;
use App\Services\WeightTrend;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $latestBloodPressure = BloodPressureReading::forUser($user)
            ->latest('measured_at')
            ->first();

        $nextReminder = $user->reminders()
            ->get()
            ->map(fn ($reminder) => [
                'type' => $reminder->type,
                'days_until_due' => ReminderStatus::daysUntilDue($reminder),
            ])
            ->filter(fn ($r) => $r['days_until_due'] !== null)
            ->sortBy('days_until_due')
            ->first();

        $currentWeight = WeightTrend::sevenDayAverage($user);
        $weightGoal = $user->profile?->weight_goal_kg;

        return Inertia::render('Dashboard', [
            'weight' => [
                'sevenDayAverage' => $currentWeight,
                'weeklyTrend' => WeightTrend::weeklyTrend($user),
                'distanceToGoal' => ($currentWeight !== null && $weightGoal !== null)
                    ? round($currentWeight - (float) $weightGoal, 1)
                    : null,
            ],
            'health' => [
                'lastBloodPressure' => $latestBloodPressure
                    ? "{$latestBloodPressure->systolic}/{$latestBloodPressure->diastolic}"
                    : null,
                'nextReminder' => $nextReminder,
            ],
        ]);
    }
}
```

- [ ] **Step 2: Replace the placeholder route**

In `routes/web.php`, replace:

```php
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

with:

```php
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
```

- [ ] **Step 3: Update the Dashboard page**

Replace the contents of `resources/js/Pages/Dashboard.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    weight: { type: Object, required: true },
    health: { type: Object, required: true },
});

const weightForm = useForm({
    date: new Date().toISOString().slice(0, 10),
    weight_kg: '',
});

const submitWeight = () => {
    weightForm.post(route('body-measurements.store'), {
        preserveScroll: true,
        onSuccess: () => weightForm.reset('weight_kg'),
    });
};

const trendLabel = (trend) => {
    if (trend === null) return 'brak danych z zeszłego tygodnia';
    if (trend === 0) return 'bez zmian';
    return trend < 0 ? `↓ ${Math.abs(trend)} kg` : `↑ ${trend} kg`;
};
</script>

<template>
    <Head title="Panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Waga</h3>
                    <p v-if="weight.sevenDayAverage !== null" class="text-2xl font-semibold text-gray-900">
                        {{ weight.sevenDayAverage }} kg <span class="text-base font-normal text-gray-500">(śr. 7 dni)</span>
                    </p>
                    <p v-else class="text-sm text-gray-600">Brak jeszcze żadnych wpisów wagi.</p>
                    <p class="text-sm text-gray-600 mt-1">Trend tygodniowy: {{ trendLabel(weight.weeklyTrend) }}</p>
                    <p v-if="weight.distanceToGoal !== null" class="text-sm text-gray-600">
                        Dystans do celu: {{ weight.distanceToGoal > 0 ? '+' : '' }}{{ weight.distanceToGoal }} kg
                    </p>

                    <form @submit.prevent="submitWeight" class="mt-4 flex items-end gap-3">
                        <div>
                            <label for="quick_weight" class="sr-only">Waga dzisiaj (kg)</label>
                            <TextInput id="quick_weight" type="number" step="0.1" placeholder="Waga dzisiaj (kg)" v-model="weightForm.weight_kg" autofocus />
                        </div>
                        <PrimaryButton :disabled="weightForm.processing">Zapisz</PrimaryButton>
                        <span v-if="weightForm.recentlySuccessful" class="text-sm text-gray-600">Zapisano.</span>
                    </form>
                    <Link :href="route('body.index')" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Zobacz historię i wykresy →</Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Zdrowie</h3>
                    <p class="text-sm text-gray-600">
                        Ostatnie ciśnienie:
                        <span class="font-medium text-gray-900">{{ health.lastBloodPressure ?? 'brak wpisów' }}</span>
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        <template v-if="health.nextReminder">
                            Następne badanie ({{ health.nextReminder.type }}):
                            <span class="font-medium" :class="health.nextReminder.days_until_due < 0 ? 'text-red-600' : 'text-gray-900'">
                                {{ health.nextReminder.days_until_due < 0 ? 'zaległe' : `za ${health.nextReminder.days_until_due} dni` }}
                            </span>
                        </template>
                        <template v-else>Brak skonfigurowanych przypomnień o badaniach.</template>
                    </p>
                    <Link :href="route('reminders.index')" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Zarządzaj przypomnieniami →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Write the feature test**

Create `tests/Feature/DashboardTest.php`:

```php
<?php

use App\Models\BodyMeasurement;
use App\Models\Reminder;
use App\Models\User;

test('dashboard shows the weight seven day average and trend', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => now()->toDateString(), 'weight_kg' => 89.0]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('weight.sevenDayAverage', 89.0)
    );
});

test('dashboard shows the next lab reminder due date', function () {
    $user = User::factory()->create();
    Reminder::factory()->for($user)->create([
        'type' => 'Lipidogram',
        'interval_days' => 90,
        'last_completed_at' => now()->subDays(80)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('health.nextReminder.type', 'Lipidogram')
        ->where('health.nextReminder.days_until_due', 10)
    );
});

test('dashboard requires authentication', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
```

- [ ] **Step 5: Run tests, build, commit**

```bash
./vendor/bin/sail artisan test --filter=DashboardTest
./vendor/bin/sail npm run build
git add app/Http/Controllers/DashboardController.php routes/web.php resources/js/Pages/Dashboard.vue tests/Feature/DashboardTest.php
git commit -m "feat: Dashboard v1 with weight trend and health summary"
```

---

## Task 15: M1 acceptance check

**Files:** None created — verification pass, same shape as M0's Task 9.

- [ ] **Step 1: Run the full automated test suite**

```bash
./vendor/bin/sail artisan test
```

Expected: 100% pass, including every test added across Tasks 2–14 plus every M0 test still green.

- [ ] **Step 2: Verify the <5s weight-entry criterion**

Log in, land on `/dashboard`, confirm the weight quick-entry field is immediately visible and focused (no navigation required) — this is the literal spec §8 M1 criterion ("użytkownik wpisuje wagę w <5 s").

- [ ] **Step 3: Verify full backdated lab history works**

Via `/badania/nowe`, save at least 3 lab results with dates spanning more than a year apart (e.g. today, 6 months ago, 18 months ago), confirm `/badania` shows one chart per marker with all points in correct chronological order and norm lines rendered for predefined markers.

- [ ] **Step 4: Verify per-user isolation end-to-end**

Log in as user 1, add a weight entry, a BP reading, a lab result, a medication, and a reminder. Log out, log in as user 2, confirm none of user 1's data appears anywhere (dashboard, `/cialo`, `/cisnienie`, `/badania`, `/leki`, `/przypomnienia`) — the shared `lab_markers` dictionary (including any custom marker user 1 added) SHOULD appear for user 2 (that's correct — it's shared by design), but user 1's `lab_values`/`lab_results` must not.

- [ ] **Step 5: Record sign-off**

Append to this plan file, at the very end:

```markdown
## M1 Sign-off

- [ ] Full test suite green (date: ____)
- [ ] Weight entry confirmed <5s from dashboard load
- [ ] Full lab history (3+ backdated results) entered and charted with norms
- [ ] Per-user isolation confirmed across all M1 modules; shared lab_markers dictionary confirmed visible to both
```

Check off each line as confirmed.

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "chore: M1 acceptance verification — ciało i zdrowie complete"
```

---

## Self-Review Notes

- **Spec coverage:** Chart.js wiring (Task 1), body measurements + trend service (Tasks 2–4), blood pressure (Task 5), lab markers dictionary + predefined seed (Task 6), lab results/values (Task 7–9), medications (Task 10), reminders + countdown (Tasks 11–12), nav (Task 13), Dashboard v1 (Task 14), M1 acceptance criteria (Task 15) — all covered. AI commentary on labs (spec §4.6) and the PDF doctor report (spec §4.6) are explicitly M4/M6, not M1 — correctly excluded.
- **Placeholder scan:** no TBD/TODO markers; every step has literal code or literal commands.
- **Type/name consistency:** `BodyMeasurement` fields (`date`, `weight_kg`, `waist_cm`) match across migration/model/factory/controller/Vue (Tasks 2–4). `WeightTrend::sevenDayAverage`/`weeklyTrend` signatures identical between definition (Task 2) and consumption (Task 14). `LabMarker` fields (`name`, `unit`, `norm_min`, `norm_max`, `is_predefined`) identical across migration/model/seeder/Form Request/Vue (Tasks 6, 8, 9). `Reminder`/`ReminderStatus::daysUntilDue` identical between definition (Task 11) and consumption (Tasks 12, 14). `lab_values` deliberately has no `user_id`/`BelongsToUser`, consistent between Task 7's model and Global Constraints.

## M1 Sign-off

- [x] Full test suite green (date: 2026-08-25) — `./vendor/bin/sail artisan test`: 63 passed (169 assertions), re-confirmed after the walkthrough and a `migrate:fresh --seed` reset.
- [x] Weight entry confirmed <5s from dashboard load — no browser available in this environment, so verified structurally instead of timed: `resources/js/Pages/Dashboard.vue` renders the weight quick-entry `<TextInput>` unconditionally in the initial template (not behind a click/expand/second page load) with `autofocus`, and its form (`submitWeight`) posts directly to `route('body-measurements.store')`. One field, one submit, no navigation — satisfies the spec §8 "<5s" criterion by construction.
- [x] Full lab history (3+ backdated results) entered and charted with norms — via `curl` with a session cookie jar (login as `user1@centrum.local`), POSTed 3 `/lab-results` spanning >18 months (2025-02-25, 2026-02-25, 2026-08-25), each carrying values for a predefined marker (`Cholesterol całkowity`, norm_max 200) and a custom marker created inline via `/lab-markers` (`Ferrytyna User1`, norm_min 20 / norm_max 250). `GET /badania` returned all 3 results in correct ascending chronological order, each with both markers' norm data intact.
- [x] Per-user isolation confirmed across all M1 modules; shared lab_markers dictionary confirmed visible to both — as user1, added one entry to body measurements, blood pressure, lab results (incl. the custom marker above), medications, and reminders. Logged out, logged in as `user2@centrum.local`: `GET /dashboard`, `/cialo`, `/cisnienie`, `/leki`, `/przypomnienia` all returned empty per-user collections, and `GET /badania` returned `results: []` for user2 while its `markers` list included both custom markers user1 created (`Ferrytyna User1`, `Ferrytyna User1 v2`) — confirming the shared dictionary is visible across users while scoped personal data (`lab_results`/`lab_values`, body measurements, BP readings, medications, reminders) is not.

Walkthrough data was created against a real seeded DB and then wiped via `./vendor/bin/sail artisan migrate:fresh --seed` to leave a clean state; the full test suite was re-run afterward and stayed green (63/63).
