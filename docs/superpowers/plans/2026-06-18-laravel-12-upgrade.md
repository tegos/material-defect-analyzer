# Laravel 12 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade from Laravel 5.4 / PHP 5.6 to Laravel 12 / PHP 8.4, fixing all removed APIs and bringing the app to a runnable state on the current host (PHP 8.4.21).

**Architecture:** Keep the traditional L10-style app structure (Kernel.php, RouteServiceProvider) rather than migrating to the new L11 bootstrapped structure - this minimises churn on a small app with no real tests. Fix each broken API surface in order: deps first, then config, then routing, then controllers, then framework internals.

**Tech Stack:** PHP 8.4, Laravel 12, intervention/image 3.x (GD driver), PHPUnit 11, fakerphp/faker, Mockery 1.x

## Global Constraints

- PHP target: `^8.2` (floor), host is 8.4.21 - use `^8.2` constraint so the package resolver accepts it
- Laravel target: `^12.0`
- intervention/image target: `^3.0` - v3 API is NOT backward compatible with v2
- No new features, no refactors beyond what's needed to boot the app on L12
- No Docker, no CI - all commands run on host PHP 8.4.21
- No real business-logic tests exist; the ExampleTest smoke test (GET /) must pass after all tasks
- Never commit directly to main; work on branch `feature/laravel-12-upgrade`

---

### Task 1: Create branch and update composer.json

**Files:**
- Modify: `composer.json`

**What changes and why:**
- `php`: `>=5.6` → `^8.2` (L12 minimum)
- `laravel/framework`: `5.4.*` → `^12.0`
- `laravel/tinker`: `~1.0` → `^2.0`
- `intervention/image`: `^2.3` → `^3.0`
- Remove `fzaninotto/faker` (abandoned package, deleted from Packagist)
- Add `fakerphp/faker: ^1.23` (the maintained fork)
- `mockery/mockery`: `0.9.*` → `^1.6`
- `phpunit/phpunit`: `~5.7` → `^11.5`
- Remove old `post-install-cmd` and `post-update-cmd` scripts - they call `Illuminate\Foundation\ComposerScripts::postInstall/postUpdate` which were removed in L6, and `php artisan optimize` which no longer exists as a standalone command
- Change `autoload.classmap` from `["database"]` to explicit subdirs `["database/seeds", "database/factories"]` since L12 expects proper namespaces for seeds/factories

- [ ] **Step 1: Create the feature branch**

```bash
git checkout -b feature/laravel-12-upgrade
```

Expected: switched to branch `feature/laravel-12-upgrade`

- [ ] **Step 2: Write the new composer.json**

Replace entire `composer.json` with:

```json
{
  "name": "tegos/web-defect",
  "description": "",
  "license": "MIT",
  "type": "project",
  "require": {
    "php": "^8.2",
    "intervention/image": "^3.0",
    "laravel/framework": "^12.0",
    "laravel/tinker": "^2.0",
    "ext-gd": "*",
    "ext-json": "*"
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "mockery/mockery": "^1.6",
    "phpunit/phpunit": "^11.5"
  },
  "autoload": {
    "classmap": [
      "database/seeds",
      "database/factories"
    ],
    "psr-4": {
      "App\\": "app/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "post-root-package-install": [
      "php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ],
    "post-create-project-cmd": [
      "php artisan key:generate"
    ]
  },
  "config": {
    "preferred-install": "dist",
    "sort-packages": true,
    "optimize-autoloader": true
  }
}
```

- [ ] **Step 3: Install dependencies**

```bash
cd /mnt/d/work/projects/material-defect-analyzer
composer update -W 2>&1
```

Expected: resolves L12 + intervention/image 3.x + PHPUnit 11. May warn about abandoned packages - that's fine. Must NOT error on PHP version conflict.

- [ ] **Step 4: Verify vendor exists**

```bash
ls vendor/laravel/framework/src/Illuminate/Foundation/Application.php
```

Expected: file listed (no "No such file")

- [ ] **Step 5: Run security audit**

```bash
composer audit 2>&1
```

