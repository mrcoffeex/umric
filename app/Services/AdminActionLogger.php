<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;

class AdminActionLogger
{
    public const ACTION_CREATE = 'created';

    public const ACTION_UPDATE = 'updated';

    public const ACTION_DELETE = 'deleted';

    public const ACTION_RESTORE = 'restored';

    public const ACTION_APPROVE = 'approved';

    public const ACTION_REJECT = 'rejected';

    public const ACTION_BLOCK = 'blocked';

    public const ACTION_UNBLOCK = 'unblocked';

    public const ACTION_ASSIGN = 'assigned';

    public const ACTION_RATE = 'rated';

    private ?User $admin = null;

    private ?Model $subject = null;

    private array $properties = [];

    private ?string $description = null;

    public function __construct(?User $admin = null)
    {
        $resolvedUser = $admin ?? Auth::user();
        $this->admin = $resolvedUser instanceof User ? $resolvedUser : null;
    }

    public static function for(?User $admin = null): self
    {
        return new self($admin);
    }

    public function on(?Model $model): self
    {
        $this->subject = $model;

        return $this;
    }

    public function action(string $action): self
    {
        $requestMetadata = $this->buildRequestMetadata();

        if ($requestMetadata !== []) {
            $existingRequestMeta = $this->properties['request'] ?? [];
            $this->properties['request'] = array_merge(
                $requestMetadata,
                is_array($existingRequestMeta) ? $existingRequestMeta : []
            );
        }

        $activity = Activity::causedBy($this->admin)
            ->performedOn($this->subject)
            ->event($action);

        if (! empty($this->properties)) {
            $activity->withProperties($this->properties);
        }

        $description = $this->description ?? $this->buildLogMessage($action);

        $activity->log($description);

        // Also log to Laravel logs for monitoring
        $this->logToFile($action);

        // Reset state
        $this->properties = [];
        $this->description = null;

        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function create(Model $model): self
    {
        $this->subject = $model;

        return $this->action(self::ACTION_CREATE);
    }

    public function update(Model $model, array $changes = []): self
    {
        $this->subject = $model;

        if (! empty($changes)) {
            $this->properties = ['changes' => $changes];
        }

        return $this->action(self::ACTION_UPDATE);
    }

    public function delete(Model $model): self
    {
        $this->subject = $model;

        return $this->action(self::ACTION_DELETE);
    }

    public function restore(Model $model): self
    {
        $this->subject = $model;

        return $this->action(self::ACTION_RESTORE);
    }

    public function approve(User $user): self
    {
        $this->subject = $user;
        $this->description = "User {$user->email} approved";

        return $this->action(self::ACTION_APPROVE);
    }

    public function reject(User $user): self
    {
        $this->subject = $user;
        $this->description = "User {$user->email} rejected";

        return $this->action(self::ACTION_REJECT);
    }

    public function block(User $user, ?string $reason = null): self
    {
        $this->subject = $user;
        $this->description = "User {$user->email} blocked";

        if ($reason) {
            $this->properties = ['reason' => $reason];
        }

        return $this->action(self::ACTION_BLOCK);
    }

    public function unblock(User $user): self
    {
        $this->subject = $user;
        $this->description = "User {$user->email} unblocked";

        return $this->action(self::ACTION_UNBLOCK);
    }

    public function assign(Model $model, array $assignments): self
    {
        $this->subject = $model;
        $this->properties = ['assignments' => $assignments];

        return $this->action(self::ACTION_ASSIGN);
    }

    private function buildLogMessage(string $action): string
    {
        $admin = $this->admin?->email ?? 'System';
        $subject = $this->subject?->getTable() ?? 'Unknown';
        $subjectId = $this->subject?->id ?? 'N/A';
        $description = $this->description ? " - {$this->description}" : '';

        return "[{$action}] {$admin} {$action} {$subject} #{$subjectId}{$description}";
    }

    private function logToFile(string $action): void
    {
        $adminEmail = $this->admin?->email ?? 'System';
        $subject = $this->subject?->getTable() ?? 'Unknown';
        $subjectId = $this->subject?->id ?? 'N/A';
        $properties = ! empty($this->properties) ? json_encode($this->properties) : '';

        Log::channel('admin_actions')->info(
            sprintf(
                'Admin action: %s | Admin: %s | Subject: %s #%s | Props: %s',
                $action,
                $adminEmail,
                $subject,
                $subjectId,
                $properties
            )
        );
    }

    private function buildRequestMetadata(): array
    {
        $request = request();

        if (! $request instanceof Request) {
            return [];
        }

        $userAgent = (string) ($request->userAgent() ?? 'Unknown');

        return [
            'ip_address' => (string) ($request->ip() ?? 'Unknown'),
            'user_agent' => $userAgent,
            'browser' => $this->detectBrowser($userAgent),
            'device' => $this->detectDevice($userAgent),
            'location' => $this->resolveLocation($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => (string) ($request->route()?->getName() ?? 'Unknown'),
        ];
    }

    private function detectBrowser(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'edg') => 'Microsoft Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox') => 'Firefox',
            str_contains($ua, 'chrome') => 'Chrome',
            str_contains($ua, 'safari') => 'Safari',
            default => 'Unknown',
        };
    }

    private function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'Tablet',
            str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'Mobile',
            default => 'Desktop',
        };
    }

    private function resolveLocation(Request $request): string
    {
        $country = (string) ($request->header('CF-IPCountry')
            ?? $request->header('X-Vercel-IP-Country')
            ?? $request->header('X-Country-Code')
            ?? 'Unknown');

        $region = (string) ($request->header('X-Vercel-IP-Country-Region') ?? '');
        $city = (string) ($request->header('X-Vercel-IP-City') ?? '');

        $segments = array_values(array_filter([$city, $region, $country], fn (string $segment) => $segment !== ''));

        return $segments !== [] ? implode(', ', $segments) : 'Unknown';
    }
}
