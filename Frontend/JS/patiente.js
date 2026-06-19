

// Vérification authentification (utilise requireAuth de api.js)
const user = requireAuth('PATIENTE');
if (!user) throw new Error('Non autorisé');

// Récupérer l'ID_PATIENTE depuis le localStorage
const idPatiente = user.profil?.ID_PATIENTE || user.id_patiente;

// Initialisation des informations utilisateur
document.getElementById('patienteName').textContent = user.profil?.PRENOM + ' ' + user.profil?.NOM;
document.getElementById('prenomPatiente').textContent = user.profil?.PRENOM;
document.getElementById('codeSuivi').textContent = user.profil?.CODE_SUIVI || '-';
document.getElementById('codeSuiviTop').textContent = 'Code: ' + (user.profil?.CODE_SUIVI || '-');

/** Toggle le menu mobile (burger) */
function toggleMobileMenu() {
    const burger = document.querySelector('.burger-menu');
    const mobileMenu = document.getElementById('mobileMenu');
    if (burger) burger.classList.toggle('active');
    if (mobileMenu) mobileMenu.classList.toggle('active');
}

/**
 * Navigation entre les sections du dashboard
 * @param {string} sectionId - ID de la section à afficher
 * @param {HTMLElement} element - Élément de navigation cliqué
 */
function showSection(sectionId, element) {
    // Cacher toutes les sections
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + sectionId).classList.add('active');
    
    // Mettre à jour la navigation active (desktop)
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    if (element && window.innerWidth > 768) {
        element.classList.add('active');
    }
    
    // Mettre à jour la navigation active (mobile)
    document.querySelectorAll('.mobile-nav a').forEach(a => a.classList.remove('active'));
    if (element && window.innerWidth <= 768) {
        element.classList.add('active');
    }
    
    // Fermer le menu mobile après sélection
    if (window.innerWidth <= 768) {
        toggleMobileMenu();
    }
    
    // Charger les données selon la section
    if (sectionId === 'grossesse') loadGrossesse();
    if (sectionId === 'rdv') loadRdv();
    if (sectionId === 'documents') loadDocuments();
}

/**
 * Charger et afficher les alertes pour la patiente
 */