Expected: either "No known security vulnerabilities" or only informational warnings. Any HIGH severity issues - fix constraints before continuing.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: upgrade to Laravel 12, PHP ^8.2, intervention/image ^3.0, PHPUnit ^11.5"
```

---

### Task 2: Update config/app.php for L12 and intervention/image v3

**Files:**
- Modify: `config/app.php`

**What changes and why:**

- intervention/image v3 moved its ServiceProvider and Facade to a new namespace:
  - OLD: `Intervention\Image\ImageServiceProvider::class`
  - NEW: `Intervention\Image\Laravel\ServiceProvider::class`
  - OLD alias: `Intervention\Image\Facades\Image::class`
  - NEW alias: `Intervention\Image\Laravel\Facades\Image::class`
- `'log'` and `'log_level'` keys were removed from `config/app.php` in L6; logging lives in `config/logging.php`. Remove them to avoid deprecation warnings.
- Many framework providers in the `providers` array are now auto-discovered by L12's package discovery. Leaving them explicit doesn't break anything, but the intervention/image provider MUST be the v3 class name.

- [ ] **Step 1: Update the intervention/image ServiceProvider in providers array**

In `config/app.php`, line 179, change:
```php
        Intervention\Image\ImageServiceProvider::class
```
to:
```php
        Intervention\Image\Laravel\ServiceProvider::class
```

- [ ] **Step 2: Update the Image facade alias**

In `config/app.php`, near the bottom of the `aliases` array, change:
```php
        'Image' => Intervention\Image\Facades\Image::class
```
to:
```php
        'Image' => Intervention\Image\Laravel\Facades\Image::class
```

- [ ] **Step 3: Remove deprecated log keys**

In `config/app.php`, remove these two lines (they cause a deprecation warning on L12 since logging moved to config/logging.php):
```php
    'log' => env('APP_LOG', 'single'),

    'log_level' => env('APP_LOG_LEVEL', 'debug'),
```

- [ ] **Step 4: Verify config/app.php loads**

```bash
cd /mnt/d/work/projects/material-defect-analyzer
php artisan config:show app 2>&1 | head -10
```

Expected: shows app config values, no PHP fatal errors.

- [ ] **Step 5: Commit**

```bash
git add config/app.php
git commit -m "chore: update config/app.php for intervention/image v3 and L12"
```

---

### Task 3: Update config/image.php for intervention/image v3

**Files:**
- Modify: `config/image.php`

**What changes and why:**

intervention/image v3 changed the config format. The driver key now takes a driver class instead of a string name `'gd'`.

- [ ] **Step 1: Replace config/image.php contents**

```php
<?php

return [
    'driver' => \Intervention\Image\Drivers\Gd\Driver::class,
];
```

- [ ] **Step 2: Commit**

```bash
git add config/image.php
git commit -m "chore: update config/image.php for intervention/image v3 driver format"
```

---

### Task 4: Fix app/Http/Kernel.php - removed middleware class

**Files:**
- Modify: `app/Http/Kernel.php`

**What changes and why:**

`Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode` was renamed to `PreventRequestsDuringMaintenance` in Laravel 8. Using the old name causes a class-not-found fatal on boot.

- [ ] **Step 1: Replace the class name in $middleware**

In `app/Http/Kernel.php`, change:
```php
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
```
to:
```php
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
```

- [ ] **Step 2: Verify artisan can boot**

```bash
cd /mnt/d/work/projects/material-defect-analyzer
php artisan list 2>&1 | head -5
```

Expected: shows artisan command list header. If it shows a fatal about a class not found, that class needs fixing before continuing.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Kernel.php
git commit -m "chore: replace CheckForMaintenanceMode with PreventRequestsDuringMaintenance (L8+)"
```

---

### Task 5: Fix RouteServiceProvider - remove deprecated namespace() chaining

**Files:**
- Modify: `app/Providers/RouteServiceProvider.php`

**What changes and why:**

`Route::namespace()` was removed from the fluent router API in Laravel 9. The `$namespace` property on RouteServiceProvider was also removed in L9. Controllers must now be referenced by FQCN in route files - we do that in Task 6. Here we remove the property and the deprecated chaining.

