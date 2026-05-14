# Event-Driven Architecture & Background Processing

This project implements an *Event-Driven* pattern to separate core logic from secondary processes, improving API scalability and responsiveness.

## 1. User Registration Flow

When a user registers via the `RegisterController`, the process does not immediately send a verification email. Instead, the system triggers an event.

**Workflow:**
1. `RegisterUser` action creates user data in the database.
2. Action triggers the `App\Events\UserRegistered` event.
3. `Modules\Auth\Listeners\SendEmailVerificationNotification` listener catches the event.
4. Listener sends the `App\Notifications\VerifyEmail` notification.

## 2. Background Processing (Queues)

The `VerifyEmail` notification is configured to implement the `ShouldQueue` interface. This means the email sending process is moved to a Background Queue and will not affect the registration request's response time.

### How it Works:
- When a notification is sent, Laravel pushes it to the `jobs` table (or other configured queue driver).
- The queue worker (`php artisan queue:work`) processes the queue asynchronously.

## 3. Adding New Events

To implement a similar pattern in other modules:

1. Create an Event class in `app/Events`.
2. Create a Listener class in the related module's `Listeners` folder.
3. Register the Event and Listener mapping in `app/Providers/EventServiceProvider` or via `Event::listen()` in the module's Service Provider.

## 4. Scalability Benefits
With this pattern, we can easily add new listeners (e.g., sending data to a CRM or Analytics system) without modifying the core user registration code.
