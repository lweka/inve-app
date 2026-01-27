document.addEventListener('DOMContentLoaded', function() {
    // Calcul du prix en temps réel
    const participantsInput = document.getElementById('nombre_participants');
    const priceDisplay = document.getElementById('price-display');
    
    if (participantsInput && priceDisplay) {
        participantsInput.addEventListener('input', calculatePrice);
        
        function calculatePrice() {
            const participants = parseInt(participantsInput.value) || 0;
            let price = 0;
            
            if (participants <= 20) {
                price = 35;
            } else if (participants <= 50) {
                price = 35 + (participants - 20) * 1.5;
            } else if (participants <= 100) {
                price = 35 + 30 * 1.5 + (participants - 50) * 1.0;
            } else {
                price = 35 + 30 * 1.5 + 50 * 1.0 + (participants - 100) * 0.5;
            }
            
            priceDisplay.textContent = price.toFixed(2);
        }
        
        // Initial calculation
        calculatePrice();
    }
    
    // Validation des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            const errorElement = input.parentElement.querySelector('.error-message');
            
            // Reset previous errors
            if (errorElement) {
                errorElement.textContent = '';
            }
            
            // Check if empty
            if (!input.value.trim()) {
                isValid = false;
                if (errorElement) {
                    errorElement.textContent = 'Ce champ est obligatoire';
                }
                input.style.borderColor = '#e74c3c';
            } else {
                input.style.borderColor = '#ddd';
                
                // Email validation
                if (input.type === 'email') {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(input.value)) {
                        isValid = false;
                        if (errorElement) {
                            errorElement.textContent = 'Veuillez entrer un email valide';
                        }
                        input.style.borderColor = '#e74c3c';
                    }
                }
                
                // Phone validation
                if (input.id === 'telephone') {
                    const phonePattern = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/;
                    if (!phonePattern.test(input.value)) {
                        isValid = false;
                        if (errorElement) {
                            errorElement.textContent = 'Veuillez entrer un numéro de téléphone valide';
                        }
                        input.style.borderColor = '#e74c3c';
                    }
                }
            }
        });
        
        return isValid;
    }
    
    // Validation en temps réel
    const realTimeInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    realTimeInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            const errorElement = this.parentElement.querySelector('.error-message');
            if (errorElement && errorElement.textContent) {
                validateField(this);
            }
        });
    });
    
    function validateField(field) {
        const errorElement = field.parentElement.querySelector('.error-message');
        
        if (!field.value.trim()) {
            if (errorElement) {
                errorElement.textContent = 'Ce champ est obligatoire';
            }
            field.style.borderColor = '#e74c3c';
            return false;
        } else {
            if (errorElement) {
                errorElement.textContent = '';
            }
            field.style.borderColor = '#ddd';
            
            // Email validation
            if (field.type === 'email') {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(field.value)) {
                    if (errorElement) {
                        errorElement.textContent = 'Veuillez entrer un email valide';
                    }
                    field.style.borderColor = '#e74c3c';
                    return false;
                }
            }
            
            // Phone validation
            if (field.id === 'telephone') {
                const phonePattern = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/;
                if (!phonePattern.test(field.value)) {
                    if (errorElement) {
                        errorElement.textContent = 'Veuillez entrer un numéro de téléphone valide';
                    }
                    field.style.borderColor = '#e74c3c';
                    return false;
                }
            }
            
            return true;
        }
    }
});

// Fonction pour générer des QR codes (utilisera une bibliothèque externe)
function generateQRCode(elementId, text, width = 128, height = 128) {
    // Cette fonction nécessite l'inclusion de la bibliothèque qrcode.js
    if (typeof QRCode !== 'undefined') {
        new QRCode(document.getElementById(elementId), {
            text: text,
            width: width,
            height: height
        });
    } else {
        console.error("La bibliothèque QRCode n'est pas chargée");
    }
}