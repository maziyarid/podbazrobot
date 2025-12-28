# API Connection Fix Summary

## Issue Report
User reported API connection errors:
- Blackbox API: ❌ خطای HTTP 404
- Tavily API: ❌ خطای HTTP 401

## Root Causes Identified

### Blackbox API - HTTP 404 Error
**Problem:**
- Wrong endpoint: `https://api.blackbox.ai/api/chat`
- Wrong model format: `claude-sonnet-4-20250514`

**Solution:**
- Correct endpoint: `https://api.blackbox.ai/chat/completions`
- Correct model format: `blackboxai` (and other supported models)

### Tavily API - HTTP 401 Error
**Problem:**
- Authentication was sent in request body as `api_key` field
- No `Authorization` header was present

**Solution:**
- Authentication now uses `Authorization: Bearer {api_key}` header
- Removed `api_key` from request body

## Changes Made (Commit: be91911)

### 1. includes/class-blackbox-api.php
```php
// Changed endpoint
private $base_url = 'https://api.blackbox.ai/chat/completions';

// Changed default model
$this->model = get_option('pbr_claude_model', 'blackboxai');
```

### 2. includes/class-tavily-api.php
```php
// Added Authorization header in search() method
'headers' => [
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $this->api_key,
],

// Removed api_key from body
$body = [
    'query' => $query,
    'search_depth' => 'advanced',
    // ... (no api_key field)
];

// Added Authorization header in test_connection() method
'headers' => [
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $this->api_key,
],
```

### 3. admin/views/settings-page.php
Updated model options to match Blackbox AI supported models:
- blackboxai (default)
- blackboxai-pro
- gpt-4o
- gpt-4o-mini
- claude-sonnet-3.5
- gemini-pro

### 4. podbaz-robot.php
Updated activation hook default model:
```php
'pbr_claude_model' => 'blackboxai',
```

## API Specifications Used

### Blackbox AI API (2024)
- **Endpoint:** `POST https://api.blackbox.ai/chat/completions`
- **Authentication:** `Authorization: Bearer {api_key}`
- **Request Format:**
  ```json
  {
    "model": "blackboxai",
    "messages": [{"role": "user", "content": "..."}],
    "max_tokens": 16000,
    "temperature": 0.7
  }
  ```

### Tavily API (2024)
- **Endpoint:** `POST https://api.tavily.com/search`
- **Authentication:** `Authorization: Bearer {api_key}`
- **Request Format:**
  ```json
  {
    "query": "search query",
    "search_depth": "advanced",
    "include_answer": true,
    "max_results": 8
  }
  ```

## Testing Status
✅ PHP syntax validated for all modified files
✅ Authentication methods updated to 2024 specifications
✅ Both APIs should now connect successfully with valid API keys

## Next Steps for User
1. Go to **Podbaz Robot → Settings**
2. Enter your Blackbox AI API key
3. Enter your Tavily API key
4. Click "Test" button for each API to verify connection
5. Both should show: ✅ اتصال برقرار است
