# Security Model

## OWASP Best Practices
Apply OWASP best practices throughout the application.

## Required Security Measures

### Input Validation & Authorization
- Validation of all inputs
- Authorization policies for all resources
- Rate limiting on all endpoints
- Mass assignment protection

### SSRF Prevention
- URL allowlist by provider
- Private IP blocking (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.0/8, 169.254.0.0/16)
- DNS rebinding protection where practical
- Accept only HTTPS
- Reject localhost, private and link-local IPs
- Reject file://, ftp://, gopher:// and similar schemes
- Prevent redirects to disallowed domains
- Limit redirect count

### Response & Execution Safety
- Maximum response size limits
- Request timeouts
- Safe JSON parsing
- No `eval`
- No arbitrary code execution
- No arbitrary shell execution

### Secrets & Authentication
- Secure secrets through environment variables only
- Password hashing (bcrypt/argon2)
- Secure session configuration
- Security headers (CSP, HSTS, X-Frame-Options, etc.)

### Audit & Monitoring
- Audit log for admin actions
- Structured application logs with request IDs
- Never log: passwords, SMS credentials, access tokens, full cookies, sensitive personal information, unmasked phone numbers

### Database & Query Safety
- SQL injection protection through framework query APIs (Eloquent/Query Builder)
- Output escaping to prevent XSS
- Prepared statements for all dynamic queries

## External URL Handling
- Only configured provider domains allowed
- Strict URL validation before any HTTP request
- Circuit breaker pattern for provider failures
- Per-provider rate limiting and retry with exponential backoff

## Provider Security
- No storage of raw cookies or user session data
- Provider credentials only via environment variables
- Provider health monitoring and alerting
- Graceful handling of CAPTCHA, 403, 429, 5xx, invalid JSON, schema changes