// ============================================
// medecin_dash.js - Dashboard Médecin
// ============================================

// Vérification authentification
const user = requireAuth('PERSONNEL');
if (!user) throw new Error('Non autorisé');

const idPersonnel = user.id_personne;
const niveauAcces = user.niveau_acces || 0;

// Initialisation des infos utilisateur
const medecinNameEl = document.getElementById('medecinName');
const medecinRoleEl = document.getElementById('medecinRole');
if (medecinNameEl) {
    medecinNameEl.textContent = (user.profil?.PRENOM || '') + ' ' + (user.profil?.NOM || '');
}
if (medecinRoleEl) {
    medecinRoleEl.textContent = user.profil?.ROLE || 'Médecin';
}

// ============================================
// NAVIGATION
// ============================================

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
        case 'patientes': loadPatientes(); break;
        case 'grossesses': loadGrossesses(); break;
        case 'consultations': loadConsultations(); break;
        case 'admissions': loadAdmissions(); break;
        case 'accouchements': loadAccouchements(); break;
        case 'rdv': loadRdv(); break;
    }
}

// ============================================
// DASHBOARD
// ============================================

async function loadDashboard() {
    try {
        const [patientesResult, grossResult, consultResult, rdvResult, admResult, accResult] = await Promise.all([
            apiCall('patiente.php'),
            apiCall('grossesse.php'),
            apiCall('consultation.php'),
            apiCall('rendez_vous.php'),
            apiCall('admissions.php'),
            apiCall('accouchements.php')
        ]);

        const patientes = patientesResult.data || [];
        const grossesses = grossResult.data || [];
        const consultations = consultResult.data || [];
        const rdvs = rdvResult.data || [];
        const admissions = admResult.data || [];
        const accouchements = accResult.data || [];

        const today = new Date();
        const todayStr = today.toDateString();

        const urgences = rdvs.filter(r => r.STATUT === 'Urgent').length;
        const enObservation = admissions.filter(a => a.STATUT_ADMISSION === 'Observation').length;
        const consultationsToday = consultations.filter(c => new Date(c.DATE_CONSULTATION).toDateString() === todayStr).length;
        const accouchementsToday = accouchements.filter(a => new Date(a.DATE_ACCOUCHEMENT).toDateString() === todayStr).length;

        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };
        
        setText('statUrgences', urgences);
        setText('statObservation', enObservation);
        setText('statConsultations', consultationsToday);
        setText('statAccouchements', accouchementsToday);

        // Admissions actives
        const admissionsActives = admissions.filter(a => a.STATUT_ADMISSION === 'Actif');
        const containerAdmissions = document.getElementById('admissionsActives');
        if (containerAdmissions) {
            if (admissionsActives.length === 0) {
                containerAdmissions.innerHTML = '<div class="empty-state"><i class="fas fa-hospital"></i><p>Aucune admission active</p></div>';
            } else {
                let html = '<table><thead><tr><th>Patiente</th><th>Service</th><th>Date</th></tr></thead><tbody>';
                admissionsActives.slice(0, 5).forEach(a => {
                    const pat = patientes.find(p => p.ID_PATIENTE === a.ID_PATIENTE);
                    const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + a.ID_PATIENTE;
                    html += `<tr><td>${nomPat}</td><td>${a.SERVICE || '-'}</td><td>${formatDateTime(a.DATE_ADMISSION)}</td></tr>`;
                });
                html += '</tbody></table>';
                containerAdmissions.innerHTML = html;
            }
        }

        // Liste des patientes
        const containerPatientes = document.getElementById('listePatientes');
        if (containerPatientes) {
            if (patientes.length === 0) {
                containerPatientes.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>Aucune patiente</p></div>';
            } else {
                let html = '<table><thead><tr><th>Nom</th><th>Code suivi</th><th>Grossesse</th></tr></thead><tbody>';
                patientes.slice(0, 10).forEach(p => {
                    const gross = grossesses.find(g => g.ID_PATIENTE === p.ID_PATIENTE);
                    const trimestre = gross ? gross.TRIMESTRE : '-';
                    html += `<tr><td>${p.NOM || '-'} ${p.PRENOM || '-'}</td><td>${p.CODE_SUIVI || '-'}</td><td>${trimestre}</td></tr>`;
                });
                html += '</tbody></table>';
                containerPatientes.innerHTML = html;
            }
        }

        // Suivi des grossesses
        const containerGrossesses = document.getElementById('suiviGrossesses');
        if (containerGrossesses) {
            const grossEnCours = grossesses.filter(g => g.STATUT_GROSSESSE === 'En cours');
            if (grossEnCours.length === 0) {
                containerGrossesses.innerHTML = '<div class="empty-state"><i class="fas fa-baby"></i><p>Aucune grossesse en cours</p></div>';
            } else {
                let html = '<table><thead><tr><th>Patiente</th><th>Trimestre</th><th>Risque</th><th>Terme prévu</th></tr></thead><tbody>';
                grossEnCours.forEach(g => {
                    const pat = patientes.find(p => p.ID_PATIENTE === g.ID_PATIENTE);
                    const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + g.ID_PATIENTE;
                    const riskClass = g.NIVEAU_RISQUE === 'Eleve' ? 'badge-danger' : 
                                      g.NIVEAU_RISQUE === 'Modere' ? 'badge-warning' : 'badge-success';
                    html += `<tr><td>${nomPat}</td><td>${g.TRIMESTRE || '-'}</td><td><span class="badge ${riskClass}">${g.NIVEAU_RISQUE}</span></td><td>${formatDate(g.DATE_ACCOUCHEMENT_PREVUE)}</td></tr>`;
                });
                html += '</tbody></table>';
                containerGrossesses.innerHTML = html;
            }
        }

    } catch (error) {
        console.error('Erreur chargement dashboard:', error);
        showToast('Erreur chargement: ' + error.message, 'error');
    }
}