- [ ] **Step 1: Remove $namespace property and namespace() calls**

Replace the entire content of `app/Providers/RouteServiceProvider.php` with:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Providers/RouteServiceProvider.php
git commit -m "chore: remove deprecated Route::namespace() from RouteServiceProvider (removed L9)"
```

---

### Task 6: Fix routes/web.php - use FQCN controller references

**Files:**
- Modify: `routes/web.php`

**What changes and why:**

String-based controller references like `'HomeController@index'` relied on the `$namespace` prefix set by RouteServiceProvider (removed above). L9+ requires either FQCN strings or the preferred array syntax `[ControllerClass::class, 'method']`. Use the array syntax - it is IDE-friendly and verifiable by static analysis.

- [ ] **Step 1: Rewrite routes/web.php with FQCN array syntax**

```php
<?php

use App\Http\Controllers\Ajax\GroupChart;
use App\Http\Controllers\Ajax\ImageIntensity;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/public', function () {
    return Redirect::to('/');
});

Route::post('upload', [ImageController::class, 'upload']);

Route::get('/image/{id}', [ImageController::class, 'index']);

Route::get('/demoGrid', [ImageController::class, 'demoGrid']);

Route::get('/ajax/intensity/{id}/{m}_{n}', [ImageIntensity::class, 'get']);

Route::post('/ajax/chart', [GroupChart::class, 'get']);
```

- [ ] **Step 2: Verify routes load**

```bash
cd /mnt/d/work/projects/material-defect-analyzer
php artisan route:list 2>&1
```

Expected: table listing all 7 routes. No "Target class does not exist" errors.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "chore: convert routes to FQCN array syntax (Route::namespace removed in L9)"
```

---

### Task 7: Fix ImageController.php - remove Input facade, update Image facade

**Files:**
- Modify: `app/Http/Controllers/ImageController.php`

**What changes and why:**

1. `Illuminate\Support\Facades\Input` was removed in L6. Use injected `Illuminate\Http\Request` instead.
2. `Intervention\Image\ImageManagerStatic` was removed in intervention/image v3. The v3 facade is `Intervention\Image\Laravel\Facades\Image` and the read method is `read()` not `make()`.

- [ ] **Step 1: Replace the use imports**

At the top of `app/Http/Controllers/ImageController.php`, replace:
```php
use Illuminate\Support\Facades\Input as Input;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\ImageManagerStatic as Image;
```
with:
```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\Laravel\Facades\Image;
```

- [ ] **Step 2: Update upload() method signature and replace Input calls**

Replace the `upload()` method:
```php
public function upload(Request $request)
{
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $file->move('uploads', $file->getClientOriginalName());
        $divide_n = (int)$request->input('divide_n', 3);
        $divide_m = (int)$request->input('divide_m', 3);
        $threshold = (int)$request->input('threshold', 255);
        $algorithm = (int)$request->input('algorithm', 1);
        $groups = (int)$request->input('groups', 3);

        $image_model = new ImageModel;

        $image_model->image = '/uploads/' . $file->getClientOriginalName();
        $image_model->divide_n = $divide_n;
        $image_model->divide_m = $divide_m;
        $image_model->threshold = $threshold;
        $image_model->algorithm = $algorithm;
        $image_model->groups = $groups;

        $image_model->save();
        return Redirect::to('image/' . $image_model->id);
    } else {
        return Redirect::to('/');
    }
}
```

- [ ] **Step 3: Replace Image::make() with Image::read() in index()**

In the `index()` method, change:
```php
        $img = Image::make($file_path);
```
to:
```php
        $img = Image::read($file_path);
```

- [ ] **Step 4: Replace Image::make() with Image::read() in demoGrid()**

In the `demoGrid()` method, change:
```php
        $img = Image::make($file_path);
```
to:
```php
        $img = Image::read($file_path);
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ImageController.php
git commit -m "chore: replace Input facade (removed L6) and update to intervention/image v3 API"
```

---

### Task 8: Fix GroupChart.php - remove Input facade

**Files:**
- Modify: `app/Http/Controllers/Ajax/GroupChart.php`

