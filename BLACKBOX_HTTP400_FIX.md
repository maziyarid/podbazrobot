# Blackbox API HTTP 400 Error Fix

## Issue Report (Comment #3694682503)
User reported: "Blackbox connection still has problem, Tavily connects. Blackbox returns this error: ❌ خطای HTTP 400"

## Root Cause
The HTTP 400 (Bad Request) error was caused by an **invalid model parameter**.

### Previous Configuration (Incorrect)
```php
$this->model = get_option('pbr_claude_model', 'blackboxai');
```

The value `'blackboxai'` is **not a valid model name** in the Blackbox AI API.

### What Blackbox API Expects
According to the official Blackbox AI API documentation (2024), the model parameter must be a specific model identifier such as:
- `gpt-4o`
- `gpt-4-turbo`
- `claude-3-opus`
- `claude-3-sonnet`
- `gemini-1.5-pro`
- `gpt-3.5-turbo`

## Solution (Commit: f5646f7)

### 1. Fixed Default Model in Constructor
**File:** `includes/class-blackbox-api.php`
```php
// BEFORE (Wrong)
$this->model = get_option('pbr_claude_model', 'blackboxai');

// AFTER (Correct)
$this->model = get_option('pbr_claude_model', 'gpt-4o');
```

### 2. Updated Model Options in Settings
**File:** `admin/views/settings-page.php`

Replaced invalid model names with valid ones:
```php
<option value="gpt-4o">GPT-4o (پیشنهادی)</option>
<option value="gpt-4-turbo">GPT-4 Turbo</option>
<option value="gpt-4">GPT-4</option>
<option value="claude-3-opus">Claude 3 Opus</option>
<option value="claude-3-sonnet">Claude 3 Sonnet</option>
<option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
<option value="gpt-3.5-turbo">GPT-3.5 Turbo (اقتصادی)</option>
```

### 3. Fixed Default in Activation Hook
**File:** `podbaz-robot.php`
```php
// BEFORE
'pbr_claude_model' => 'blackboxai',

// AFTER
'pbr_claude_model' => 'gpt-4o',
```

### 4. Improved Error Handling
Enhanced the `test_connection()` method to show detailed API error messages:
```php
// Now extracts and displays actual error message from API response
if (isset($body['error'])) {
    if (is_string($body['error'])) {
        $error_msg .= ': ' . $body['error'];
    } elseif (isset($body['error']['message'])) {
        $error_msg .= ': ' . $body['error']['message'];
    }
}
```

## Timeline of Fixes

### Previous Issues Fixed
1. **HTTP 404 Error (Commit: be91911)**
   - Wrong endpoint: `/api/chat` → `/chat/completions`
   - Authentication: Already correct (Bearer token)

2. **HTTP 400 Error (Commit: f5646f7)**
   - Invalid model name: `blackboxai` → `gpt-4o`
   - Added proper error message display

### Tavily API (Already Working)
Fixed in commit be91911:
- Changed from body parameter to Authorization header
- Now working correctly ✅

## Testing
✅ PHP syntax validated for all modified files
✅ Model names verified against Blackbox AI 2024 documentation
✅ Error handling tested to display detailed messages

## Expected Result
When the user tests the Blackbox API connection:
- **Previous:** ❌ خطای HTTP 400
- **Now:** ✅ اتصال به Blackbox API برقرار است

## Available Models for Users
Users can now select from these validated models:
1. **GPT-4o** (default, recommended) - Multi-modal, fast
2. **GPT-4 Turbo** - Optimized GPT-4
3. **GPT-4** - Original GPT-4
4. **Claude 3 Opus** - Anthropic's flagship model
5. **Claude 3 Sonnet** - Balanced performance
6. **Gemini 1.5 Pro** - Google's advanced model
7. **GPT-3.5 Turbo** - Cost-effective option

## User Action
No action required beyond testing:
1. Go to **Podbaz Robot → Settings**
2. Enter Blackbox API key (if not already entered)
3. Select desired model (default is GPT-4o)
4. Click "Test" button
5. Should see: ✅ اتصال به Blackbox API برقرار است
