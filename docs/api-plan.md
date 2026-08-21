# API Plan

## Versioned API
Use `/api/v1` as the base for all API endpoints.

## Endpoints

### Authentication
- POST `/auth/register`
- POST `/auth/login`
- POST `/auth/logout`
- GET `/auth/me`
- POST `/auth/verify-mobile`

### Product Preview
- POST `/product-previews`
- GET `/product-previews/{id}`

### Watches
- GET `/watches`
- POST `/watches`
- GET `/watches/{watch}`
- PATCH `/watches/{watch}`
- DELETE `/watches/{watch}`
- POST `/watches/{watch}/pause`
- POST `/watches/{watch}/resume`
- POST `/watches/{watch}/check-now`

### Workflows
- GET `/workflow-types`
- GET `/watches/{watch}/workflows`
- POST `/watches/{watch}/workflows`
- PATCH `/workflows/{workflow}`
- DELETE `/workflows/{workflow}`
- POST `/workflows/{workflow}/validate`

### Observations
- GET `/watches/{watch}/observations`

### Notifications
- GET `/notifications`
- GET `/notifications/{notification}`

### Admin
- GET `/admin/dashboard`
- GET `/admin/users`
- GET `/admin/watches`
- GET `/admin/providers`
- GET `/admin/notifications`
- POST `/admin/watches/{watch}/check-now`

## Implementation Guidelines
- Use Form Requests for validation
- Use API Resources for response formatting
- Provide OpenAPI documentation
- Consistent error response format
- Include Request IDs
- Implement pagination, filtering, and sorting
- Apply authorization and rate limiting

## Error Response Format
```json
{
  "message": "Unable to fetch product data.",
  "code": "PROVIDER_UNAVAILABLE",
  "request_id": "..."
}
```