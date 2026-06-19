document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const messageDiv = document.getElementById('message');
    
    try {
        const response = await fetch('http://localhost/MaterniteCare/Backend/API/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                EMAIL: email,
                MOT_DE_PASSE: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
messageDiv.className = 'mb-4 p-3 rounded bg-green-100 text-green-700';
            messageDiv.textContent = '✅ ' + data.message;
            messageDiv.classList.remove('hidden');
            
           
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('type_compte', data.user.type_compte);
            
            console.log('Connexion réussie:', data.user);
            
           
            setTimeout(() => {
                if (data.user.type_compte === 'PATIENTE') {
                    window.location.href = 'dash_patiente.html';
                } else if (data.user.type_compte === 'PERSONNEL') {
                    const niveau = data.user.niveau_acces || 0;
                    if (niveau >= 10) {
                        window.location.href = 'admin_dash.html';
                    } else {
                        window.location.href = 'medecin_dash.html';
                    }
                }
            }, 1500);
            
        } else {
            messageDiv.className = 'mb-4 p-3 rounded bg-red-100 text-red-700';
            messageDiv.textContent =  data.message;
            messageDiv.classList.remove('hidden');
        }
    } catch (error) {
        messageDiv.className = 'mb-4 p-3 rounded bg-red-100 text-red-700';
        messageDiv.textContent = '️ Erreur: ' + error.message;
        messageDiv.classList.remove('hidden');
    }
});