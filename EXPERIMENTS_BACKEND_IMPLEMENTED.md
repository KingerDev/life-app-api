# Tiny Experiments Backend - Implementation Complete ✅

## Implemented Components

### 1. Database Migrations
**Files Created:**
- `database/migrations/2026_01_23_150641_create_tiny_experiments_table.php`
- `database/migrations/2026_01_23_150723_create_experiment_check_ins_table.php`

**Tables:**
- `tiny_experiments` - Main experiments table with UUID primary key
- `experiment_check_ins` - Daily check-ins with unique constraint per experiment per day

**Schema Details:**

**tiny_experiments:**
- `id` (UUID, primary key)
- `user_id` (foreign key to users, cascade on delete)
- `domain_id` (string) - Beliefs domain
- `field_notes` (JSON) - 9 questions from Exercise #1
- `patterns` (JSON) - 3 patterns from Exercise #2
- `research_question` (JSON) - Research question from Exercise #3
- `pact` (JSON) - Action and duration from Exercise #4
- `duration_value` (integer) - e.g., 2, 14, 30
- `duration_type` (enum: days, weeks, months)
- `start_date` (date)
- `end_date` (date)
- `status` (enum: active, completed, abandoned) - default: active
- `suggestion_source` (string, nullable) - wheel_of_life or manual
- `related_aspect_id` (string, nullable) - WoL aspect
- `created_at`, `updated_at` (timestamps)
- Index: `(user_id, status)` for performance

**experiment_check_ins:**
- `id` (UUID, primary key)
- `experiment_id` (UUID, foreign key to tiny_experiments, cascade on delete)
- `date` (date)
- `completed` (boolean, default: false)
- `notes` (text, nullable)
- `created_at`, `updated_at` (timestamps)
- Unique constraint: `(experiment_id, date)` - one check-in per day

### 2. Eloquent Models
**Files Created:**
- `app/Models/TinyExperiment.php`
- `app/Models/ExperimentCheckIn.php`

**Modified:**
- `app/Models/User.php` - Added `tinyExperiments()` relationship

**Model Features:**
- UUID primary keys using `HasUuids` trait
- JSON casting for field_notes, patterns, research_question, pact
- Date casting for start_date, end_date
- Relationships:
  - `TinyExperiment` → belongs to `User`
  - `TinyExperiment` → has many `ExperimentCheckIn`
  - `ExperimentCheckIn` → belongs to `TinyExperiment`

### 3. API Controller
**File Created:**
- `app/Http/Controllers/Api/ExperimentController.php` (450+ lines)

**Implemented Endpoints (13 total):**

#### 1. GET /api/experiments
- Returns active experiments for authenticated user
- Ordered by created_at DESC

#### 2. GET /api/experiments/suggestions
- Generates suggestions based on latest Wheel of Life assessment
- Logic:
  - Finds aspects with score < 7
  - Maps WoL aspects → Beliefs domains
  - Sorts by score (ascending) - lower score = higher priority
  - Returns top 3 suggestions
- Returns empty array if no assessment exists

#### 3. GET /api/experiments/history?page=N
- Returns completed/abandoned experiments (paginated, 20 per page)
- Ordered by end_date DESC
- Includes pagination meta

#### 4. GET /api/experiments/{id}
- Returns single experiment
- Validates user ownership

#### 5. POST /api/experiments
- Creates new experiment
- **Validation:**
  - Max 3 active experiments per user (422 error if exceeded)
  - All 4 exercises required
  - Duration value (min: 1) and type (days/weeks/months)
  - Start date required
- Auto-calculates end_date based on start_date + duration
- Status defaults to 'active'

#### 6. PATCH /api/experiments/{id}
- Updates experiment fields
- Recalculates end_date if duration or start_date changes
- Validates user ownership

#### 7. DELETE /api/experiments/{id}
- Deletes experiment and all its check-ins (cascade)
- Validates user ownership

#### 8. POST /api/experiments/{id}/abandon
- Sets status to 'abandoned'
- Validates user ownership

#### 9. POST /api/experiments/{id}/complete
- Sets status to 'completed'
- Validates user ownership

#### 10. GET /api/experiments/{id}/progress
- Calculates comprehensive progress stats:
  - **Days**: total_days, days_elapsed, days_remaining
  - **Check-ins**: check_ins_count, completed_count, completion_rate (%)
  - **Streaks**: current_streak, longest_streak
  - **Today**: needs_check_in_today, completed_today
- Streak algorithm:
  - Current streak only counts if includes today or yesterday
  - Longest streak tracks all-time best
  - Resets on incomplete check-in

#### 11. POST /api/experiments/{id}/check-ins
- Creates daily check-in
- **Validation:**
  - Date, completed (boolean), notes (optional)
  - Prevents duplicate check-ins for same date (409 conflict)
- Returns created check-in

#### 12. GET /api/experiments/{id}/check-ins
- Returns all check-ins for experiment
- Ordered by date DESC

#### 13. GET /api/experiments/check-ins/today
- Returns today's check-ins across all active experiments
- Includes experiment data via relationship

