/* Comportements de l'interface du tableau de bord : modales et actions sociétés.
  Attend window.DashboardConfig = { apiCompanies, apiDepartments, apiUsers }
  ainsi que window.AppAPI.
*/
(function(){
  const config = window.DashboardConfig || {};
  const apiCompanies = config.apiCompanies;
  const apiDepartments = config.apiDepartments;
  const apiUsers = config.apiUsers;

  // Gestion des modales
  (function setupModals(){
    const overlay = document.getElementById('dashboard-overlay');
    const modals = document.querySelectorAll('.dashboard-modal, .crud-modal');
    const openButtons = document.querySelectorAll('[data-modal-target]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    const crudModal = document.getElementById('crud-modal');
    const crudTitle = document.getElementById('crud-modal-title');
    const crudSubtitle = document.getElementById('crud-modal-subtitle');
    const crudTag = document.getElementById('crud-modal-tag');
    const crudBody = document.getElementById('crud-modal-body');

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
        const entity = button.getAttribute('data-modal-entity') || '';
        const title = button.getAttribute('data-modal-title') || button.textContent.trim();
        const targetModal = document.getElementById(targetId);
        if (!targetModal) return;
        closeAll();
        openButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');

        if (targetId === 'crud-modal' && crudModal) {
          const templateId = entity ? `crud-template-${entity}` : 'crud-template-placeholder';
          const template = document.getElementById(templateId) || document.getElementById('crud-template-placeholder');
          if (crudTitle) crudTitle.textContent = title;
          if (crudSubtitle) crudSubtitle.textContent = entity === 'companies' ? 'Super Admin only' : 'Common CRUD shell';
          if (crudTag) crudTag.textContent = entity ? entity.charAt(0).toUpperCase() + entity.slice(1) : 'CRUD';
          if (crudBody && template) crudBody.innerHTML = template.innerHTML;
        }

        targetModal.hidden = false;
        targetModal.classList.add('is-open');
        if (overlay) { overlay.hidden = false; overlay.classList.add('is-open'); }
      });
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeAll));
    if (overlay) overlay.addEventListener('click', closeAll);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAll(); });
  })();

  // Actions sociétés / départements / utilisateurs
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
              const ip = prompt('Signature IP address (leave blank to remove):');
              if (ip === null) return;
              const j = await AppAPI.companies.setSignatureIp(apiCompanies, companyId, ip);
              if (!j.ok) alert('Error: ' + (j.error || 'unknown')); else alert('IP updated');
              return;
            }

            if (action === 'delete') {
              if (!confirm('Confirm deletion of this company?')) return;
              const j = await AppAPI.companies.delete(apiCompanies, companyId);
              if (!j.ok) alert('Error: ' + (j.error || 'unknown')); else location.reload();
              return;
            }

            if (action === 'manage-departments') {
              const j = await AppAPI.departments.list(apiDepartments, companyId);
              if (!j.ok) { alert('Error: ' + (j.error || 'unknown')); return; }
              const list = j.departments.map(d => `${d.id}: ${d.name}`).join('\n') || 'No departments';
              const cmd = prompt('Departments:\n' + list + '\n\nTo create: type a new name. To delete: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.departments.delete(apiDepartments, id);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              } else {
                const jr = await AppAPI.departments.create(apiDepartments, companyId, cmd);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'manage-employees') {
              const j = await AppAPI.users.listByCompany(apiUsers, companyId);
              if (!j.ok) { alert('Error: ' + (j.error || 'unknown')); return; }
              const list = j.users.map(u => `${u.id}: ${u.first_name} ${u.last_name} (${u.role})`).join('\n') || 'No employees';
              const cmd = prompt('Employees:\n' + list + '\n\nTo create: new:First Last,email,role. To delete: del:<id>');
              if (!cmd) return;
              if (cmd.startsWith('del:')) {
                const id = cmd.split(':')[1];
                const jr = await AppAPI.users.delete(apiUsers, id);
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              } else if (cmd.startsWith('new:')) {
                const payload = cmd.substring(4).split(',');
                const name = payload[0] || ''; const email = payload[1] || ''; const role = payload[2] || 'employee';
                const names = name.split(' '); const first = names.shift(); const last = names.join(' ') || '';
                const jr = await AppAPI.users.create(apiUsers, { department_id: null, first_name: first, last_name: last, email, role });
                if (!jr.ok) alert('Error: ' + (jr.error || 'unknown')); else location.reload();
              }
              return;
            }

            if (action === 'assign-head') { alert('Use the Manage Employees flow and assign-head through the future UI.'); return; }
            if (action === 'edit') { alert('Edit company — UI not implemented yet.'); return; }

          } catch (err) { alert('Network error: ' + err.message); }
        });
      });
    });
  })();
})();
