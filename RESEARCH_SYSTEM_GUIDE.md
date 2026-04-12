# Research Management Web Application - Implementation Guide

## ✅ Phase 1: Foundation (COMPLETED)

### Database Schema
- ✅ 8 core tables created with proper relationships
- ✅ Tracking system with UUID tracking IDs  
- ✅ User roles (student, faculty, staff, admin)
- ✅ File management with local/S3 storage support
- ✅ Citation and publication tracking
- ✅ Soft deletes for data integrity

### Eloquent Models
- ✅ ResearchPaper (with status tracking and progress calculation)
- ✅ Category (research domains)
- ✅ User (with profile, papers, and tracking relationships)
- ✅ UserProfile (detailed user information)
- ✅ PaperAuthor (pivot for multi-author support)
- ✅ Citation, Publication, PaperFile, TrackingRecord

### Test Factories
- ✅ CategoryFactory - for test categories
- ✅ ResearchPaperFactory - for test research papers
- ✅ PaperFileFactory - for test files
- ✅ PublicationFactory - for test publications

## 🔄 Phase 2: API Controllers & Routes

### Next: Create REST API

```bash
# Create resource controllers
php artisan make:controller ResearchPaperController --resource --model=ResearchPaper
php artisan make:controller CategoryController --resource --model=Category
php artisan make:controller TrackingController --model=TrackingRecord
php artisan make:controller PublicationController --resource --model=Publication
```

### Define Routes (routes/api.php or routes/web.php)
```php
Route::middleware('auth')->group(function () {
    Route::apiResource('papers', ResearchPaperController);
    Route::apiResource('categories', CategoryController);
    Route::get('papers/{paper:tracking_id}', [ResearchPaperController::class, 'showByTracking']);
    Route::post('papers/{paper}/track', [TrackingController::class, 'track']);
    Route::post('papers/{paper}/files', [ResearchPaperController::class, 'uploadFile']);
});

Route::get('papers/public/{paper:tracking_id}', [ResearchPaperController::class, 'publicTrack']);
```

## 🎨 Phase 3: Frontend Components (Neumorphism UI)

### Vue Components to Create
- `PaperList.vue` - Display research papers with neumorphism cards
- `PaperForm.vue` - Submit/edit papers
- `TrackingWidget.vue` - Real-time tracking status
- `Dashboard.vue` - User dashboard with analytics
- `QRScanner.vue` - QR code tracking
- `LandingPage.vue` - Public-facing hero + showcase

### Neumorphism Design System
```vue
<!-- Example neumorphic card -->
<div class="neumorph-card">
  <h3>{{ paper.title }}</h3>
  <p class="status-badge" :class="paper.status">{{ paper.status }}</p>
  <a :href="paper.tracking_url">Track</a>
</div>

<style scoped>
.neumorph-card {
  background: #e9ecef;
  border-radius: 20px;
  box-shadow: 
    9px 9px 16px rgba(0, 0, 0, 0.1),
    -8px -8px 16px rgba(255, 255, 255, 0.7);
  padding: 2rem;
  transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
}

.neumorph-card:hover {
  box-shadow: 
    4px 4px 10px rgba(0, 0, 0, 0.1),
    -4px -4px 10px rgba(255, 255, 255, 0.7);
}

/* Dark mode variant */
.dark .neumorph-card {
  background: #2d3748;
  box-shadow: 
    9px 9px 16px rgba(0, 0, 0, 0.3),
    -8px -8px 16px rgba(255, 255, 255, 0.1);
}
</style>
```

## 🔐 Phase 4: Authentication with OAuth

### Install Socialite
```bash
composer require laravel/socialite
```

### Configure OAuth Providers
**config/services.php:**
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],
```

### Create OAuth Controller
```php
php artisan make:controller OAuthController
```

**OAuthController.php:**
```php
public function redirectToProvider($provider)
{
    return Socialite::driver($provider)->redirect();
}

public function handleProviderCallback($provider)
{
    $user = Socialite::driver($provider)->user();
    
    $authUser = User::updateOrCreate(
        ['email' => $user->email],
        [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => now(),
            'password' => bcrypt(str()->random(32)),
        ]
    );
    
    auth()->login($authUser);
    return redirect('/dashboard');
}
```

## 📁 Phase 5: File Storage (Local + S3)

### Create StorageService
**app/Services/StorageService.php:**
```php
class StorageService {
    public function store($file, $disk = 'local') {
        $path = $file->store('papers', $disk);
        
        return PaperFile::create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
        ]);
    }
    
    public function delete($file) {
        Storage::disk($file->disk)->delete($file->file_path);
        $file->delete();
    }
}
```

## 📱 Phase 6: QR Code Tracking

### Install QR Code Generator
```bash
composer require endroid/qr-code
```

### Create QR Route
```php
Route::get('papers/{paper}/qr', [ResearchPaperController::class, 'generateQR']);
```

**In ResearchPaperController:**
```php
use Endroid\QrCode\QrCode;

