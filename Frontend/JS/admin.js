



// Vérification authentification
const user = requireAuth('PERSONNEL');
if (!user) throw new Error('Non autorisé');

// Vérifier niveau administrateur
if (user.niveau_acces < 10) {
    alert('Accès réservé aux administrateurs');
    window.location.href = 'connect.html';
}

const idPersonnel = user.id_personne;

// Initialisation des infos utilisateur
const adminNameEl = document.getElementById('adminName');
const adminRoleEl = document.getElementById('adminRole');
if (adminNameEl) {
    adminNameEl.textContent = (user.profil?.PRENOM || '') + ' ' + (user.profil?.NOM || '');
}
if (adminRoleEl) {
    adminRoleEl.textContent = user.profil?.ROLE || 'Administrateur';
}



function toggleMobileMenu() {
    const burger = document.querySelector('.burger-menu');
    const mobileMenu = document.getElementById('mobileMenu');
    if (burger) burger.classList.toggle('active');
    if (mobileMenu) mobileMenu.classList.toggle('active');
}

function showSection(sectionId, element) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    const section = document.getElementById('section-' + sectionId);
    if (section) section.classList.add('active');
    
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    if (element && window.innerWidth > 768) element.classList.add('active');
    
    document.querySelectorAll('.mobile-nav a').forEach(a => a.classList.remove('active'));
    if (element && window.innerWidth <= 768) element.classList.add('active');
    
    if (window.innerWidth <= 768) toggleMobileMenu();
    
    switch(sectionId) {
        case 'dashboard': loadDashboard(); break;
        case 'personnel': loadPersonnel(); break;
        case 'patientes': loadPatientes(); break;
        case 'workspaces': loadWorkspaces(); break;
        case 'lits': loadLits(); break;
        case 'admissions': loadAdmissions(); break;
        case 'accouchements': loadAccouchements(); break;
        case 'audit': loadAudit(); break;
    }
}



