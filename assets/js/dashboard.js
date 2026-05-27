/* Dashboard UI behaviors: modals + company actions
   Expects window.DashboardConfig = { apiCompanies, apiDepartments, apiUsers }
   and window.AppAPI to be present.
*/
(function(){
  const config = window.DashboardConfig || {};
  const apiCompanies = config.apiCompanies;
  const apiDepartments = config.apiDepartments;
  const apiUsers = config.apiUsers;

  // Modal handling
  (function setupModals(){
    const overlay = document.getElementById('dashboard-overlay');
    const modals = document.querySelectorAll('.dashboard-modal');
    const openButtons = document.querySelectorAll('.dashboard-sidebar-link[data-modal-target]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');

    const closeAll = () => {
      modals.forEach((modal) => {
        modal.hidden = true;
        modal.classList.remove('is-open');
      });
      if (overlay) {
        overlay.hidden = true;
        overlay.classList.remove('is-open');
      }
    };

    openButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-modal-target');
        const targetModal = document.getElementById(targetId);
        if (!targetModal) return;
        closeAll();
        openButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');
        targetModal.hidden = false;
        targetModal.classList.add('is-open');
        if (overlay) { overlay.hidden = false; overlay.classList.add('is-open'); }
      });
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeAll));
    if (overlay) overlay.addEventListener('click', closeAll);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAll(); });
  })();

  // Company / Department / User actions
  (function setupCompanyActions(){
    if (!window.AppAPI) return;
    document.querySelectorAll('.dashboard-directory-card').forEach(card => {
      const companyId = card.getAttribute('data-company-id');
      if (!companyId) return;
      card.querySelectorAll('.company-actions [data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const action = btn.getAttribute('data-action');
          try {
            if (action === 'set-ip') {
              const ip = prompt('Adresse IP pour la signature (laisser vide per rimuovere):');
              if (ip === null) return;
              const j = await AppAPI.companies.setSignatureIp(apiCompanies, companyId, ip);
              if (!j.ok) alert('Erreur: ' + (j.error || 'unknown')); else alert('IP mise à jour');
              return;
            }

            if (action === 'delete') {
              if (!confirm('Confirmer la suppression de cette entreprise ?')) return;
              const j = await AppAPI.companies.delete(apiCompanies, companyId);
              if (!j.ok) alert('Erreur: ' + (j.error || 'unknown')); else location.reload();
              return;
            }

            if (action === 'manage-departments') {
              const j = await AppAPI.departments.list(apiDepartments, companyId);
              if (!j.ok) { alert('Erreur: ' + (j.error || 'unknown')); return; }
              const list = j.departments.map(d => `${d.id}: ${d.name}`).join('\n') || 'Aucun département';
              const cmd = prompt('Départements:\n' + list + '\n\nPour créer: saisir un nouveau nom. Pour supprimer: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.departments.delete(apiDepartments, id);
                if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
              } else {
                const jr = await AppAPI.departments.create(apiDepartments, companyId, cmd);
                if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'manage-employees') {
              const j = await AppAPI.users.listByCompany(apiUsers, companyId);
              if (!j.ok) { alert('Erreur: ' + (j.error || 'unknown')); return; }
              const list = j.users.map(u => `${u.id}: ${u.first_name} ${u.last_name} (${u.role})`).join('\n') || 'Aucun employé';
              const cmd = prompt('Employés:\n' + list + '\n\nPour créer: new:First Last,email,role. Pour supprimer: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.users.delete(apiUsers, id);
                if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
              } else if (cmd.startsWith('new:')) {
                const payload = cmd.substring(4).split(',');
                const name = payload[0] || ''; const email = payload[1] || ''; const role = payload[2] || 'employee';
                const names = name.split(' '); const first = names.shift(); const last = names.join(' ') || '';
                const jr = await AppAPI.users.create(apiUsers, { department_id: null, first_name: first, last_name: last, email, role });
                if (!jr.ok) alert('Erreur: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'assign-head') { alert('Utilisez le flux Gérer les employés puis assign-head via UI futura.'); return; }
            if (action === 'edit') { alert('Edit company — UI non implementata ancora.'); return; }

          } catch (err) { alert('Erreur réseau: ' + err.message); }
        });
      });
    });
  })();
})();