**What changes and why:**

Same as Task 7 - `Illuminate\Support\Facades\Input` removed in L6.

- [ ] **Step 1: Replace the entire file**

```php
<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GroupChart extends Controller
{
    public function get(Request $request)
    {
        $imageKeys = $request->input('imageKeys');
        $featureDataOfImages = json_decode($request->input('featureDataOfImages'), true);

        $imageKeysData = [];
        foreach ($imageKeys as $key => $imageKey) {
            $imageKeysData[] = $imageKey;
        }

        $resultFeatures = [];
        $imageKeysDataIndex = [];
        $min = [];
        $max = [];

        foreach ($imageKeysData as $imageKey) {
            $resultFeatures[] = $featureDataOfImages[$imageKey];
            $imageKeysDataIndex[] = $imageKey;
            $min[] = min($featureDataOfImages[$imageKey]);
            $max[] = max($featureDataOfImages[$imageKey]);
        }

        $seriesData = [];

        for ($i = 0; $i < count($resultFeatures); $i++) {
            $seriesData[] = [
                'data' => $resultFeatures[$i],
                'name' => "Частина {$imageKeysDataIndex[$i]}",
                'min' => min($min) - 15,
                'max' => max($max) + 15
            ];
        }

        return response()->json($seriesData);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Ajax/GroupChart.php
git commit -m "chore: replace Input facade with Request in GroupChart (Input removed in L6)"
```

---

### Task 9: Fix app/Exceptions/Handler.php - Throwable type hints

**Files:**
- Modify: `app/Exceptions/Handler.php`

**What changes and why:**

In PHP 7+, `Throwable` is the correct type for anything that can be caught (covers both `Exception` and `Error`). Laravel 8+ changed the handler signatures from `Exception` to `Throwable`. Using `Exception` in the handler causes a method signature conflict with the parent class in L8+, which throws a fatal on boot.

Also: `\Illuminate\Session\TokenMismatchException` was removed in L9 - remove it from `$dontReport`.

- [ ] **Step 1: Replace the entire Handler.php**

```php
<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Exceptions/Handler.php
git commit -m "chore: update Handler.php to use Throwable (L8+), remove removed TokenMismatchException"
```

---

### Task 10: Fix app/Image/AbstractImage.php - PHP 8.0 GD resource type change

**Files:**
- Modify: `app/Image/AbstractImage.php`

**What changes and why:**

In PHP 8.0, GD functions no longer return `resource` type values - they return `\GdImage` objects. `get_resource_type($image) === 'gd'` always returns false in PHP 8.x (get_resource_type returns false for non-resources). This breaks `isImage()`, `setImage()`, `setImageByPath()`, and `__destruct()`, causing the image grid and characteristic features to silently fail.

Fix: use `$image instanceof \GdImage` instead.

- [ ] **Step 1: Replace the isImage() method**

In `app/Image/AbstractImage.php`, replace:
```php
    public function isImage($image)
    {
        if (mb_strtolower(get_resource_type($image)) === 'gd') {
            return true;
        }
        return false;
    }
```
with:
```php
    public function isImage($image): bool
    {
        return $image instanceof \GdImage;
    }
```

- [ ] **Step 2: Replace the setImage() resource type check**

In `setImage()`, replace:
```php
        if (mb_strtolower(get_resource_type($image)) === 'gd') {
```
with:
```php
        if ($image instanceof \GdImage) {
```

- [ ] **Step 3: Replace the setImageByPath() resource type check**

In `setImageByPath()`, replace:
```php
        if (mb_strtolower(get_resource_type($image)) === 'gd') {
```
with:
```php
        if ($image instanceof \GdImage) {
```

- [ ] **Step 4: Commit**

```bash
git add app/Image/AbstractImage.php
git commit -m "chore: fix GD resource type check for PHP 8.0+ (GdImage object, not resource)"
```

---

### Task 11: Update phpunit.xml for PHPUnit 11

**Files:**
- Modify: `phpunit.xml`

**What changes and why:**