async function loadAlertes() {
    try {
        const alertes = [];
        
        // Vérifier les rendez-vous imminents
        const rdvResult = await apiCall('rdv.php');
        const rdvs = (rdvResult.data || []).filter(r => r.ID_PATIENTE == idPatiente);
        const today = new Date();
        
        rdvs.forEach(rdv => {
            const rdvDate = new Date(rdv.DATE_RDV);
            const diffDays = Math.ceil((rdvDate - today) / (1000 * 60 * 60 * 24));
            
            if (diffDays <= 2 && diffDays >= 0 && (rdv.STATUT === 'Confirmé' || rdv.STATUT === 'Confirme')) {
                alertes.push({
                    type: 'warning',
                    icon: 'fa-calendar-exclamation',
                    title: 'Rendez-vous imminent',
                    message: `Vous avez un rendez-vous dans ${diffDays} jour(s) - ${rdv.MOTIF || 'Consultation'}`
                });
            }
        });

        // Vérifier si grossesse à risque
        const grossResult = await apiCall('grossesse.php');
        const grossesse = (grossResult.data || []).find(g => g.ID_PATIENTE == idPatiente);
        
        if (grossesse && grossesse.NIVEAU_RISQUE === 'Eleve') {
            alertes.push({
                type: 'danger',
                icon: 'fa-exclamation-triangle',
                title: 'Grossesse à haut risque',
                message: 'Votre grossesse nécessite une surveillance particulière.'
            });
        }

        // Afficher les alertes dans le DOM
        const container = document.getElementById('alertesContainer');
        if (container) {
            container.innerHTML = alertes.length === 0 ? '' : alertes.map(alerte => `
                <div class="alerte-card ${alerte.type}">
                    <div class="alerte-header">
                        <i class="fas ${alerte.icon}"></i>
                        ${alerte.title}
                    </div>
                    <div>${alerte.message}</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement alertes:', error);
    }
}

/**
 * Charger les informations de grossesse de la patiente
 */
async function loadGrossesse() {
    try {
        const result = await apiCall('grossesse.php');
        const grossesse = (result.data || []).find(g => g.ID_PATIENTE == idPatiente);
        
        if (grossesse) {
            document.getElementById('grossTrimestre').textContent = grossesse.TRIMESTRE || '-';
            document.getElementById('grossDatePrevue').textContent = formatDate(grossesse.DATE_ACCOUCHEMENT_PREVUE);
            document.getElementById('grossDateDebut').textContent = formatDate(grossesse.DATE_DEBUT);
            document.getElementById('grossDateAccouchement').textContent = formatDate(grossesse.DATE_ACCOUCHEMENT_PREVUE);

            // Badge de niveau de risque avec couleur
            const riskClass = grossesse.NIVEAU_RISQUE === 'Eleve' ? 'badge-danger' : 
                              grossesse.NIVEAU_RISQUE === 'Modere' ? 'badge-warning' : 'badge-success';
            document.getElementById('grossRisque').innerHTML = `<span class="badge ${riskClass}">${grossesse.NIVEAU_RISQUE}</span>`;
            document.getElementById('grossStatut').textContent = grossesse.STATUT_GROSSESSE || '-';
            document.getElementById('grossFoetus').textContent = grossesse.NOMBRE_FOETUS || '1';
            document.getElementById('grossObservation').textContent = grossesse.OBSERVATION || 'Aucune observation particulière';
        } else {
            document.getElementById('grossTrimestre').textContent = 'Aucune grossesse enregistrée';
        }

        // Charger l'historique des consultations
        const consultResult = await apiCall('consultation.php');
        const consults = (consultResult.data || []).filter(c => c.ID_PATIENTE == idPatiente);

        // Afficher dans la section grossesse (historique complet)
        const container = document.getElementById('historiqueConsultations');
        if (container) {
            if (consults.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-stethoscope"></i><p>Aucune consultation enregistrée</p></div>';
            } else {
                let html = '<table><thead><tr><th>Date</th><th>Motif</th><th>Diagnostic</th></tr></thead><tbody>';
                consults.forEach(c => {
                    html += `<tr><td>${formatDateTime(c.DATE_CONSULTATION)}</td><td>${c.MOTIF_CONSULTATION || '-'}</td><td>${c.DIAGNOSTIC || '-'}</td></tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            }
        }

        // Afficher les dernières consultations dans la section accueil
        const containerAccueil = document.getElementById('dernieresConsultations');
        if (containerAccueil) {
            const recentes = consults.slice(0, 3); // 3 dernières consultations
            if (recentes.length === 0) {
                containerAccueil.innerHTML = '<div class="empty-state"><i class="fas fa-stethoscope"></i><p>Aucune consultation récente</p></div>';
            } else {
                let html = '<table><thead><tr><th>Date</th><th>Motif</th><th>Diagnostic</th></tr></thead><tbody>';
                recentes.forEach(c => {
                    html += `<tr><td>${formatDateTime(c.DATE_CONSULTATION)}</td><td>${c.MOTIF_CONSULTATION || '-'}</td><td>${c.DIAGNOSTIC || '-'}</td></tr>`;
                });
                html += '</tbody></table>';
                containerAccueil.innerHTML = html;
            }
        }
    } catch (error) {
        showToast('Erreur chargement grossesse: ' + error.message, 'error');
    }
}

/**
 * Charger les rendez-vous de la patiente
 */
async function loadRdv() {
    try {
        const result = await apiCall('rdv.php');
        const rdvs = (result.data || []).filter(r => r.ID_PATIENTE == idPatiente);
        
        // Prochains RDV (pour la section accueil)
        const nextRdvs = rdvs.filter(r => new Date(r.DATE_RDV) >= new Date()).slice(0, 3);
        
        const container1 = document.getElementById('prochainsRdv');
        if (container1) {
            if (nextRdvs.length === 0) {
                container1.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>Aucun rendez-vous prévu</p></div>';
            } else {
                let html = '<table><thead><tr><th>Date</th><th>Motif</th><th>Statut</th></tr></thead><tbody>';
                nextRdvs.forEach(r => {
                    const badgeClass = r.STATUT === 'Confirmé' || r.STATUT === 'Confirme' ? 'badge-success' : 
                                       r.STATUT === 'Urgent' ? 'badge-danger' : 'badge-info';
                    html += `<tr><td>${formatDateTime(r.DATE_RDV)}</td><td>${r.MOTIF || '-'}</td><td><span class="badge ${badgeClass}">${r.STATUT}</span></td></tr>`;
                });
                html += '</tbody></table>';
                container1.innerHTML = html;
            }
        }
        
        // Tous les RDV (pour la section dédiée)
        const container2 = document.getElementById('tousRdv');
        if (container2) {
            if (rdvs.length === 0) {
                container2.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>Aucun rendez-vous</p></div>';
            } else {
                let html = '<table><thead><tr><th>Date</th><th>Motif</th><th>Statut</th></tr></thead><tbody>';
                rdvs.forEach(r => {
                    const badgeClass = r.STATUT === 'Confirmé' || r.STATUT === 'Confirme' ? 'badge-success' : 
                                       r.STATUT === 'Urgent' ? 'badge-danger' : 
                                       r.STATUT === 'Annulé' ? 'badge-warning' : 'badge-info';
                    html += `<tr><td>${formatDateTime(r.DATE_RDV)}</td><td>${r.MOTIF || '-'}</td><td><span class="badge ${badgeClass}">${r.STATUT}</span></td></tr>`;
                });
                html += '</tbody></table>';
                container2.innerHTML = html;
            }
        }
    } catch (error) {
        showToast('Erreur chargement RDV: ' + error.message, 'error');
    }
}

