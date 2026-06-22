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
`POST /auth/logout` (Auth: Bearer) -- Response 200

### Get Me
`GET /auth/me` (Auth: Bearer) -- Returns user with roles/permissions

### Forgot Password
`POST /auth/forgot-password` -- Request: `{ "email": "..." }` -- Response 200

### Reset Password
`POST /auth/reset-password` -- Request: `{ "email", "token", "password", "password_confirmation" }`

### Verify Email
`GET /auth/verify-email/{id}/{hash}?expires=...&signature=...` (Signed URL)

### Resend Verification
`POST /auth/email/verification-notification` (Auth: Bearer)

### Social Redirect
`GET /auth/social/{provider}/redirect` -- Returns `{ "data": { "url": "..." } }`

### Social Callback
`GET /auth/social/{provider}/callback` -- Returns token

### List Devices
`GET /auth/devices` (Auth: Bearer)

### Delete Device
`DELETE /auth/devices/{device}` (Auth: Bearer)

### Logout Other Devices
`POST /auth/devices/logout-others` (Auth: Bearer)

---

## Users (`/api/v1/users/`) -- requires `user.*` permission

- `GET /users?page=1&per_page=15&search=john&role=admin&status=active`
- `POST /users` -- `{ "name", "email", "password", "password_confirmation", "roles": [...] }`
- `GET /users/{user}`
- `PUT /users/{user}` -- `{ "name", "email", "roles": [...] }`
- `DELETE /users/{user}`
- `POST /users/bulk/delete` -- `{ "ids": [...] }`
- `POST /users/bulk/restore` -- `{ "ids": [...] }`

## Roles (`/api/v1/roles/`) -- requires `role.*` permission

- `GET /roles?page=1&per_page=15&search=admin`
- `POST /roles` -- `{ "name", "permissions": [...] }`
- `GET /roles/{role}`
- `PUT /roles/{role}` -- `{ "name", "permissions": [...] }`
- `DELETE /roles/{role}`
- `POST /roles/bulk/delete` -- `{ "ids": [...] }`
- `POST /roles/bulk/restore` -- `{ "ids": [...] }`

## Permissions (`/api/v1/permissions/`) -- requires `permission.*` permission

- `GET /permissions?page=1&per_page=15`
- `POST /permissions` -- `{ "name" }`
- `GET /permissions/{permission}`
- `PUT /permissions/{permission}` -- `{ "name" }`
- `DELETE /permissions/{permission}`