// ============================================
// AUTRES FONCTIONS (patientes, grossesses, etc.)
// ============================================

async function loadPatientes() {
    try {
        const result = await apiCall('patiente.php');
        const patientes = result.data || [];
        const container = document.getElementById('listePatientesComplete');
        
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

async function loadGrossesses() {
    try {
        const [grossResult, patResult] = await Promise.all([
            apiCall('grossesse.php'),
            apiCall('patiente.php')
        ]);
        
        const grossesses = grossResult.data || [];
        const patientes = patResult.data || [];

        const container = document.getElementById('listeGrossesses');
        if (!container) return;

        if (grossesses.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-baby"></i><p>Aucune grossesse</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Patiente</th><th>Trimestre</th><th>Terme prévu</th><th>Risque</th><th>Statut</th></tr></thead><tbody>';
        grossesses.forEach(g => {
            const pat = patientes.find(p => p.ID_PATIENTE === g.ID_PATIENTE);
            const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + g.ID_PATIENTE;
            const riskClass = g.NIVEAU_RISQUE === 'Eleve' ? 'badge-danger' : 
                              g.NIVEAU_RISQUE === 'Modere' ? 'badge-warning' : 'badge-success';
            html += `<tr>
                <td>${g.ID_GROSSESSE}</td>
                <td>${nomPat}</td>
                <td>${g.TRIMESTRE || '-'}</td>
                <td>${formatDate(g.DATE_ACCOUCHEMENT_PREVUE)}</td>
                <td><span class="badge ${riskClass}">${g.NIVEAU_RISQUE}</span></td>
                <td>${g.STATUT_GROSSESSE || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement grossesses: ' + error.message, 'error');
    }
}

async function loadConsultations() {
    try {
        const [consultResult, patResult] = await Promise.all([
            apiCall('consultation.php'),
            apiCall('patiente.php')
        ]);
        
        const consultations = consultResult.data || [];
        const patientes = patResult.data || [];

        const container = document.getElementById('listeConsultations');
        if (!container) return;

        if (consultations.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-stethoscope"></i><p>Aucune consultation</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Motif</th><th>Diagnostic</th></tr></thead><tbody>';
        consultations.forEach(c => {
            const pat = patientes.find(p => p.ID_PATIENTE === c.ID_PATIENTE);
            const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + c.ID_PATIENTE;
            html += `<tr>
                <td>${c.ID_CONSULTATION}</td>
                <td>${formatDateTime(c.DATE_CONSULTATION)}</td>
                <td>${nomPat}</td>
                <td>${c.MOTIF_CONSULTATION || '-'}</td>
                <td>${c.DIAGNOSTIC || '-'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement consultations: ' + error.message, 'error');
    }
}

async function loadAdmissions() {
    try {
        const [admResult, patResult] = await Promise.all([
            apiCall('admissions.php'),
            apiCall('patiente.php')
        ]);
        
        const admissions = admResult.data || [];
        const patientes = patResult.data || [];

        const container = document.getElementById('listeAdmissions');
        if (!container) return;

        if (admissions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-hospital"></i><p>Aucune admission</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Service</th><th>Urgence</th><th>Statut</th></tr></thead><tbody>';
        admissions.forEach(a => {
            const pat = patientes.find(p => p.ID_PATIENTE === a.ID_PATIENTE);
            const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + a.ID_PATIENTE;
            const urgClass = a.NIVEAU_URGENCE === 'Elevé' ? 'badge-danger' : 
                             a.NIVEAU_URGENCE === 'Modéré' ? 'badge-warning' : 'badge-success';
            const statClass = a.STATUT_ADMISSION === 'Actif' ? 'badge-success' : 'badge-info';
            html += `<tr>
                <td>${a.ID_ADMISSION}</td>
                <td>${formatDateTime(a.DATE_ADMISSION)}</td>
                <td>${nomPat}</td>
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

async function loadAccouchements() {
    try {
        const [accResult, patResult] = await Promise.all([
            apiCall('accouchements.php'),
            apiCall('patiente.php')
        ]);
        
        const accouchements = accResult.data || [];
        const patientes = patResult.data || [];

        const container = document.getElementById('listeAccouchements');
        if (!container) return;

        if (accouchements.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-baby-carriage"></i><p>Aucun accouchement</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Type</th><th>Résultat</th><th>Poids bébé</th></tr></thead><tbody>';
        accouchements.forEach(a => {
            const pat = patientes.find(p => p.ID_PATIENTE === a.ID_PATIENTE);
            const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + a.ID_PATIENTE;
            html += `<tr>
                <td>${a.ID_ACCOUCHEMENT}</td>
                <td>${formatDateTime(a.DATE_ACCOUCHEMENT)}</td>
                <td>${nomPat}</td>
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

async function loadRdv() {
    try {
        const [rdvResult, patResult] = await Promise.all([
            apiCall('rendez_vous.php'),
            apiCall('patiente.php')
        ]);
        
        const rdvs = rdvResult.data || [];
        const patientes = patResult.data || [];

        const container = document.getElementById('listeRdv');
        if (!container) return;

        if (rdvs.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>Aucun rendez-vous</p></div>';
            return;
        }

        let html = '<table><thead><tr><th>ID</th><th>Date</th><th>Patiente</th><th>Motif</th><th>Statut</th></tr></thead><tbody>';
        rdvs.forEach(r => {
            const pat = patientes.find(p => p.ID_PATIENTE === r.ID_PATIENTE);
            const nomPat = pat ? (pat.NOM + ' ' + pat.PRENOM) : 'Patiente #' + r.ID_PATIENTE;
            const badgeClass = r.STATUT === 'Confirmé' || r.STATUT === 'Confirme' ? 'badge-success' : 
                               r.STATUT === 'Urgent' ? 'badge-danger' : 
                               r.STATUT === 'Annulé' ? 'badge-warning' : 'badge-info';
            html += `<tr>
                <td>${r.ID_RENDEZ_VOUS}</td>
                <td>${formatDateTime(r.DATE_RDV)}</td>
                <td>${nomPat}</td>
                <td>${r.MOTIF || '-'}</td>
                <td><span class="badge ${badgeClass}">${r.STATUT}</span></td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        showToast('Erreur chargement RDV: ' + error.message, 'error');
    }
}

// ============================================
// MODALS
// ============================================

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

const formConsult = document.getElementById('formConsultation');
if (formConsult) {
    formConsult.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const data = {
            ID_PATIENTE: parseInt(formData.get('ID_PATIENTE')),
            ID_GROSSESSE: parseInt(formData.get('ID_GROSSESSE')),
            ID_PERSONNEL: idPersonnel,
            DATE_CONSULTATION: formData.get('DATE_CONSULTATION') || new Date().toISOString(),
            MOTIF_CONSULTATION: formData.get('MOTIF_CONSULTATION'),
            DIAGNOSTIC: formData.get('DIAGNOSTIC')
        };

        try {
            await apiCall('consultation.php', 'POST', data);
            showToast('Consultation créée avec succès', 'success');
            e.target.reset();
            closeModal('modalConsultation');
            loadConsultations();
        } catch (error) {
            showToast('Erreur: ' + error.message, 'error');
        }
    });
}

const formAdmission = document.getElementById('formAdmission');
if (formAdmission) {
    formAdmission.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const data = {
            ID_PATIENTE: parseInt(formData.get('ID_PATIENTE')),
            ID_GROSSESSE: parseInt(formData.get('ID_GROSSESSE')),
            ID_PERSONNEL: idPersonnel,
            ID_WORKSPACE: parseInt(formData.get('ID_WORKSPACE')),
            ID_LITS: parseInt(formData.get('ID_LITS')),
            MOTIF: formData.get('MOTIF'),
            SERVICE: formData.get('SERVICE'),
            NIVEAU_URGENCE: formData.get('NIVEAU_URGENCE') || 'Normal',
            STATUT_ADMISSION: 'Actif'
        };

        try {
            await apiCall('admissions.php', 'POST', data);
            showToast('Admission créée avec succès', 'success');
            e.target.reset();
            closeModal('modalAdmission');
            loadAdmissions();
        } catch (error) {
            showToast('Erreur: ' + error.message, 'error');
        }
    });
}

const formAccouchement = document.getElementById('formAccouchement');
if (formAccouchement) {
    formAccouchement.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        const data = {
            ID_PATIENTE: parseInt(formData.get('ID_PATIENTE')),
            ID_PERSONNEL: idPersonnel,
            DATE_ACCOUCHEMENT: formData.get('DATE_ACCOUCHEMENT') || new Date().toISOString(),
            TYPE_ACCOUCHEMENT: formData.get('TYPE_ACCOUCHEMENT'),
            RESULTAT: formData.get('RESULTAT'),
            POIDS_BEBE: parseFloat(formData.get('POIDS_BEBE')),
            TAILLE_BEBE: parseFloat(formData.get('TAILLE_BEBE')),
            NOMBRE_FAUSSE_COUCHES: parseInt(formData.get('NOMBRE_FAUSSE_COUCHES')) || 0
        };

        try {
            await apiCall('accouchements.php', 'POST', data);
            showToast('Accouchement enregistré avec succès', 'success');
            e.target.reset();
            closeModal('modalAccouchement');
            loadAccouchements();
        } catch (error) {
            showToast('Erreur: ' + error.message, 'error');
        }
    });
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});