PHPUnit 10+ removed several XML attributes:
- `backupStaticAttributes` - removed
- `convertErrorsToExceptions`, `convertNoticesToExceptions`, `convertWarningsToExceptions` - removed (PHP 8 throws these as errors natively)
- `stopOnFailure` - now CLI flag only, not XML
- `processIsolation` - still valid

PHPUnit 10+ changed coverage syntax: `<filter><whitelist>` → `<source>`.
PHPUnit 12+ errors if a testsuite directory does not exist on disk. The `tests/Unit` directory exists (has ExampleTest.php checked in), so it's safe.

The bootstrap should point to `vendor/autoload.php` - the existing `bootstrap/autoload.php` just requires vendor/autoload.php, so either works; use `vendor/autoload.php` directly.

- [ ] **Step 1: Replace phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Feature Tests">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit Tests">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_DRIVER" value="sync"/>
    </php>
</phpunit>
```

- [ ] **Step 2: Commit**

```bash
git add phpunit.xml
git commit -m "chore: update phpunit.xml for PHPUnit 11 (remove removed attributes, update coverage syntax)"
```

---

### Task 12: Update ExampleTest.php for PHPUnit 11

**Files:**
- Modify: `tests/Feature/ExampleTest.php`

**What changes and why:**

PHPUnit 10+ requires test method names to start with `test` (already satisfied) OR have `#[Test]` attribute. The method name `testBasicTest()` is fine. However, the file imports unused traits that no longer exist at their old path in L12:
- `DatabaseMigrations` still exists but at a different namespace in L12
- `DatabaseTransactions` still exists
- `WithoutMiddleware` still exists

All three are unused in this class - remove the unused imports to avoid autoload failures if the classes moved.

- [ ] **Step 1: Update tests/Feature/ExampleTest.php**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicTest(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add tests/Feature/ExampleTest.php
git commit -m "chore: clean unused trait imports from ExampleTest (L12 compat)"
```

---

### Task 13: Verify - artisan boots and tests run

**Files:** none

**What to verify:**

1. `php artisan` boots without fatal errors
2. `php artisan route:list` shows all 7 routes
3. `php artisan config:show app` shows config
4. `php artisan test` runs PHPUnit 11 - the ExampleTest may fail if there is no DB (GET / hits the HomeController which calls `ImageGrid::getAlgorithms()` - pure PHP, no DB). It should return 200 if a .env exists.
5. `composer audit` - no new vulnerabilities

- [ ] **Step 1: Ensure .env exists**

```bash
cd /mnt/d/work/projects/material-defect-analyzer
test -f .env && echo "exists" || cp .env.example .env && php artisan key:generate
```

Expected: "exists" OR key generated successfully.

- [ ] **Step 2: Artisan smoke test**

```bash
php artisan list 2>&1 | head -15
```

Expected: artisan command list, no PHP fatals.

- [ ] **Step 3: Route list**

```bash
php artisan route:list 2>&1
```

Expected: 7 routes listed. No "Target class does not exist" errors.

- [ ] **Step 4: Run tests**

```bash
php artisan test 2>&1
```

Expected for ExampleTest: PASS (GET / → HomeController::index → view('home') → 200). If it fails due to missing view compiled cache or missing APP_KEY, fix before continuing.

- [ ] **Step 5: Security audit**

```bash
composer audit 2>&1
```

Expected: no HIGH severity advisories.

- [ ] **Step 6: Commit any fixes found during verification, then tag the branch ready**

```bash
git log --oneline feature/laravel-12-upgrade ^main
```

Expected: 12+ commits visible.

---

## Self-Review Checklist

- [x] Spec coverage: all 7 code-level changes identified in recon are covered (composer.json, config/app, config/image, Kernel, RouteServiceProvider, routes, ImageController, GroupChart, Handler, AbstractImage, phpunit.xml, ExampleTest)
- [x] Placeholder scan: all steps have concrete code or commands
- [x] Type consistency: `Request $request` used consistently in Tasks 7 and 8; `\GdImage` used consistently in Task 10
- [x] No silent caps: every known breaking change from L5.4→L12 that touches THIS codebase is covered
- [x] Verification: Task 13 confirms artisan boots, routes load, and the one real test passes
