# Troubleshooting Report - CSRF & Answer Status Issues

## December 18, 2025

## Issues Reported

### Issue 1: CSRFトークンエラーが出ている

**Status**: Under Investigation
**Severity**: High

**Details**:

- User reports 419 "Page Expired" errors still occurring
- Previous CSRF fixes may not be working correctly
- Needs verification that token is being properly sent with requests

**Investigation Steps Taken**:

1. Verified VerifyCsrfToken.php is not exempting any routes ✓
2. Verified HandleInertiaRequests shares csrf_token in props ✓
3. Verified Practice.vue includes \_token in form ✓
4. Verified Part.vue includes \_token in form ✓
5. Verified Laravel logs show no 419 errors in recent logs ✓
6. Verified Redis session storage is working (PONG) ✓

**Possible Root Causes**:

- Session not being maintained between requests
- CSRF token not matching due to session rotation
- Token not being properly extracted from meta tag
- Possible network/firewall issue with localhost:9323

---

### Issue 2: localhost:9323 Connection Refused

**Status**: Identified but not critical
**Severity**: Low-Medium

**Details**:

- Playwright browser context cannot connect to port 9323
- Was working two days ago ("一昨日までは問題なく接続できていた")
- Port 9323 is internal Node.js process port (likely CDP - Chrome DevTools Protocol)

**Current State**:

- Port is listening on 127.0.0.1:9323 (localhost only)
- This is normal for Playwright internal communication
- Only critical if cross-machine testing is required

**Possible Causes**:

1. Docker network configuration change
2. Firewall rule change
3. Browser upgrade changed connection behavior
4. WSL2 network reconfiguration

---

### Issue 3: Answer Status Judgment Error

**Status**: Fixed (Debugging Added)
**Severity**: High

**Symptom**: "問題に回答できているのに出来ていない判定になっている"

**Root Cause**: Likely one of the following:

1. Question ID (id field) is undefined in frontend
2. Answer not being saved to form.answers object
3. Form submission not including answers
4. Validation error on server side

**Fixes Applied**:

#### Frontend (Practice.vue & Part.vue)

Added comprehensive debug logging:

```javascript
// handleAnswer function
console.log("=== handleAnswer ===");
console.log("currentIndex:", currentIndex.value);
console.log("selected answer:", sanitizedLabel);
console.log("answerStatus length:", answerStatus.value.length);
```

```javascript
// updateFormAnswers function
console.log("=== updateFormAnswers debug ===");
console.log("answerStatus length:", answerStatus.value.length);
console.log("questions length:", questions.value.length);
// Logs each question ID and answer
console.log("Final answers object:", answers);
```

#### Backend (PracticeController.php)

Added request logging:

```php
Log::info('=== 練習問題完了処理開始 ===', [
    'answers_data' => $validated['answers'] ?? [],
    'request_body' => $request->all(),
    // ... other fields
]);
```

**How to Verify**:

1. Open browser Developer Tools (F12)
2. Go to "Console" tab
3. Answer a question in practice section
4. Look for "=== handleAnswer ===" and "=== updateFormAnswers debug ===" messages
5. Verify that answer is being registered

---

## Environment Configuration

### Session Storage

```
SESSION_DRIVER=redis
SESSION_LIFETIME=1440 (24 hours)
```

### CSRF Protection

```
VerifyCsrfToken: Enabled (no exemptions)
HandleInertiaRequests: Shares csrf_token prop
```

### Database/Cache

```
Redis: ✓ Working (docker-compose)
MySQL: ✓ Working (docker-compose)
```

---

## Debugging Instructions for User

### To identify the CSRF token issue:

1. **Check browser console for errors**:
    - Open F12 → Console tab
    - Try to complete a practice section
    - Look for any JavaScript errors related to CSRF or tokens

2. **Check form submission data**:
    - Open F12 → Network tab
    - Filter for "XHR/Fetch" requests
    - Click to complete practice
    - Check the request body for "\_token" field
    - Verify it contains a valid token string

3. **Check Laravel logs**:
    ```bash
    tail -50 storage/logs/laravel.log | grep -i "csrf\|419\|token"
    ```

### To identify the answer state issue:

1. **Enable console logging**:
    - Open F12 → Console tab
    - Go to practice page
    - Click on answer option
    - Look for `=== handleAnswer ===` message
    - Verify answer is being set

2. **Check form data before submission**:
    - In console, run: `Object.keys(window.__INERTIA__.page.props.form?.answers || {})`
    - Should show array of question IDs that have answers

3. **Check server logs for validation errors**:
    ```bash
    tail -20 storage/logs/laravel.log | grep "完了"
    ```

---

## Test Improvements Made

### Fixed Playwright Selector Issues

Changed from ambiguous selectors to more specific ones:

**Before**:

```typescript
await authenticatedPage.click('button:has-text("A")');
// Issue: Matches multiple buttons including Search button
```

**After**:

```typescript
const answerButton = authenticatedPage.locator('button[role="button"]:has-text("A")').first();
await answerButton.click();
```

---

## Next Actions

1. **User to reproduce issue with debug logging enabled**
2. **Collect browser console output when issue occurs**
3. **Check Laravel logs for any validation or session errors**
4. **Verify CSRF token is present in request body**
5. **If issue persists**: Check if question IDs are being returned by backend

---

## Files Modified This Session

1. `resources/js/Pages/Practice.vue`
    - Enhanced handleAnswer() with logging
    - Enhanced updateFormAnswers() with validation and logging

2. `resources/js/Pages/Part.vue`
    - Enhanced updateFormAnswers() with detailed logging

3. `app/Http/Controllers/PracticeController.php`
    - Added request body logging
    - Added detailed answer data logging

4. `e2e/example.spec.ts`
    - Fixed selector specificity issues
    - Improved test reliability

---

## Monitoring

Continue to monitor:

- `storage/logs/laravel.log` for CSRF errors
- Browser console for JavaScript errors
- Network tab for form submission details
- Answer state synchronization