async function loadDashboard() {
    try {
        const [patientesResult, personnelResult, litsResult, admissionsResult] = await Promise.all([
            apiCall('patiente.php'),
            apiCall('personne.php'),
            apiCall('lits.php'),
            apiCall('admissions.php')
        ]);

        const patientes = patientesResult.data || [];
        const personnel = personnelResult.data || [];
        const lits = litsResult.data || [];
        const admissions = admissionsResult.data || [];

        const patientesActives = patientes.length;
        const personnelCount = personnel.filter(p => p.PROFESSION === 'Médecin' || p.PROFESSION === 'Sage-Femme').length;
        const litsDisponibles = lits.filter(l => l.STATUT === 'Libre').length;
        const admissionsActives = admissions.filter(a => a.STATUT_ADMISSION === 'Actif').length;

        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setText('statPatientes', patientesActives);
        setText('statPersonnel', personnelCount);
        setText('statLits', `${litsDisponibles}/${lits.length}`);
        setText('statAdmissions', admissionsActives);

        // Dernières admissions
        const containerAdmissions = document.getElementById('dernieresAdmissions');
        if (containerAdmissions) {
            const recentesAdmissions = admissions.slice(0, 5);
            if (recentesAdmissions.length === 0) {
                containerAdmissions.innerHTML = '<div class="empty-state"><i class="fas fa-hospital"></i><p>Aucune admission récente</p></div>';
            } else {
                let html = '<table><thead><tr><th>Date</th><th>Patiente</th><th>Service</th><th>Urgence</th></tr></thead><tbody>';
                recentesAdmissions.forEach(a => {
                    const urgClass = a.NIVEAU_URGENCE === 'Elevé' ? 'badge-danger' : 
                                     a.NIVEAU_URGENCE === 'Modéré' ? 'badge-warning' : 'badge-success';
                    html += `<tr>
                        <td>${formatDateTime(a.DATE_ADMISSION)}</td>
                        <td>Patiente #${a.ID_PATIENTE}</td>
                        <td>${a.SERVICE || '-'}</td>
                        <td><span class="badge ${urgClass}">${a.NIVEAU_URGENCE || '-'}</span></td>
                    </tr>`;
                });
                html += '</tbody></table>';
                containerAdmissions.innerHTML = html;
            }
        }

        // Liste du personnel
        const containerPersonnel = document.getElementById('listePersonnel');
        if (containerPersonnel) {
            if (personnel.length === 0) {
                containerPersonnel.innerHTML = '<div class="empty-state"><i class="fas fa-user-md"></i><p>Aucun personnel</p></div>';
            } else {
                let html = '<table><thead><tr><th>Nom</th><th>Prénom</th><th>Profession</th><th>Email</th></tr></thead><tbody>';
                personnel.forEach(p => {
                    html += `<tr>
                        <td>${p.NOM || '-'}</td>
                        <td>${p.PRENOM || '-'}</td>
                        <td>${p.PROFESSION || '-'}</td>
                        <td>${p.EMAIL || '-'}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                containerPersonnel.innerHTML = html;
            }
        }

    } catch (error) {
        console.error('Erreur chargement dashboard:', error);
        showToast('Erreur chargement: ' + error.message, 'error');
    }
}



async function loadPersonnel() {
    try {
        const result = await apiCall('personne.php');
        const personnel = result.data || [];
        const container = document.getElementById('tablePersonnel');
        
        if (!container) return;
        
        if (personnel.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-user-md"></i><p>Aucun personnel</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Profession</th><th>Email</th><th>Tél</th></tr></thead><tbody>';
        personnel.forEach(p => {
            html += `<tr>
                <td>${p.ID_PERSONNE}</td>
                <td>${p.NOM || '-'}</td>
                <td>${p.PRENOM || '-'}</td>
                <td>${p.PROFESSION || '-'}</td>
                <td>${p.EMAIL || '-'}</td>
                <td>${p.TEL || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement personnel: ' + error.message, 'error');
    }
}



async function loadPatientes() {
    try {
        const result = await apiCall('patiente.php');
        const patientes = result.data || [];
        const container = document.getElementById('tablePatientes');
        
        if (!container) return;
        
        if (patientes.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>Aucune patiente</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Code suivi</th><th>Matricule</th><th>Groupe sanguin</th><th>Antécédents</th></tr></thead><tbody>';
        patientes.forEach(p => {
            html += `<tr>
                <td>${p.ID_PATIENTE}</td>
                <td>${p.CODE_SUIVI || '-'}</td>
                <td>${p.MATRICULE || '-'}</td>
                <td>${p.GROUPE_SANGUIN || '-'}</td>
                <td>${p.ANTECEDENT_MED || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement patientes: ' + error.message, 'error');
    }
}



async function loadWorkspaces() {
    try {
        const result = await apiCall('workspace.php');
        const workspaces = result.data || [];
        const container = document.getElementById('tableWorkspaces');
        
        if (!container) return;
        
        if (workspaces.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-hospital"></i><p>Aucun workspace</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Nom</th><th>Numéro</th><th>Capacité</th><th>Secteur</th><th>Statut</th></tr></thead><tbody>';
        workspaces.forEach(w => {
            const statutClass = w.STATUT_WORKSPACE === 'Disponible' ? 'badge-success' : 
                                w.STATUT_WORKSPACE === 'Occupé' ? 'badge-danger' : 'badge-warning';
            html += `<tr>
                <td>${w.ID_WORKSPACE}</td>
                <td>${w.NOM || '-'}</td>
                <td>${w.NUMERO || '-'}</td>
                <td>${w.CAPACITE || '-'}</td>
                <td>${w.SECTEUR || '-'}</td>
                <td><span class="badge ${statutClass}">${w.STATUT_WORKSPACE || '-'}</span></td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement workspaces: ' + error.message, 'error');
    }
}



async function loadLits() {
    try {
        const result = await apiCall('lits.php');
        const lits = result.data || [];
        const container = document.getElementById('tableLits');
        
        if (!container) return;
        
        if (lits.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-bed"></i><p>Aucun lit</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Numéro</th><th>Localisation</th><th>Statut</th></tr></thead><tbody>';
        lits.forEach(l => {
            const statutClass = l.STATUT === 'Libre' ? 'badge-success' : 
                                l.STATUT === 'Occupé' ? 'badge-danger' : 'badge-warning';
            html += `<tr>
                <td>${l.ID_LITS}</td>
                <td>${l.NUMERO || '-'}</td>
                <td>${l.LOCALISATION || '-'}</td>
                <td><span class="badge ${statutClass}">${l.STATUT || '-'}</span></td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement lits: ' + error.message, 'error');
    }
}



async function loadAdmissions() {
    try {
        const result = await apiCall('admissions.php');
        const admissions = result.data || [];
        const container = document.getElementById('tableAdmissions');
        
        if (!container) return;
        
        if (admissions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-hospital-user"></i><p>Aucune admission</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Service</th><th>Urgence</th><th>Statut</th></tr></thead><tbody>';
        admissions.forEach(a => {
            const urgClass = a.NIVEAU_URGENCE === 'Elevé' ? 'badge-danger' : 
                             a.NIVEAU_URGENCE === 'Modéré' ? 'badge-warning' : 'badge-success';
            const statClass = a.STATUT_ADMISSION === 'Actif' ? 'badge-success' : 'badge-info';
            html += `<tr>
                <td>${a.ID_ADMISSION}</td>
                <td>${formatDateTime(a.DATE_ADMISSION)}</td>
                <td>Patiente #${a.ID_PATIENTE}</td>
                <td>${a.SERVICE || '-'}</td>
                <td><span class="badge ${urgClass}">${a.NIVEAU_URGENCE || '-'}</span></td>
                <td><span class="badge ${statClass}">${a.STATUT_ADMISSION || '-'}</span></td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement admissions: ' + error.message, 'error');
    }
}

/
async function loadAccouchements() {
    try {
        const result = await apiCall('accouchements.php');
        const accouchements = result.data || [];
        const container = document.getElementById('tableAccouchements');
        
        if (!container) return;
        
        if (accouchements.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-baby-carriage"></i><p>Aucun accouchement</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Type</th><th>Résultat</th><th>Poids bébé</th></tr></thead><tbody>';
        accouchements.forEach(a => {
            html += `<tr>
                <td>${a.ID_ACCOUCHEMENT}</td>
                <td>${formatDateTime(a.DATE_ACCOUCHEMENT)}</td>
                <td>Patiente #${a.ID_PATIENTE}</td>
                <td>${a.TYPE_ACCOUCHEMENT || '-'}</td>
                <td>${a.RESULTAT || '-'}</td>
                <td>${a.POIDS_BEBE ? a.POIDS_BEBE + ' kg' : '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement accouchements: ' + error.message, 'error');
    }
}


async function loadAudit() {
    try {
        const result = await apiCall('audit.php');
        const audits = result.data || [];
        const container = document.getElementById('tableAudit');
        
        if (!container) return;
        
        if (audits.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-clipboard-list"></i><p>Aucune entrée d\'audit</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>Date</th><th>Personnel</th><th>Action</th><th>Module</th><th>Table</th></tr></thead><tbody>';
        audits.slice(0, 50).forEach(a => {
            html += `<tr>
                <td>${formatDateTime(a.DATE_ACTION)}</td>
                <td>Personnel #${a.ID_PERSONNEL}</td>
                <td>${a.ACTION_AUDIT || '-'}</td>
                <td>${a.MODULE || '-'}</td>
                <td>${a.TABLE_CONCERNEE || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement audit: ' + error.message, 'error');
    }
}

/

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});