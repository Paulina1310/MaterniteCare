

const API_BASE = 'http://localhost/MaterniteCare/Backend/API';
 

/* Fonction d'appel API avec token d'authentification */

async function apiCall(endpoint, method = 'GET', data = null) {
    const token = localStorage.getItem('token');
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, options);
        
       
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {

            let errorMessage = `Erreur HTTP ${response.status}`;
            
            if (contentType && contentType.includes('application/json')) {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } else {
                const text = await response.text();
                if (text) errorMessage = text.substring(0, 200);
            }
            
            throw new Error(errorMessage);
        }
        
        // ✅ Parser le JSON seulement si OK
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            return await response.text();
        }
        
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/* Formater une date au format*/
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/* Formater une date avec heure*/

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
/*Afficher une notification toast */

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

/** Déconnexion de l'utilisateur*/
function logout() {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
        localStorage.clear();
        window.location.href = 'connect.html';
    }
}

/* Vérifier l'authentification et le rôle requis*/

function requireAuth(requiredType, requiredNiveauMin = null) {
    const user = JSON.parse(localStorage.getItem('user'));
    const token = localStorage.getItem('token');
    const typeCompte = localStorage.getItem('type_compte');
    
    if (!user || !token) {
        alert('Veuillez vous connecter');
        window.location.href = 'connect.html';
        return null;
    }
    
    if (requiredType && typeCompte !== requiredType) {
        alert('Accès non autorisé pour votre profil');
        window.location.href = 'connect.html';
        return null;
    }
    
    if (requiredNiveauMin && user.niveau_acces < requiredNiveauMin) {
        alert('Accès non autorisé : niveau d\'accès insuffisant');
        window.location.href = 'connect.html';
        return null;
    }
    
    return user;
}