// Main JavaScript for Personal CV Blog

document.addEventListener('DOMContentLoaded', function() {
    // Image preview for uploads
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewId = e.target.getAttribute('data-preview') || 'imagePreview';
            const preview = document.getElementById(previewId);
            
            if (preview && file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Password strength indicator
    const newPasswordInput = document.getElementById('new_password');
    if (newPasswordInput) {
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'mt-2';
        strengthIndicator.innerHTML = `
            <small>Password strength: <span id="strength-text">None</span></small>
            <div class="progress" style="height: 5px;">
                <div id="strength-bar" class="progress-bar" style="width: 0%"></div>
            </div>
        `;
        
        newPasswordInput.parentNode.appendChild(strengthIndicator);
        
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            document.getElementById('strength-text').textContent = strength.text;
            document.getElementById('strength-bar').className = 'progress-bar ' + strength.color;
            document.getElementById('strength-bar').style.width = strength.percentage + '%';
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 500);
            }
        }, 5000);
    });
});

function checkPasswordStrength(password) {
    let score = 0;
    
    // Length check
    if (password.length >= 6) score += 1;
    if (password.length >= 8) score += 1;
    
    // Complexity checks
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^a-zA-Z0-9]/.test(password)) score += 1;
    
    const strengths = [
        { text: 'Very Weak', color: 'bg-danger', percentage: 20 },
        { text: 'Weak', color: 'bg-danger', percentage: 40 },
        { text: 'Fair', color: 'bg-warning', percentage: 60 },
        { text: 'Good', color: 'bg-info', percentage: 80 },
        { text: 'Strong', color: 'bg-success', percentage: 100 }
    ];
    
    return strengths[Math.min(score, 4)];
}

function confirmDelete(id, message = 'Are you sure you want to delete this item?') {
    if (confirm(message)) {
        window.location.href = '?delete=' + id;
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    }
    
    if (file) {
        reader.readAsDataURL(file);
    }
}