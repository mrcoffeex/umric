---
name: laravel-mail-queue
description: 'Always use Mail::to()->queue() instead of send() when dispatching Laravel Mailables. Activate whenever writing or reviewing email-sending code in Laravel: new Mailable classes, notification channels, controller actions that trigger emails, or any call to Mail::to/cc/bcc/send. Covers: queue vs send decision, ensuring Mailable uses Queueable + SerializesModels, queue worker setup, ShouldQueue interface, and fixing existing send() calls.'
license: MIT
metadata:
    author: umric
---

# Laravel Mail Queue

Always dispatch emails via the queue. Never call `Mail::to()->send()` in controllers, listeners, jobs, or service classes.

## Rule

```php
// ✅ Correct
Mail::to($user)->queue(new SomeMail($user));

// ❌ Wrong — blocks the HTTP response
Mail::to($user)->send(new SomeMail($user));
```

## Why

`send()` executes the SMTP round-trip synchronously inside the HTTP request. This adds 200–2000 ms to every response, blocks the web worker, and fails the request if the mail server is temporarily unreachable.

`queue()` pushes a job to the queue and returns immediately. The queue worker sends the email in the background. The user sees a fast response regardless of mail server latency.

## Mailable Setup Checklist

Every Mailable that will be queued **must** include both traits:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class MyMail extends Mailable
{
    use Queueable, SerializesModels;
    // ...
}
```

`SerializesModels` ensures Eloquent models passed to the constructor are serialized by ID and re-fetched when the job runs, preventing stale or oversized payloads.

Optionally implement `ShouldQueue` on the Mailable itself to force queuing even if called via `send()`:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class MyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
}
```

## Queue Worker Requirement

`queue()` only works if a queue worker is running. For local development:

```bash
php artisan queue:work
# or with auto-restart on code changes:
php artisan queue:listen
```

For production, use Supervisor to keep `queue:work` alive. If no worker is configured, switch the `QUEUE_CONNECTION` in `.env` to `sync` for development — this makes `queue()` behave like `send()` without code changes:

```env
# .env (local only)
QUEUE_CONNECTION=sync

# .env (production)
QUEUE_CONNECTION=database   # or redis
```

**Do not change `queue()` calls to `send()` to "fix" missing queue workers.** Fix the worker instead, or use `sync` connection in development.

## Specifying Queue / Connection

```php
// Named queue
Mail::to($user)->queue((new MyMail($user))->onQueue('emails'));

// Specific connection
Mail::to($user)->queue((new MyMail($user))->onConnection('redis'));

// Delay
Mail::to($user)->queue((new MyMail($user))->delay(now()->addMinutes(5)));
```

## Reviewing Existing Code

When you see `Mail::to()->send()` anywhere in the codebase:

1. Confirm the Mailable has `use Queueable, SerializesModels`
2. Replace `->send(` with `->queue(`
3. Verify `QUEUE_CONNECTION` is set appropriately in `.env`
4. Do **not** add `implements ShouldQueue` unless you want to enforce queuing regardless of call site

## Multiple Recipients

```php
// ✅ Still queue
Mail::to($primary)
    ->cc($ccUsers)
    ->bcc($bccUsers)
    ->queue(new MyMail($data));
```

## Notifications

For `Notification` classes that use the `mail` channel, implement `ShouldQueue` on the notification itself:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class MyNotification extends Notification implements ShouldQueue
{
    use Queueable;
}
```

This is equivalent to queuing the email without calling `queue()` explicitly.
