const baseURL = 'http://localhost';
function handleGoogleSignIn(response) {
      // Send credential to backend
      fetch(baseURL + '/api/google-signin', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              credential: response.credential
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Show success message
              showAlert('success', 'Login berhasil! Mengalihkan...');
              
              // Redirect after 1 second
              setTimeout(() => {
                  window.location.href = data.redirect || baseURL + '/';
              }, 1000);
          } else {
              // Show error message
              showAlert('danger', data.message || 'Terjadi kesalahan saat login dengan Google');
          }
      })
      .catch(error => {
          showAlert('danger', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
      });
}

function showAlert(type, message) {
      const alertContainer = document.getElementById('alert-container');
      const alertHtml = `
          <div class="alert alert-${type} alert-dismissible fade show" role="alert">
              ${message}
              
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