/**
 * Charger et compter les documents médicaux par catégorie
 */
async function loadDocuments() {
    try {
        const result = await apiCall('document.php');
        const docs = (result.data || []).filter(d => d.ID_PATIENTE == idPatiente);
        
        // Compter les documents par type
        const counts = {
            'Echographie': 0,
            'Ordonnance': 0,
            'Bilan sanguin': 0,
            'Compte rendu': 0,
            'Autre': 0
        };

        docs.forEach(d => {
            if (counts[d.TYPE_DOCUMENT] !== undefined) {
                counts[d.TYPE_DOCUMENT]++;
            } else {
                counts['Autre']++;
            }
        });

        // Mettre à jour les compteurs dans le DOM
        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };
        
        setText('countEchographies', counts['Echographie']);
        setText('countOrdonnances', counts['Ordonnance']);
        setText('countBilans', counts['Bilan sanguin']);
        setText('countComptesRendus', counts['Compte rendu']);
        setText('countAutres', counts['Autre']);
    } catch (error) {
        console.error('Erreur chargement documents:', error);
    }
}

/**
 * Gérer l'upload de documents médicaux
 */
async function handleFileUpload(input) {
    const files = input.files;
    if (files.length === 0) return;
    
    for (let file of files) {
        // Vérifier la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showToast(`Le fichier ${file.name} dépasse 5MB`, 'error');
            continue;
        }
        
        // Vérifier le type MIME
        const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            showToast(`Le fichier ${file.name} n'est pas un format valide`, 'error');
            continue;
        }
        
        try {
            // Convertir en base64
            const base64 = await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result.split(',')[1]);
                reader.readAsDataURL(file);
            });
            
            // Déterminer le type de document selon le nom du fichier
            let typeDoc = 'Autre';
            const fileName = file.name.toLowerCase();
            if (fileName.includes('echo') || fileName.includes('ultra')) typeDoc = 'Echographie';
            else if (fileName.includes('ordon') || fileName.includes('prescription')) typeDoc = 'Ordonnance';
            else if (fileName.includes('bilan') || fileName.includes('sang')) typeDoc = 'Bilan sanguin';
            else if (fileName.includes('compte') || fileName.includes('rendu')) typeDoc = 'Compte rendu';
            
            // Envoyer à l'API
            const data = {
                ID_PATIENTE: idPatiente,
                TYPE_DOCUMENT: typeDoc,
                NOM_DOCUMENT: file.name,
                CONTENU: base64,
                TYPE_MIME: file.type,
                TAILLE: file.size
            };
            
            await apiCall('document.php', 'POST', data);
            showToast('Document uploadé avec succès', 'success');
            
        } catch (error) {
            showToast('Erreur upload: ' + error.message, 'error');
        }
    }

    // Recharger la liste des documents
    loadDocuments();
}

/**
 * Formulaire d'auto-évaluation quotidienne
 */
const formAutoEval = document.getElementById('formAutoEval');
if (formAutoEval) {
    formAutoEval.addEventListener('submit', async (e) => {
        e.preventDefault();
        showToast('Auto-évaluation enregistrée', 'success');
        e.target.reset();
    });
}

/**
 * Ouvrir un modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

/**
 * Fermer un modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

/**
 * Formulaire de création de rendez-vous
 */
const formRdv = document.getElementById('formRdv');
if (formRdv) {
    formRdv.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData(formRdv);
            const data = {
                ID_PATIENTE: idPatiente,
                DATE_RDV: formData.get('DATE_RDV'),
                MOTIF: formData.get('MOTIF') || null,
                STATUT: 'Confirmé'
            };
            
            await apiCall('rdv.php', 'POST', data);
            showToast('Rendez-vous demandé avec succès', 'success');
            closeModal('modalRdv');
            formRdv.reset();
            loadRdv(); // Recharger la liste des rendez-vous
        } catch (error) {
            showToast('Erreur création RDV: ' + error.message, 'error');
        }
    });
}

// Chargement initial au démarrage de la page
document.addEventListener('DOMContentLoaded', () => {
    loadAlertes();
    loadGrossesse();
    loadRdv();
    loadDocuments();
});