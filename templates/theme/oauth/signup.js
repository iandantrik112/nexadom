/**
 * Google Sign-In Integration
 * Handles Google OAuth authentication for signup and signin
 */
const baseURL = "http://localhost";

/**
 * Handle Google Sign-In Response
 * Called when user successfully signs in with Google
 */
function handleGoogleSignIn(response) {
    // Show loading
    showAlert('info', 'Memproses pendaftaran dengan Google...');
    
    // Send credential to backend
    const apiUrl = baseURL + '/api/google-signup';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            credential: response.credential
        })
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('success', 'Pendaftaran berhasil! Mengalihkan...');
            
            // Redirect after 1 second
            setTimeout(() => {
                window.location.href = data.redirect || baseURL + '/signin';
            }, 1000);
        } else {
            // Show error message
            showAlert('danger', data.message || 'Terjadi kesalahan saat mendaftar dengan Google');
        }
    })
    .catch(error => {
        showAlert('danger', 'Terjadi kesalahan koneksi: ' + error.message);
    });
}

/**
 * Manual Google Sign-In function for fallback button
 * Used when official Google button fails to load
 */
function manualGoogleSignIn() {
    if (typeof google === 'undefined' || !google.accounts) {
        showAlert('danger', 'Google Sign-In library belum dimuat. Refresh halaman dan coba lagi.');
        return;
    }
    
    try {
        google.accounts.id.initialize({
            client_id: '439618760894-hrjfpe69fqmfttbcep17ik80angbi8pt.apps.googleusercontent.com',
            callback: handleGoogleSignIn,
            ux_mode: 'popup'
        });
        
        google.accounts.id.prompt((notification) => {
            if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                showAlert('info', 'Silakan pilih akun Google Anda di popup yang muncul');
            }
        });
    } catch (error) {
        showAlert('danger', 'Error: ' + error.message);
    }
}

/**
 * Show Alert Message
 * Display Bootstrap alert with auto-dismiss
 */
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        return;
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }
    }, 5000);
}

/**
 * Test Google API Connection
 * Debug function to test API endpoints
 */
function testGoogleAPI() {
    if (typeof google === 'undefined') {
        showAlert('danger', '❌ Google library belum dimuat!');
        return;
    }
    
    if (!google.accounts) {
        showAlert('danger', '❌ Google accounts tidak tersedia!');
        return;
    }
    
    showAlert('success', '✅ Google library loaded!');
    
    const testUrl = baseURL + '/api/google-test';
    
    fetch(testUrl)
    .then(response => {
        return response.json();
    })
    .then(data => {
        showAlert('success', 'API berfungsi! Testing POST endpoint...');
        
        const apiUrl = baseURL + '/api/google-signup';
        
        return fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ test: true })
        });
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        showAlert('info', '✅ Semua API berfungsi! Check console untuk detail.');
    })
    .catch(error => {
        showAlert('danger', 'API endpoint error: ' + error.message);
    });
}

/**
 * Initialize Google Button Check
 * Check if Google button loaded, if not show manual button
 */
function initGoogleButtonCheck() {
    setTimeout(() => {
        const googleBtn = document.getElementById('google-signin-button');
        const manualBtn = document.getElementById('google-manual-btn');
        
        if (!googleBtn || !manualBtn) {
            return;
        }
        
        if (googleBtn.children.length === 0) {
            manualBtn.style.display = 'block';
        } else {
            manualBtn.style.display = 'none';
        }
    }, 2000);
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGoogleButtonCheck);
} else {
    initGoogleButtonCheck();
}
