/* Central API helpers for fetch and uploads */
(function(window){
  const AppAPI = {
    endpoints: {},
    init(endpoints){ this.endpoints = endpoints || {}; },
    postJSON: async function(url, payload){
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      return res.json();
    },
    uploadForm: async function(form){
      const fd = new FormData(form);
      const res = await fetch(form.action, { method: 'POST', body: fd });
      try { return await res.json(); } catch (e) { return res; }
    },
    companies: {
      setSignatureIp(url, companyId, ip){ return AppAPI.postJSON(url, { action: 'set_signature_ip', company_id: companyId, ip }); },
      delete(url, id){ return AppAPI.postJSON(url, { action: 'delete', id }); }
    },
    departments: {
      list(url, companyId){ return AppAPI.postJSON(url, { action: 'list', company_id: companyId }); },
      delete(url, id){ return AppAPI.postJSON(url, { action: 'delete', id }); },
      create(url, companyId, name){ return AppAPI.postJSON(url, { action: 'create', company_id: companyId, name }); }
    },
    users: {
      listByCompany(url, companyId){ return AppAPI.postJSON(url, { action: 'list_by_company', company_id: companyId }); },
      delete(url, id){ return AppAPI.postJSON(url, { action: 'delete', id }); },
      create(url, payload){ return AppAPI.postJSON(url, Object.assign({ action: 'create' }, payload)); }
    }
  };
  window.AppAPI = AppAPI;
})(window);