### 4. Routes Registration
**Modified:** `routes/api.php`

All routes protected by `ClerkAuth` middleware:
```php
Route::get('/experiments/suggestions', ...);
Route::get('/experiments/history', ...);
Route::get('/experiments/check-ins/today', ...);
Route::post('/experiments/{id}/abandon', ...);
Route::post('/experiments/{id}/complete', ...);
Route::get('/experiments/{id}/progress', ...);
Route::post('/experiments/{id}/check-ins', ...);
Route::get('/experiments/{id}/check-ins', ...);
Route::apiResource('experiments', ...);
```

### 5. Domain Mappings
**WoL → Beliefs Domain Map:**
```php
'career' => 'career',
'physical_health' => 'health',
'mental_health' => 'health',
'family_friends' => 'relationships',
'romantic_life' => 'relationships',
'finances' => 'money',
'personal_growth' => 'learning',
'purpose' => 'impact',
```

**Domain Labels (Slovak):**
```php
'career' => 'Kariéra',
'relationships' => 'Vzťahy',
'health' => 'Zdravie',
'creativity' => 'Kreativita',
'learning' => 'Učenie',
'money' => 'Peniaze',
'confidence' => 'Sebadôvera',
'impact' => 'Dopad',
```

## Database Migration Status
✅ **Migrations run successfully**
- `tiny_experiments` table created (494ms)
- `experiment_check_ins` table created (407ms)

## Routes Verification
✅ **All 13 routes registered:**
```
GET|HEAD    api/experiments
POST        api/experiments
GET|HEAD    api/experiments/check-ins/today
GET|HEAD    api/experiments/history
GET|HEAD    api/experiments/suggestions
GET|HEAD    api/experiments/{experiment}
PUT|PATCH   api/experiments/{experiment}
DELETE      api/experiments/{experiment}
POST        api/experiments/{id}/abandon
POST        api/experiments/{id}/check-ins
GET|HEAD    api/experiments/{id}/check-ins
POST        api/experiments/{id}/complete
GET|HEAD    api/experiments/{id}/progress
```

## Key Features Implemented

### ✅ Validation & Business Logic
- 3-experiment limit enforcement
- Unique check-in per day constraint
- Auto-calculation of end dates
- User ownership validation on all operations

### ✅ Progress Tracking Algorithm
- Smart streak calculation (current vs longest)
- Completion rate percentage
- Today's check-in status tracking
- Days elapsed/remaining calculations

### ✅ Suggestions System
- Integration with Wheel of Life assessments
- Priority-based ranking (lower scores = higher priority)
- Top 3 suggestions with Slovak labels
- Graceful handling of missing assessments

### ✅ Data Relationships
- Cascade deletion (delete experiment → delete check-ins)
- Eager loading support for nested data
- Clean separation of concerns

## Testing Checklist

### Backend API Testing
- [ ] Create experiment via POST /api/experiments
- [ ] Verify 3-experiment limit (4th should fail with 422)
- [ ] Get active experiments via GET /api/experiments
- [ ] Get suggestions via GET /api/experiments/suggestions
- [ ] Add check-in via POST /api/experiments/{id}/check-ins
- [ ] Verify duplicate check-in fails (409)
- [ ] Get progress stats via GET /api/experiments/{id}/progress
- [ ] Verify streak calculations are correct
- [ ] Complete experiment via POST /api/experiments/{id}/complete
- [ ] Abandon experiment via POST /api/experiments/{id}/abandon
- [ ] Get history via GET /api/experiments/history
- [ ] Verify pagination works
- [ ] Update experiment via PATCH /api/experiments/{id}
- [ ] Delete experiment via DELETE /api/experiments/{id}

### Integration Testing
- [ ] Test with React Native frontend
- [ ] Verify snake_case → camelCase normalization
- [ ] Test all 6-step wizard flow end-to-end
- [ ] Test daily check-in flow
- [ ] Verify progress displays correctly
- [ ] Test home card integration
- [ ] Verify suggestions appear correctly

## API Response Format

All responses follow the pattern:
```json
{
  "data": { ... } // or array
}
```

Paginated responses include:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 87
  }
}
```

Error responses:
```json
{
  "error": "Error message",
  "data": { ... } // Optional (e.g., for conflicts)
}
```

## Files Summary

**Created:**
- 2 migrations
- 2 models
- 1 controller (450+ lines)

**Modified:**
- 1 model (User.php - added relationship)
- 1 route file (api.php - added 13 routes)

**Total:** 6 files, ~650 lines of backend code

## Next Steps

1. **Test API endpoints** using Postman or similar tool
2. **Connect React Native app** to backend
3. **Test full flow** from frontend to database
4. **Monitor performance** of progress calculations
5. **Add indexes** if queries are slow (already added for user_id + status)

## Notes

- All JSON fields use Laravel's built-in JSON casting
- UUIDs used for better security and distributed systems support
- Cascade deletes prevent orphaned check-ins
- Unique constraints at database level for data integrity
- Slovak labels for better UX consistency with frontend
- Progress calculations optimized for single query
- Streak algorithm handles edge cases (today, yesterday, gaps)
