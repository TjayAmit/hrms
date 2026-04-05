# Step 2 API Authentication Plan

This plan implements API authentication system using Laravel Sanctum for token-based authentication, rate limiting, and CORS configuration to support mobile and third-party integrations.

## Current Status Analysis

**Already Implemented:**
- Laravel Fortify handles web authentication
- Spatie Permissions provides role-based access control
- Basic routing structure exists
- React/Inertia frontend is configured

**Missing Components:**
- Laravel Sanctum package not installed
- No API authentication middleware
- Missing API routes structure
- No rate limiting configuration
- CORS not configured for API access
- No API documentation setup

## Implementation Plan

### Feature 1: Sanctum Token-Based Authentication

#### Backend Setup
- [ ] Install Laravel Sanctum package
- [ ] Publish Sanctum configuration and migrations
- [ ] Configure Sanctum guards in auth.php
- [ ] Update User model with HasApiTokens trait
- [ ] Create API token management endpoints
- [ ] Set up token revocation functionality

#### API Routes Structure
- [ ] Create api.php routes file
- [ ] Implement user authentication endpoints
- [ ] Add protected API routes for HRMS data
- [ ] Create token refresh endpoints
- [ ] Add logout endpoint for API

#### Token Management
- [ ] Create token issuance service
- [ ] Implement token expiration handling
- [ ] Add token permissions integration
- [ ] Create token listing/revocation UI
- [ ] Set up automatic token cleanup

### Feature 2: API Rate Limiting

#### Rate Limiting Configuration
- [ ] Configure API-specific rate limiters
- [ ] Implement tiered rate limiting by user role
- [ ] Add burst protection for sensitive endpoints
- [ ] Create rate limiting middleware
- [ ] Set up rate limit headers in responses

#### Rate Limiting Rules
- [ ] Authentication endpoints: 5 requests per minute
- [ ] General API: 60 requests per minute
- [ ] Admin endpoints: 100 requests per minute
- [ ] Sensitive operations: 3 requests per minute
- [ ] Implement IP-based limiting fallback

### Feature 3: API Documentation

#### Documentation Setup
- [ ] Install API documentation package (Swagger)
- [ ] Configure API documentation generation
- [ ] Document authentication endpoints
- [ ] Document HRMS data endpoints
- [ ] Add request/response examples
- [ ] Set up automatic documentation updates

#### Documentation Features
- [ ] Interactive API testing interface
- [ ] Authentication flow documentation
- [ ] Error response documentation
- [ ] Rate limiting information
- [ ] Code examples for mobile clients

### Feature 4: CORS Configuration

#### CORS Setup
- [ ] Publish CORS configuration file
- [ ] Configure allowed origins for API
- [ ] Set up credentials support
- [ ] Configure allowed headers and methods
- [ ] Add preflight request handling
- [ ] Test cross-origin requests

#### Security Considerations
- [ ] Restrict origins to known domains
- [ ] Configure secure headers
- [ ] Set up proper caching headers
- [ ] Add environment-specific CORS rules
- [ ] Implement origin validation

## Technical Implementation Details

### Sanctum Configuration
- [ ] Configure sanctum.php settings
- [ ] Set up token expiration times
- [ ] Configure authentication guards
- [ ] Set up middleware stack
- [ ] Integrate with existing permissions

### API Security
- [ ] Implement request validation
- [ ] Add response formatting standards
- [ ] Set up error handling
- [ ] Configure logging for API requests
- [ ] Add request/response middleware

### Integration with Existing System
- [ ] Connect API auth with web auth
- [ ] Share permissions between systems
- [ ] Sync user roles to API tokens
- [ ] Maintain session compatibility
- [ ] Ensure consistent user experience

## Testing Strategy

### API Authentication Tests
- [ ] Token issuance test ✅ Ready for test case
- [ ] Token validation test ✅ Ready for test case
- [ ] Token revocation test ✅ Ready for test case
- [ ] Expired token handling test ✅ Ready for test case
- [ ] Invalid token rejection test ✅ Ready for test case

### Rate Limiting Tests
- [ ] Basic rate limiting test ✅ Ready for test case
- [ ] Tiered rate limiting test ✅ Ready for test case
- [ ] Burst protection test ✅ Ready for test case
- [ ] Rate limit headers test ✅ Ready for test case
- [ ] IP fallback test ✅ Ready for test case

### CORS Tests
- [ ] Cross-origin request test ✅ Ready for test case
- [ ] Preflight request test ✅ Ready for test case
- [ ] Credentials support test ✅ Ready for test case
- [ ] Origin validation test ✅ Ready for test case
- [ ] Error handling test ✅ Ready for test case

### Integration Tests
- [ ] Web/API auth compatibility test ✅ Ready for test case
- [ ] Permission synchronization test ✅ Ready for test case
- [ ] Mobile client authentication test ✅ Ready for test case
- [ ] Third-party integration test ✅ Ready for test case

## Implementation Order

### Step 2A: Sanctum Setup (Days 1-2)
1. Install and configure Sanctum
2. Set up API routes structure
3. Implement token management
4. Create basic authentication endpoints
5. Write initial tests

### Step 2B: Security & Rate Limiting (Days 3-4)
1. Configure rate limiting
2. Set up CORS configuration
3. Add security middleware
4. Implement error handling
5. Write security tests

### Step 2C: Documentation & Integration (Days 5-6)
1. Set up API documentation
2. Document all endpoints
3. Test mobile integration
4. Optimize performance
5. Complete integration tests

## API Endpoints Structure

### Authentication Endpoints
```
POST /api/auth/login - Issue API token
POST /api/auth/logout - Revoke token
POST /api/auth/refresh - Refresh token
GET  /api/auth/user - Get current user
POST /api/auth/register - Register new user (optional)
```

### Protected Endpoints
```
GET    /api/employees - List employees
POST   /api/employees - Create employee
PUT    /api/employees/{id} - Update employee
DELETE /api/employees/{id} - Delete employee

GET    /api/payroll - Get payroll data
POST   /api/attendance - Record attendance
GET    /api/reports - Generate reports
```

## Success Criteria

- API tokens can be issued and validated securely
- Rate limiting prevents abuse while allowing legitimate use
- CORS configuration enables mobile and third-party access
- API documentation is comprehensive and up-to-date
- All security tests pass
- Mobile clients can authenticate and access data
- Performance benchmarks are met
- Integration with existing web authentication works seamlessly

## Dependencies & Prerequisites

- Laravel Sanctum package (needs installation)
- API documentation package (Scribe/Docus)
- CORS configuration package (fruitcake/laravel-cors)
- Testing framework (Pest - already configured)
- Existing authentication system (Step 1 completion)

## Risk Mitigation

- Implement proper token expiration handling
- Use secure token storage practices
- Monitor API usage and abuse patterns
- Keep documentation synchronized with code changes
- Test thoroughly with various client types
- Implement fallback authentication methods
- Regular security audits of API endpoints
