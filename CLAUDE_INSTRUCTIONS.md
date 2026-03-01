# LifeApp API - Inštrukcie pre implementáciu

## Prehľad
Toto je backend API pre LifeApp - osobnú aplikáciu na sledovanie života. Frontend je React Native (Expo) aplikácia s Clerk autentifikáciou.

## Čo potrebuješ implementovať

### 1. Clerk JWT Autentifikácia

Aplikácia používa **Clerk** pre autentifikáciu. Laravel musí overovať Clerk JWT tokeny.

**Inštalácia:**
```bash
composer require clerk/backend-sdk
```

**Vytvor middleware** `app/Http/Middleware/ClerkAuth.php`:
- Overuje Bearer token z Authorization headeru
- Používa Clerk SDK na verifikáciu tokenu
- Extrahuje user ID z tokenu (`sub` claim)
- Vytvorí alebo nájde používateľa v databáze

**Environment variables (.env):**
```
CLERK_SECRET_KEY=sk_test_...
```

Clerk Secret Key získaš z Clerk Dashboard → API Keys.

### 2. User Model Update

Uprav `app/Models/User.php`:
- Pridaj `clerk_id` stĺpec (string, unique) - toto je Clerk user ID
- Pridaj `avatar_url` stĺpec (nullable string)
- Uprav fillable atribúty

**Migrácia:**
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('clerk_id')->unique()->after('id');
    $table->string('avatar_url')->nullable()->after('email');
});
```

### 3. WeeklyAssessment Model

Vytvor model `app/Models/WeeklyAssessment.php`:

**Atribúty:**
- `id` - UUID
- `user_id` - foreign key na users
- `week_start` - date (pondelok týždňa)
- `week_end` - date (nedeľa týždňa)
- `ratings` - JSON pole s hodnoteniami
- `notes` - text (nullable)
- `created_at`, `updated_at`

**Ratings JSON štruktúra:**
```json
[
  { "aspectId": "physical_health", "value": 7 },
  { "aspectId": "mental_health", "value": 8 },
  { "aspectId": "family_friends", "value": 6 },
  { "aspectId": "romantic_life", "value": 5 },
  { "aspectId": "career", "value": 8 },
  { "aspectId": "finances", "value": 7 },
  { "aspectId": "personal_growth", "value": 9 },
  { "aspectId": "purpose", "value": 6 }
]
```

### 4. API Routes

Vytvor súbor `routes/api.php` a zaregistruj ho v `bootstrap/app.php`.

**Endpoints:**

```
# Autentifikované routes (potrebujú Clerk token)
GET    /api/user                    # Získať aktuálneho používateľa
PATCH  /api/user                    # Aktualizovať profil

# Wheel of Life
GET    /api/assessments             # Zoznam všetkých hodnotení
POST   /api/assessments             # Vytvoriť nové hodnotenie
GET    /api/assessments/{id}        # Získať jedno hodnotenie
PUT    /api/assessments/{id}        # Aktualizovať hodnotenie
DELETE /api/assessments/{id}        # Zmazať hodnotenie
GET    /api/assessments/current-week # Získať hodnotenie pre aktuálny týždeň
```

### 5. Controllers

**UserController** (`app/Http/Controllers/Api/UserController.php`):
- `show()` - vráti aktuálneho používateľa
- `update()` - aktualizuje meno/avatar

**AssessmentController** (`app/Http/Controllers/Api/AssessmentController.php`):
- `index()` - zoznam hodnotení (paginated, zoradené od najnovšieho)
- `store()` - vytvorí nové hodnotenie
- `show()` - detail hodnotenia
- `update()` - aktualizuje hodnotenie
- `destroy()` - zmaže hodnotenie
- `currentWeek()` - vráti hodnotenie pre aktuálny týždeň

### 6. Validácia

**StoreAssessmentRequest:**
```php
[
    'week_start' => 'required|date',
    'week_end' => 'required|date|after:week_start',
    'ratings' => 'required|array|size:8',
    'ratings.*.aspectId' => 'required|string|in:physical_health,mental_health,family_friends,romantic_life,career,finances,personal_growth,purpose',
    'ratings.*.value' => 'required|integer|min:0|max:10',
    'notes' => 'nullable|string|max:1000',
]
```

### 7. API Responses

Používaj konzistentný formát:

**Úspech:**
```json
{
  "data": { ... },
  "message": "Assessment created successfully"
}
```

**Chyba:**
```json
{
  "error": "Validation failed",
  "errors": {
    "ratings": ["The ratings field is required"]
  }
}
```

**Zoznam (paginated):**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### 8. CORS

Uprav `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'], // Pre development, v produkcii obmedziť
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## Štruktúra súborov na vytvorenie

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── UserController.php
│   │       └── AssessmentController.php
│   ├── Middleware/
│   │   └── ClerkAuth.php
│   └── Requests/
│       ├── StoreAssessmentRequest.php
│       └── UpdateAssessmentRequest.php
├── Models/
│   ├── User.php (upraviť)
│   └── WeeklyAssessment.php

database/
└── migrations/
    ├── xxxx_add_clerk_fields_to_users_table.php
    └── xxxx_create_weekly_assessments_table.php

routes/
└── api.php

bootstrap/
└── app.php (upraviť - pridať api routes)

config/
└── cors.php (upraviť)
```

---

## Postup implementácie

1. **Nainštaluj Clerk SDK:**
   ```bash
   composer require clerk/backend-sdk
   ```

2. **Vytvor migrácie** a spusti `php artisan migrate`

3. **Vytvor ClerkAuth middleware** a zaregistruj ho

4. **Vytvor modely** (User update, WeeklyAssessment)

5. **Vytvor controllery** s validáciou

6. **Nastav routes** v `routes/api.php`

7. **Aktualizuj bootstrap/app.php** - pridaj api routes

8. **Nastav CORS**

9. **Testuj endpointy** pomocou Postman/curl

---

## Testovanie

Pre testovanie API bez Clerk tokenu môžeš dočasne:
1. Vytvoriť test route bez middleware
2. Alebo použiť Clerk Development JWT z Dashboard

**Clerk test token:**
V Clerk Dashboard → Sessions → môžeš získať JWT token pre testovanie.

---

## Poznámky

- Použi UUID pre assessment IDs (`$table->uuid('id')->primary()`)
- Ratings ukladaj ako JSON (`$table->json('ratings')`)
- Nezabudni na `$casts` v modeli pre JSON konverziu
- Všetky dátumy vracaj v ISO formáte
