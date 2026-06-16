# API Reference

## Authentication (`/api/v1/auth/`)

### Register
`POST /auth/register`
```json
// Request
{ "name": "John", "email": "john@test.com", "password": "Str0ng!Pass", "password_confirmation": "Str0ng!Pass" }
// Response 201
{ "status": 201, "message": "...", "data": { "user": {...}, "access_token": "...", "token_type": "Bearer" } }
```

### Login
`POST /auth/login`
```json
// Request
{ "email": "john@test.com", "password": "Str0ng!Pass" }
// Response 200
{ "status": 200, "message": "...", "data": { "user": {...}, "access_token": "...", "token_type": "Bearer" } }
```

### Logout
`POST /auth/logout` (Auth: Bearer)
```json
// Response 200
{ "status": 200, "message": "Logout successful.", "data": null }
```

### Get Me
`GET /auth/me` (Auth: Bearer)
```json
// Response 200
{ "status": 200, "message": "...", "data": { "id": "...", "name": "...", "email": "...", "roles": [...], "permissions": [...],  "email_verified_at": "...", "created_at": "...", "updated_at": "...", "deleted_at": null } }
```

### Forgot Password
`POST /auth/forgot-password`
```json
// Request
{ "email": "john@test.com" }
// Response 200
{ "status": 200, "message": "...", "data": null }
```

### Reset Password
`POST /auth/reset-password`
```json
// Request
{ "email": "john@test.com", "token": "...", "password": "New!Pass123", "password_confirmation": "New!Pass123" }
// Response 200
```

### Verify Email
`GET /auth/verify-email/{id}/{hash}?expires=...&signature=...` (Signed URL)

### Resend Verification
`POST /auth/email/verification-notification` (Auth: Bearer)
```json
// Response 200
```

### Social Redirect
`GET /auth/social/{provider}/redirect`
```json
// Response 200
{ "data": { "url": "https://accounts.google.com/o/oauth2/auth?..." } }
```

### Social Callback
`GET /auth/social/{provider}/callback`
```json
// Response 200
{ "status": 200, "message": "...", "data": { "user": {...}, "access_token": "...", "token_type": "Bearer" } }
```

### List Devices
`GET /auth/devices` (Auth: Bearer)
```json
// Response 200
{ "status": 200, "message": "...", "data": [{"id": 1, "name": "...", "ip_address": "...", "user_agent": "...", "last_used_at": "...", "created_at": "..."}] }
```

### Delete Device
`DELETE /auth/devices/{device}` (Auth: Bearer)
```json
// Response 200
{ "status": 200, "message": "Device logged out.", "data": null }
```

### Logout Other Devices
`POST /auth/devices/logout-others` (Auth: Bearer)
```json
// Response 200
{ "status": 200, "message": "Other devices logged out.", "data": null }
```

---

## Users (`/api/v1/users/`) -- requires `user.*` permission

### List
`GET /users?page=1&per_page=15&search=john&role=admin&status=active` (Auth: Bearer)

### Create
`POST /users` (Auth: Bearer)
```json
// Request
{ "name": "Jane", "email": "jane@test.com", "password": "Str0ng!Pass", "password_confirmation": "Str0ng!Pass", "roles": ["user"] }
```

### Show
`GET /users/{user}` (Auth: Bearer)

### Update
`PUT /users/{user}` (Auth: Bearer)
```json
// Request
{ "name": "Jane Updated", "email": "jane@test.com", "roles": ["admin"] }
```

### Delete
`DELETE /users/{user}` (Auth: Bearer)

### Bulk Delete
`POST /users/bulk/delete` (Auth: Bearer)
```json
// Request
{ "ids": ["ulid-1", "ulid-2"] }
```

### Bulk Restore
`POST /users/bulk/restore` (Auth: Bearer)
```json
// Request
{ "ids": ["ulid-1", "ulid-2"] }
```

---

## Roles (`/api/v1/roles/`) -- requires `role.*` permission

### List
`GET /roles?page=1&per_page=15&search=admin` (Auth: Bearer)

### Create
`POST /roles` (Auth: Bearer)
```json
// Request
{ "name": "editor", "permissions": ["user.view", "role.view"] }
```

### Show
`GET /roles/{role}` (Auth: Bearer)

### Update
`PUT /roles/{role}` (Auth: Bearer)
```json
// Request
{ "name": "editor-updated", "permissions": ["user.view"] }
```

### Delete
`DELETE /roles/{role}` (Auth: Bearer)

### Bulk Delete
`POST /roles/bulk/delete` (Auth: Bearer)
```json
// Request
{ "ids": ["ulid-1", "ulid-2"] }
```

### Bulk Restore
`POST /roles/bulk/restore` (Auth: Bearer)
```json
// Request
{ "ids": ["ulid-1", "ulid-2"] }
```

---

## Permissions (`/api/v1/permissions/`) -- requires `permission.*` permission

### List
`GET /permissions?page=1&per_page=15` (Auth: Bearer)

### Create
`POST /permissions` (Auth: Bearer)
```json
// Request
{ "name": "report.view" }
```

### Show
`GET /permissions/{permission}` (Auth: Bearer)

### Update
`PUT /permissions/{permission}` (Auth: Bearer)
```json
// Request
{ "name": "report.view" }
```

### Delete
`DELETE /permissions/{permission}` (Auth: Bearer)