public function generateQR(ResearchPaper $paper) {
    $url = route('papers.publicTrack', $paper->tracking_id);
    $qrCode = new QrCode($url);
    
    return response()->contentType($qrCode->getContentType())
        ->body($qrCode->writeString());
}
```

## 🔍 Phase 7: Search & Filtering

### Add Search Scopes
```php
// ResearchPaper.php
public function scopeSearch($query, $term) {
    return $query->where('title', 'like', "%{$term}%")
        ->orWhere('description', 'like', "%{$term}%")
        ->orWhere('keywords', 'like', "%{$term}%");
}

public function scopeFilter($query, $filters) {
    return $query
        ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
        ->when($filters['category'] ?? null, fn($q) => $q->where('category_id', $filters['category']))
        ->when($filters['year'] ?? null, fn($q) => $q->whereYear('submission_date', $filters['year']));
}
```

## 📊 Phase 8: Dashboard & Analytics

### Create DashboardController
```php
php artisan make:controller DashboardController
```

**DashboardController.php:**
```php
public function __invoke(Request $request) {
    $user = auth()->user();
    
    return inertia('Dashboard', [
        'stats' => [
            'total_papers' => $user->researchPapers()->count(),
            'published' => $user->researchPapers()->published()->count(),
            'pending' => $user->researchPapers()->where('status', 'submitted')->count(),
        ],
        'papers' => $user->researchPapers()->latest()->limit(5)->get(),
        'tracking_data' => $this->getTrackingMetrics($user),
    ]);
}

private function getTrackingMetrics($user) {
    return $user->researchPapers()
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get()
        ->keyBy('status');
}
```

## 📧 Phase 9: Email Notifications

### Create Notifications
```bash
php artisan make:notification PaperSubmitted
php artisan make:notification PaperApproved
php artisan make:notification PaperAssignedAsAuthor
```

### Dispatch Notifications
```php
// In ResearchPaperController
Notification::send($paper->user, new PaperSubmitted($paper));

// Notify all authors
foreach ($paper->authors as $author) {
    Notification::send($author, new PaperAssignedAsAuthor($paper));
}
```

## 🚀 Implementation Checklist

- [ ] Create API controllers for all resources
- [ ] Define routes (Inertia + API)
- [ ] Build Vue components with neumorphism styling
- [ ] Implement OAuth (Google, Facebook)
- [ ] Set up file upload (local + S3)
- [ ] Generate QR codes for tracking
- [ ] Add search/filter functionality
- [ ] Build dashboard with charts
- [ ] Set up email notifications
- [ ] Create seeders for test data
- [ ] Write tests (Feature & Unit)
- [ ] Deploy on Laravel Cloud

## 🎯 Key Features Summary

✅ **Research Tracking** - Real-time status updates  
✅ **Multi-Author Support** - Co-authored papers  
✅ **File Management** - PDF, presentations, data  
✅ **QR Code Tracking** - Easy paper identification  
✅ **Role-Based Access** - Student, Faculty, Staff, Admin  
✅ **OAuth Integration** - Google & Facebook login  
✅ **Neumorphism UI** - Modern, minimal design  
✅ **Light/Dark Mode** - Theme toggling  
✅ **Search & Filter** - Advanced discovery  
✅ **Analytics Dashboard** - Usage metrics  

## 📝 Environment Variables Needed

```env
# OAuth
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

FACEBOOK_CLIENT_ID=your_client_id
FACEBOOK_CLIENT_SECRET=your_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/auth/facebook/callback

# File Storage
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
```

## 🔗 Related Technologies

- **Laravel 13** - Backend framework
- **Inertia.js v3** - SPA adapter
- **Vue 3** - Frontend framework
- **TailwindCSS v4** - Utility-first CSS
- **PostgreSQL** - Database (via Supabase locally)
- **Laravel Fortify** - Authentication
- **Laravel Socialite** - OAuth integration
- **Laravel Mcp** - AI integration ready

